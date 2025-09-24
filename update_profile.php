<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        $user_id = $_SESSION['user_id'];
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            updateProfileInfo($pdo, $user_id);
        } elseif ($action === 'change_password') {
            changePassword($pdo, $user_id);
        } else {
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }
        
    } catch (PDOException $e) {
        error_log("Update profile error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update profile'], 500);
    }
} else {
    
    try {
        $pdo = getDBConnection();
        $user_id = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("
            SELECT username, email, full_name, phone, address, user_type
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }
        
        jsonResponse(['success' => true, 'user' => $user]);
        
    } catch (PDOException $e) {
        error_log("Get profile error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to get profile'], 500);
    }
}

function updateProfileInfo($pdo, $user_id) {
    
    $full_name = sanitizeInput($_POST['full_name']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    
    
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (!empty($phone) && !preg_match('/^[\d\-\+\(\)\s]+$/', $phone)) {
        $errors[] = "Invalid phone number format";
    }
    
    if (!empty($errors)) {
        jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
    }
    
    try {
        
        $pdo->beginTransaction();
        
        
        error_log("Updating user $user_id with: full_name='$full_name', phone='$phone', address='$address'");
        
       
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, phone = ?, address = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $full_name,
            $phone ?: null,
            $address ?: null,
            $user_id
        ]);
        
        
        $rowsAffected = $stmt->rowCount();
        error_log("Rows affected: " . $rowsAffected);
        
        if ($rowsAffected === 0) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'No changes were made or user not found'], 400);
        }
        
        
        $pdo->commit();
        
        
        $_SESSION['full_name'] = $full_name;
        
        jsonResponse([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
        
    } catch (PDOException $e) {
        
        $pdo->rollBack();
        error_log("Update profile error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
    }
}

function changePassword($pdo, $user_id) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = "Current password is required";
    }
    
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New password and confirmation do not match";
    }
    
    if (!empty($errors)) {
        jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
    }
    
    try {
        
        $pdo->beginTransaction();
        
       
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }
        
        
        if (!password_verify($current_password, $user['password'])) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'Current password is incorrect'], 400);
        }
        
    
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$new_password_hash, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'Failed to update password'], 500);
        }
        
        
        $pdo->commit();
        
        jsonResponse([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Change password error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Database error occurred'], 500);
    }
}
?>