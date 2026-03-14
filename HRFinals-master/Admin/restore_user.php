<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

require '../database.php';

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    // Update the status back to 1 (Active)
    $query = "UPDATE user SET status = 1 WHERE user_id = $user_id";

    if ($mysql->query($query)) {
        $_SESSION['status_icon'] = 'success';
        $_SESSION['status_title'] = 'Restored!';
        $_SESSION['status_text'] = 'The user has been successfully restored to Active status.';
    } else {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Error';
        $_SESSION['status_text'] = 'Failed to restore user: ' . $mysql->error;
    }
}

// Redirect back to the archived users page
header("Location: archived_users.php");
exit();
?>