<?php
session_start();
require '../database.php';

// 1. Force PHP and MySQL to strictly use Philippine Time
date_default_timezone_set('Asia/Manila');
$mysql->query("SET time_zone = '+08:00'");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

$today = date('Y-m-d');

// CRITICAL FIX: We must send the FULL Date AND Time so MySQL does not default to 00:00:00
$fullDateTimeNow = date('Y-m-d H:i:s'); 
$displayTime = date('h:i A'); // Used just for the Success popup

if ($action === 'Time In') {
    // Check if they already clocked in today
    $check = $mysql->query("SELECT * FROM attendance WHERE user_id = '$user_id' AND date = '$today'");
    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'You have already clocked in today.']);
        exit();
    }

    // Insert the perfect Full DateTime string
    $stmt = $mysql->prepare("INSERT INTO attendance (user_id, date, time_in) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $today, $fullDateTimeNow);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Successfully clocked in at ' . $displayTime]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    }
    $stmt->close();

} elseif ($action === 'Time Out') {
    // Make sure they have a Time In first
    $check = $mysql->query("SELECT * FROM attendance WHERE user_id = '$user_id' AND date = '$today'");
    if ($check->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'You must clock in first.']);
        exit();
    }
    
    $att = $check->fetch_assoc();
    if (!empty($att['time_out']) && $att['time_out'] !== '0000-00-00 00:00:00') {
        echo json_encode(['status' => 'error', 'message' => 'You have already clocked out today.']);
        exit();
    }

    // Update the record with the Time Out
    $stmt = $mysql->prepare("UPDATE attendance SET time_out = ? WHERE user_id = ? AND date = ?");
    $stmt->bind_param("sis", $fullDateTimeNow, $user_id, $today);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Successfully clocked out at ' . $displayTime]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action received.']);
}
?>