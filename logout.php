<?php
session_start();

// clear all data
session_unset();
session_destroy();


if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header("Location: login.html?message=You have been logged out successfully");
exit();
?>