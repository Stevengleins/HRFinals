<?php
session_start();
include('../database.php');

// Security: Only Admins can delete
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['user_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['user_id']);

    // Extra Security: Ensure we aren't deleting an Admin via URL manipulation
    $check = "SELECT role FROM users WHERE user_id = '$id'";
    $res = mysqli_query($conn, $check);
    $user = mysqli_fetch_assoc($res);

    if ($user && $user['role'] !== 'Admin') {
        $query = "DELETE FROM users WHERE user_id = '$id'";
        if (mysqli_query($conn, $query)) {
            $_SESSION['msg'] = "User deleted successfully!";
        }
    }
}

header("Location: user_management.php"); // Redirect back
exit();
?>