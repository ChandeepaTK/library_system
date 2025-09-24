<?php
require_once 'config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        
        // Validation
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Username is required";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }
        
        // Check if username already exists
        if (!empty($username)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Username already exists";
            }
        }
        
        // Check if email already exists
        if (!empty($email)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email already exists";
            }
        }
        
        if (!empty($errors)) {
            $errorMessage = implode(', ', $errors);
            header("Location: register.html?error=" . urlencode($errorMessage));
            exit();
        }
        
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, user_type) 
            VALUES (?, ?, ?, ?, 'student')
        ");
        
        $stmt->execute([$username, $email, $hashedPassword, $username]);
        
        // Registration successful
        header("Location: login.html?success=Registration successful! Please login.");
        exit();
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        header("Location: register.html?error=Registration failed. Please try again.");
        exit();
    }
} else {
    
    header("Location: register.html");
    exit();
}
?>