<?php
session_start();
include('includes/config.php');
$pdo_login = pdo_connect_mysql2();

// Check if the user is logged in
if(isset($_SESSION['stafflogin'])) {
    // Set the user's online status to 0 in the database to indicate they are offline
    $user_id = $_SESSION['eid']; // Assuming 'eid' is the user's ID
    $sql_update_online_status = "UPDATE tblemployee SET online_status = 0 WHERE id = :user_id";
    $query_update_online_status = $pdo_login->prepare($sql_update_online_status);
    $query_update_online_status->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $query_update_online_status->execute();

    // Clear session data
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 60*60,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy session
    session_destroy();
}

if (isset($_SESSION['login_time'])) {
    // Check if more than 1 hour (3600 seconds) has passed since login
    $current_time = time();
    $elapsed_time = $current_time - $_SESSION['login_time'];
    
    if ($elapsed_time > 40800) {
     
        $user_id = $_SESSION['eid']; // Assuming 'eid' is the user's ID
        $sql_update_online_status = "UPDATE tblemployee SET online_status = 0 WHERE id = :user_id";
        $query_update_online_status = $pdo_login->prepare($sql_update_online_status);
        $query_update_online_status->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $query_update_online_status->execute();

        // Clear session data and destroy session
        $_SESSION = array();
        session_destroy();

        // Redirect to the login page
        header("Location: https://localhost/vg/login/login.php");
        exit(); // Ensure the script stops executing
    }
}


header("Location: https://localhost/vg/login/login.php");
?>
