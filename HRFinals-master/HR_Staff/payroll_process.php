<?php
session_start();
require '../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $payroll_period = trim($_POST['payroll_period']);
    $days_worked = intval($_POST['days_worked']);
    $daily_rate = floatval($_POST['daily_rate']);
    $deductions = floatval($_POST['deductions']);
    $status = trim($_POST['status']);

    if (empty($user_id) || empty($payroll_period) || $days_worked < 0 || $daily_rate < 0 || $deductions < 0) {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Input';
        $_SESSION['status_text'] = 'Please fill out all payroll fields correctly.';
        header("Location: payroll.php");
        exit();
    }

    $gross_salary = $days_worked * $daily_rate;
    $net_salary = $gross_salary - $deductions;

    if ($net_salary < 0) {
        $net_salary = 0;
    }

    $stmt = $mysql->prepare("
        INSERT INTO payroll (user_id, payroll_period, days_worked, daily_rate, gross_salary, deductions, net_salary, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "isidddds",
        $user_id,
        $payroll_period,
        $days_worked,
        $daily_rate,
        $gross_salary,
        $deductions,
        $net_salary,
        $status
    );

    if ($stmt->execute()) {
        $_SESSION['status_icon'] = 'success';
        $_SESSION['status_title'] = 'Payroll Saved';
        $_SESSION['status_text'] = 'Payroll record has been created successfully.';
    } else {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Save Failed';
        $_SESSION['status_text'] = 'Unable to save payroll record.';
    }
}

header("Location: payroll.php");
exit();
?>