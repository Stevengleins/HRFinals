<?php
session_start();
require '../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    exit("Unauthorized Access");
}

$type = $_GET['type'] ?? 'all';
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Force the browser to download a CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=payroll_export_' . date('Y-m-d_His') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, ['Employee Name', 'Payroll Period', 'Days Worked', 'Daily Rate', 'Gross Salary', 'Deductions', 'Net Salary', 'Status']);

// Build the query based on the export type
$queryStr = "
    SELECT u.first_name, u.last_name, p.payroll_period, p.days_worked, 
           p.daily_rate, p.gross_salary, p.deductions, p.net_salary, p.status 
    FROM payroll p 
    JOIN user u ON p.user_id = u.user_id
";

if ($type === 'single' && $userId) {
    $queryStr .= " WHERE p.user_id = $userId";
}

$queryStr .= " ORDER BY p.payroll_id DESC";

$result = $mysql->query($queryStr);

// Output the data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['first_name'] . ' ' . $row['last_name'],
        $row['payroll_period'],
        $row['days_worked'],
        $row['daily_rate'],
        $row['gross_salary'],
        $row['deductions'],
        $row['net_salary'],
        $row['status']
    ]);
}

fclose($output);
exit();
?>