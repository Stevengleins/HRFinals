<?php
session_start();
require_once('../database.php');

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    exit("Unauthorized Access");
}

// Force the browser to download a CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Employee_Directory_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Title Rows for context in the Excel file
fputcsv($output, ['WORKFORCEPRO - Official Employee Directory']);
fputcsv($output, ['Date Exported: ' . date('F j, Y')]);
fputcsv($output, []); // Blank row for spacing

// Output the column headings
fputcsv($output, ['Employee Name', 'Position', 'System Role', 'Email Address', 'Mobile Number', 'Gender', 'Join Date', 'Account Status']);

// Query to pull all active employees and HR staff
$query = "
    SELECT 
        u.email as u_email, u.role as u_role, u.first_name as u_first, u.last_name as u_last, u.status,
        e.first_name as e_first, e.middle_name, e.last_name as e_last, e.suffix,
        e.position, e.gender, e.mobile_number, e.join_date
    FROM user u 
    LEFT JOIN employee_details e ON u.user_id = e.user_id
    WHERE u.role != 'Admin' AND u.status = 1
    ORDER BY u.first_name ASC
";
$result = $mysql->query($query);

while ($row = $result->fetch_assoc()) {
    // Smart Full Name Construction
    $f_name = !empty($row['e_first']) ? $row['e_first'] : $row['u_first'];
    $m_name = !empty($row['middle_name']) ? $row['middle_name'] : '';
    $l_name = !empty($row['e_last']) ? $row['e_last'] : $row['u_last'];
    $s_name = !empty($row['suffix']) ? $row['suffix'] : '';
    
    $name_parts = [];
    if (!empty($f_name)) $name_parts[] = $f_name;
    if (!empty($m_name)) $name_parts[] = $m_name;
    if (!empty($l_name)) $name_parts[] = $l_name;
    if (!empty($s_name)) $name_parts[] = $s_name;
    
    $full_name = implode(' ', $name_parts);
    
    // Fallbacks for empty data
    $email = !empty($row['u_email']) ? $row['u_email'] : 'N/A';
    $role = !empty($row['u_role']) ? $row['u_role'] : 'N/A';
    $joinDate = !empty($row['join_date']) ? date('M d, Y', strtotime($row['join_date'])) : 'N/A';
    $statusText = ($row['status'] == 1) ? 'Active' : 'Archived';

    // Print to CSV
    fputcsv($output, [
        $full_name,
        $row['position'] ?? 'Not Assigned',
        $role,
        $email,
        $row['mobile_number'] ?? 'N/A',
        $row['gender'] ?? 'N/A',
        $joinDate,
        $statusText
    ]);
}

fclose($output);
exit();
?>