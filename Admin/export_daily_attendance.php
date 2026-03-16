<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    exit("Unauthorized Access");
}

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Force the browser to download a CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=daily_attendance_' . $filter_date . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Title Row for context in the Excel file
fputcsv($output, ['Daily Attendance Report', 'Date: ' . date('F j, Y', strtotime($filter_date))]);
fputcsv($output, []); // Blank row for spacing

// Output the column headings
fputcsv($output, ['Employee Name', 'Role', 'Date', 'Time In', 'Time Out', 'Status']);

// Prepare and run the same query used in the view
$attendanceQuery = $mysql->prepare("
    SELECT a.*, u.first_name, u.last_name, u.role
    FROM attendance a
    JOIN user u ON a.user_id = u.user_id
    WHERE a.date = ?
    ORDER BY a.time_in ASC
");
$attendanceQuery->bind_param("s", $filter_date);
$attendanceQuery->execute();
$result = $attendanceQuery->get_result();

while ($row = $result->fetch_assoc()) {
    
    if (!empty($row['time_out'])) {
        $status = 'Present';
    } elseif (!empty($row['time_in'])) {
        $status = 'Checked In';
    } else {
        $status = 'Absent';
    }

    $time_in_formatted = $row['time_in'] ? date('g:i A', strtotime($row['time_in'])) : '-';
    $time_out_formatted = $row['time_out'] ? date('g:i A', strtotime($row['time_out'])) : '-';
    $date_formatted = date('M j, Y', strtotime($row['date']));

    fputcsv($output, [
        $row['first_name'] . ' ' . $row['last_name'],
        $row['role'],
        $date_formatted,
        $time_in_formatted,
        $time_out_formatted,
        $status
    ]);
}

$attendanceQuery->close();
fclose($output);
exit();
?>