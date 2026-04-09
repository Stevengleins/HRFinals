<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    exit("Unauthorized Access");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Official_Leave_Records_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Title Rows
fputcsv($output, ['WORKFORCEPRO - Official Leave Records Masterlist']);
fputcsv($output, ['Exported On:', date('F j, Y h:i A')]);
fputcsv($output, []); // Blank row for spacing

// Output the column headings
fputcsv($output, ['Employee Name', 'Leave Type', 'Start Date', 'End Date', 'Reason', 'HR Remarks', 'Status', 'Date Applied']);

$query = "
    SELECT lr.*, u.first_name, u.last_name 
    FROM leave_requests lr
    JOIN user u ON lr.user_id = u.user_id
    ORDER BY lr.date_applied DESC
";
$result = $mysql->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
        $startDate = date('M d, Y', strtotime($row['start_date']));
        $endDate = date('M d, Y', strtotime($row['end_date']));
        $dateApplied = date('M d, Y', strtotime($row['date_applied']));
        $remarks = !empty($row['remarks']) ? $row['remarks'] : 'None';

        // Print to CSV
        fputcsv($output, [
            $fullName,
            $row['leave_type'],
            $startDate,
            $endDate,
            $row['reason'],
            $remarks,
            $row['status'],
            $dateApplied
        ]);
    }
}

fclose($output);
exit();
?>