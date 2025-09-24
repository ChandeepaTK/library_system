<?php
require_once 'config.php';
requireLibrarian();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if (!$user_id) {
            jsonResponse(['success' => false, 'message' => 'User ID is required'], 400);
        }
        
        // Check  user exists and is a student
        $stmt = $pdo->prepare("SELECT full_name, user_type FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }
        
        if ($user['user_type'] !== 'student') {
            jsonResponse(['success' => false, 'message' => 'Cannot delete librarian accounts'], 400);
        }
        
        // Ceck  user has borrowed books
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
        $stmt->execute([$user_id]);
        $borrowedCount = $stmt->fetchColumn();
        
        if ($borrowedCount > 0) {
            jsonResponse(['success' => false, 'message' => 'Cannot delete user. They have ' . $borrowedCount . ' borrowed book(s)'], 400);
        }
        
        // Delete the user 
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        jsonResponse([
            'success' => true,
            'message' => 'User "' . $user['full_name'] . '" deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Delete user error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to delete user'], 500);
    }
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
}
?>