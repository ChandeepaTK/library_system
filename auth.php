<?php
require_once 'config.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit();
}

// login
try {
    $pdo = getDBConnection();
    
    
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = sanitizeInput($_POST['userType'] ?? '');
    
    
    if (empty($email) || empty($password) || empty($userType)) {
        $message = 'All fields are required';
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $message], 400);
        } else {
            header("Location: login.html?error=" . urlencode($message));
            exit();
        }
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format';
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $message], 400);
        } else {
            header("Location: login.html?error=" . urlencode($message));
            exit();
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT id, username, email, password, full_name, user_type 
        FROM users 
        WHERE email = ? AND user_type = ?
    ");
    $stmt->execute([$email, $userType]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $message = 'Invalid credentials or user type';
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $message], 401);
        } else {
            header("Location: login.html?error=" . urlencode($message));
            exit();
        }
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        $message = 'Invalid credentials';
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $message], 401);
        } else {
            header("Location: login.html?error=" . urlencode($message));
            exit();
        }
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['user_type'] = $user['user_type'];
    
    $redirectUrl = ($user['user_type'] === 'librarian') ? 'librarian_dashboard.php' : 'student_dashboard.php';
    
    if (isAjaxRequest()) {
        jsonResponse([
            'success' => true, 
            'message' => 'Login successful',
            'redirect' => $redirectUrl,
            'user_type' => $user['user_type']
        ]);
    } else {
        header("Location: " . $redirectUrl);
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    $message = 'Login failed. Please try again';
    
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => $message], 500);
    } else {
        header("Location: login.html?error=" . urlencode($message));
        exit();
    }
}
?>