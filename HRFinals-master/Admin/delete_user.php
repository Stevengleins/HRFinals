<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

require '../database.php';

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    // Update the status to 0 (Archived) instead of actually deleting them
    $query = "UPDATE user SET status = 0 WHERE user_id = $user_id";

    if ($mysql->query($query)) {
        $_SESSION['status_icon'] = 'success';
        $_SESSION['status_title'] = 'Archived!';
        $_SESSION['status_text'] = 'The user has been successfully archived.';
    } else {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Error';
        $_SESSION['status_text'] = 'Failed to archive user: ' . $mysql->error;
    }
}

// Redirect back to the active user management page
header("Location: user_management.php");
exit();
?>