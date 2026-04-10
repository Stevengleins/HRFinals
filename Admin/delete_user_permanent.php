<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    // Begin a transaction to ensure we delete everything or nothing
    $mysql->begin_transaction();

    try {
        // 1. Delete all child records first to prevent Foreign Key crashes
        $mysql->query("DELETE FROM `attendance` WHERE user_id = $user_id");
        $mysql->query("DELETE FROM `leave_requests` WHERE user_id = $user_id");
        $mysql->query("DELETE FROM `payroll` WHERE user_id = $user_id");
        $mysql->query("DELETE FROM `employee_requests` WHERE user_id = $user_id");
        
        // 2. Delete Employee Details profile
        $mysql->query("DELETE FROM `employee_details` WHERE user_id = $user_id");
        
        // 3. Finally, delete the main user account
        $stmt = $mysql->prepare("DELETE FROM `user` WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // If everything succeeds, commit the changes to the database
        $mysql->commit();

        $_SESSION['status_icon'] = 'success';
        $_SESSION['status_title'] = 'Deleted!';
        $_SESSION['status_text'] = 'The user and all of their historical records were permanently deleted.';

    } catch (Exception $e) {
        // If ANY deletion fails, rollback everything to prevent partial data corruption
        $mysql->rollback();
        
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Database Error';
        $_SESSION['status_text'] = 'Failed to delete user: ' . $e->getMessage();
    }
}

// Redirect back to the archived users page
header("Location: archived_users.php");
exit();
?>