<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    // Use Prepared Statements and Backticks for the `user` table
    $stmt = $mysql->prepare("UPDATE `user` SET status = 1 WHERE user_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['status_icon'] = 'success';
            $_SESSION['status_title'] = 'Restored!';
            $_SESSION['status_text'] = 'The user has been successfully restored to Active status.';
        } else {
            $_SESSION['status_icon'] = 'error';
            $_SESSION['status_title'] = 'Error';
            $_SESSION['status_text'] = 'Failed to restore user: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Database Error';
        $_SESSION['status_text'] = 'Query preparation failed: ' . $mysql->error;
    }
}

// Redirect back to the archived users page
header("Location: archived_users.php");
exit();
?>