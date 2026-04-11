<?php
session_start();
require '../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $request_id = intval($_GET['id']);

    $stmt = $mysql->prepare("UPDATE employee_requests SET status = 'Reviewed' WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
}

header("Location: requesthr.php");
exit();
?>