<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $attendance_id = (int)$_GET['id'];
    $action = $_GET['action'];
    $status = '';

    if ($action === 'apply' && $_SESSION['role'] === 'Employee') {
        $status = 'Pending';
        $msg = 'Your overtime request has been successfully submitted to HR.';
    } elseif ($action === 'approve' && ($_SESSION['role'] === 'HR Staff' || $_SESSION['role'] === 'Admin')) {
        $status = 'Approved';
        $msg = 'Overtime approved successfully.';
    } elseif ($action === 'reject' && ($_SESSION['role'] === 'HR Staff' || $_SESSION['role'] === 'Admin')) {
        $status = 'Rejected';
        $msg = 'Overtime request rejected.';
    }

    if ($status !== '') {
        $stmt = $mysql->prepare("UPDATE attendance SET overtime_status = ? WHERE attendance_id = ?");
        $stmt->bind_param("si", $status, $attendance_id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['status_icon'] = 'success';
        $_SESSION['status_title'] = 'Success!';
        $_SESSION['status_text'] = $msg;
    }
}

// Smoothly redirect back to the page they clicked the button from
$referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: " . $referer);
exit();
?>