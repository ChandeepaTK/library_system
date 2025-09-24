<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'library_management');


define('SITE_URL', 'http://localhost/library-system/');
define('BOOKS_PER_PAGE', 10);
define('BORROW_DURATION_DAYS', 14);
define('FINE_PER_DAY', 1.00);
define('MAX_BOOKS_PER_USER', 5); // Added missing constant


function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}


function isLibrarian() {
    return isLoggedIn() && $_SESSION['user_type'] === 'librarian';
}


function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student';
}


function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.html?error=Please login to access this page');
        exit();
    }
}


function requireLibrarian() {
    requireLogin();
    if (!isLibrarian()) {
        header('Location: login.html?error=Access denied. Librarian details required');
        exit();
    }
}


function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: login.html?error=Access denied. Student details required');
        exit();
    }
}


function sanitizeInput($input) {
    if ($input === null) return '';
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}


function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}


function updateOverdueBorrowings() {
    try {
        $pdo = getDBConnection();
        
        
        $stmt = $pdo->prepare("
            UPDATE borrowings 
            SET status = 'overdue', 
                fine_amount = DATEDIFF(CURDATE(), due_date) * ?
            WHERE status = 'borrowed' 
            AND due_date < CURDATE()
        ");
        $stmt->execute([FINE_PER_DAY]);
    } catch (PDOException $e) {
        error_log("Update overdue borrowings error: " . $e->getMessage());
    }
}


function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, user_type, phone, address FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get current user error: " . $e->getMessage());
        return null;
    }
}


function canBorrowMore($userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as borrowed_count 
            FROM borrowings 
            WHERE user_id = ? AND status IN ('borrowed', 'overdue')
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return ($result['borrowed_count'] < MAX_BOOKS_PER_USER);
    } catch (PDOException $e) {
        error_log("Check borrow limit error: " . $e->getMessage());
        return false;
    }
}


if (isLoggedIn()) {
    updateOverdueBorrowings();
}
?>