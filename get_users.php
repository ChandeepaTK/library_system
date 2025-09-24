<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);


ob_start();


header('Content-Type: application/json');

try {
    require_once 'config.php';
    requireLibrarian();

    $pdo = getDBConnection();

    
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'search':
            searchUsers();
            break;
        case 'get':
            getUserById();
            break;
        case 'all':
            getAllUsers();
            break;
        default:
            getAllUsers();
            break;
    }
} catch (Exception $e) {
    
    ob_clean();
    error_log("Error in get_users.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred while processing your request']);
}

function searchUsers() {
    global $pdo;
    
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT id, username, email, full_name, user_type, status, created_at FROM users WHERE user_type = 'student'";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (full_name LIKE ? OR email LIKE ? OR username LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " ORDER BY full_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    
    foreach ($users as &$user) {
        $userStats = getUserBorrowingStats($user['id']);
        $user['stats'] = $userStats;
    }
    
    jsonResponse(['success' => true, 'users' => $users]);
}

function getUserById() {
    global $pdo;
    
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'User ID required'], 400);
    }
    
    $stmt = $pdo->prepare("
        SELECT id, username, email, full_name, phone, address, user_type, status, created_at 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }
    
    $user['stats'] = getUserBorrowingStats($user['id']);
    
    jsonResponse(['success' => true, 'user' => $user]);
}

function getAllUsers() {
    global $pdo;
    
    $page = $_GET['page'] ?? 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    
    $countStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'student'");
    $totalUsers = $countStmt->fetchColumn();
    
    
    $stmt = $pdo->prepare("
        SELECT id, username, email, full_name, user_type, status, created_at 
        FROM users 
        WHERE user_type = 'student'
        ORDER BY full_name ASC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $users = $stmt->fetchAll();
    
    
    foreach ($users as &$user) {
        $userStats = getUserBorrowingStats($user['id']);
        $user['stats'] = $userStats;
    }
    
    $totalPages = ceil($totalUsers / $limit);
    
    jsonResponse([
        'success' => true,
        'users' => $users,
        'pagination' => [
            'current_page' => (int)$page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
            'users_per_page' => $limit
        ]
    ]);
}

function getUserBorrowingStats($userId) {
    global $pdo;
    
    $stats = [];
    
    try {
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
        $stmt->execute([$userId]);
        $stats['borrowed'] = $stmt->fetchColumn();

        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'returned'");
        $stmt->execute([$userId]);
        $stats['returned'] = $stmt->fetchColumn();

        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'overdue'");
        $stmt->execute([$userId]);
        $stats['overdue'] = $stmt->fetchColumn();

        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'borrowed' AND due_date < CURDATE()");
        $stmt->execute([$userId]);
        $overdueCount = $stmt->fetchColumn();

        
        $stats['overdue'] += $overdueCount;

        
        $stats['total_fine'] = 0.00;
        

    } catch (Exception $e) {
        
        error_log("Error in getUserBorrowingStats: " . $e->getMessage());
        $stats = [
            'borrowed' => 0,
            'returned' => 0,
            'overdue' => 0,
            'total_fine' => 0.00
        ];
    }

    return $stats;
}
?>