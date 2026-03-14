<?php
session_start();

// Changed to check for HR Staff role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

require '../database.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    
    // Check your database structure, if your primary key is id instead of leave_id, you may need to update this variable
    $leave_id = (int)$_GET['id'];
    $new_status = mysqli_real_escape_string($mysql, $_GET['status']);

    if ($new_status === 'Approved' || $new_status === 'Rejected') {
        
        // Ensure this column matches your DB (either 'id' or 'leave_id')
        $updateQuery = "UPDATE leave_requests SET status = '$new_status' WHERE leave_id = $leave_id";

        if ($mysql->query($updateQuery)) {
            $_SESSION['status_icon'] = 'success';
            $_SESSION['status_title'] = 'Success!';
            $_SESSION['status_text'] = 'The leave request has been ' . strtolower($new_status) . '.';
        } else {

            $_SESSION['status_icon'] = 'error';
            $_SESSION['status_title'] = 'Database Error';
            $_SESSION['status_text'] = 'Could not update the request: ' . $mysql->error;
        }
    } else {
        
        $_SESSION['status_icon'] = 'warning';
        $_SESSION['status_title'] = 'Invalid Status';
        $_SESSION['status_text'] = 'The requested status is not recognized.';
    }
} else {
    $_SESSION['status_icon'] = 'error';
    $_SESSION['status_title'] = 'Error';
    $_SESSION['status_text'] = 'Missing leave ID or status.';
}

header("Location: leave_management.php");
exit();
?>