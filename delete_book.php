<?php
require_once 'config.php';
requireLibrarian();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        $book_id = (int)($_POST['book_id'] ?? 0);
        
        if (!$book_id) {
            jsonResponse(['success' => false, 'message' => 'Book ID is required'], 400);
        }
        
        // Check book exists
        $stmt = $pdo->prepare("SELECT title FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();
        
        if (!$book) {
            jsonResponse(['success' => false, 'message' => 'Book not found'], 404);
        }
        
        // Check  book is currently borrowed
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE book_id = ? AND status = 'borrowed'");
        $stmt->execute([$book_id]);
        $borrowedCount = $stmt->fetchColumn();
        
        if ($borrowedCount > 0) {
            jsonResponse(['success' => false, 'message' => 'Cannot delete book. It is currently borrowed by ' . $borrowedCount . ' user(s)'], 400);
        }
        
        // Delete the book
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Book "' . $book['title'] . '" deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Delete book error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to delete book'], 500);
    }
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
}
?>