<?php
session_start();
require '../database.php';

header('Content-Type: application/json'); 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please log in again.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
$currentTime = date('H:i:s');

if ($action === 'Time In') {
    $check = $mysql->query("SELECT * FROM attendance WHERE user_id = '$user_id' AND date = '$today'");
    
    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'You have already clocked in for today!']);
    } else {
        $mysql->query("INSERT INTO attendance (user_id, date, time_in) VALUES ('$user_id', '$today', '$currentTime')");
        echo json_encode(['status' => 'success', 'message' => 'Successfully clocked in at ' . date('h:i A')]);
    }

} elseif ($action === 'Time Out') {
    $check = $mysql->query("SELECT * FROM attendance WHERE user_id = '$user_id' AND date = '$today'");
    
    if ($check->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'You need to clock in first!']);
    } else {
        $row = $check->fetch_assoc();
        
        if ($row['time_out'] !== null) {
            echo json_encode(['status' => 'error', 'message' => 'You have already clocked out for today.']);
        } else {
            $mysql->query("UPDATE attendance SET time_out = '$currentTime' WHERE user_id = '$user_id' AND date = '$today'");
            echo json_encode(['status' => 'success', 'message' => 'Successfully clocked out at ' . date('h:i A')]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>