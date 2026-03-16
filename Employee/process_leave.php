<?php
session_start();
header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require '../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $leave_type = mysqli_real_escape_string($mysql, $_POST['leave_type']);
    $start_date = mysqli_real_escape_string($mysql, $_POST['start_date']);
    $end_date   = mysqli_real_escape_string($mysql, $_POST['end_date']);
    $reason     = mysqli_real_escape_string($mysql, $_POST['reason']);

    // Validate dates
    if (strtotime($end_date) < strtotime($start_date)) {
        echo json_encode(['status' => 'error', 'message' => 'End date cannot be earlier than start date.']);
        exit();
    }

    // Insert into database
    $query = "INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status) 
              VALUES (?, ?, ?, ?, ?, 'Pending')";
              
    $stmt = $mysql->prepare($query);
    $stmt->bind_param("issss", $user_id, $leave_type, $start_date, $end_date, $reason);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $mysql->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>  