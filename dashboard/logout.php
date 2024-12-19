<?php
session_start();
include('includes/config.php');
$pdo = pdo_connect_mysql();

// Check if the user is logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Update the user's online status in the `adminuser` table
    $user_id = $_SESSION['id']; // Assuming 'id' is the user's ID in the session

    // Clear session data
    $_SESSION = array();

    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();
}

// Redirect to the login page
header("Location: admin-login.php");
exit; // Stop further script execution
