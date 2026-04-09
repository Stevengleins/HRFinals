<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    exit("Unauthorized Access");
}

$view = $_GET['view'] ?? 'daily';
$filter_val = $_GET['filter_val'] ?? date('Y-m-d');
$emp_id = $_GET['emp_id'] ?? '';

$whereClause = "";
$title = "Attendance_Export";

// Match the exact same filtering logic as the main page
if ($view === 'daily') {
    $whereClause = "WHERE a.date = '$filter_val'";
    $title = "Daily_Attendance_" . $filter_val;
} elseif ($view === 'weekly') {
    $db_week = str_replace('-W', '', $filter_val);
    $whereClause = "WHERE YEARWEEK(a.date, 1) = '$db_week'";
    $title = "Weekly_Attendance_" . $filter_val;
} elseif ($view === 'monthly') {
    $whereClause = "WHERE DATE_FORMAT(a.date, '%Y-%m') = '$filter_val'";
    $title = "Monthly_Attendance_" . $filter_val;
} elseif ($view === 'yearly') {
    $whereClause = "WHERE YEAR(a.date) = '$filter_val'";
    $title = "Yearly_Attendance_" . $filter_val;
} elseif ($view === 'dtr') {
    $whereClause = "WHERE a.user_id = '$emp_id' AND DATE_FORMAT(a.date, '%Y-%m') = '$filter_val'";
    $title = "Employee_DTR_" . $filter_val;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $title . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, ['WORKFORCEPRO - Official Time & Attendance Record']);
fputcsv($output, ['Export Type:', strtoupper($view), 'Period:', $filter_val]);
fputcsv($output, []); 

// DTR vs Standard Headers
if ($view === 'dtr') {
    fputcsv($output, ['Date', 'Shift', 'Time In', 'Time Out', 'Reg. Hrs', 'OT Hrs', 'Status']);
} else {
    fputcsv($output, ['Date', 'Employee Name', 'Role', 'Shift', 'Time In', 'Time Out', 'Reg. Hrs', 'OT Hrs', 'Status']);
}

$queryStr = "
    SELECT 
        a.*, u.role, u.first_name as u_first, u.last_name as u_last,
        e.first_name as e_first, e.middle_name, e.last_name as e_last, e.suffix,
        e.shift_start, e.shift_end
    FROM attendance a
    JOIN user u ON a.user_id = u.user_id
    LEFT JOIN employee_details e ON u.user_id = e.user_id
    $whereClause
    ORDER BY a.date DESC, a.time_in ASC
";

$result = $mysql->query($queryStr);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $f_name = !empty($row['e_first']) ? $row['e_first'] : $row['u_first'];
        $m_name = !empty($row['middle_name']) ? $row['middle_name'] : '';
        $l_name = !empty($row['e_last']) ? $row['e_last'] : $row['u_last'];
        $s_name = !empty($row['suffix']) ? $row['suffix'] : '';
        $full_name = trim("$f_name $m_name $l_name $s_name");

        $date = date('M d, Y', strtotime($row['date']));
        $shift_start = !empty($row['shift_start']) ? $row['shift_start'] : '08:00:00';
        $shift_end = !empty($row['shift_end']) ? $row['shift_end'] : '17:00:00';
        $shiftDisplay = date('h:i A', strtotime($shift_start)) . ' - ' . date('h:i A', strtotime($shift_end));

        $tIn = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-';
        $tOut = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-';
        
        // OT Math
        $regular_hours = 0.00;
        $overtime_hours = 0.00;
        
        if ($row['time_in'] && $row['time_out']) {
            $total_calculated = round((strtotime($row['time_out']) - strtotime($row['time_in'])) / 3600, 2);
            if ($total_calculated > 8) {
                $regular_hours = 8.00;
                $overtime_hours = $total_calculated - 8.00;
            } else {
                $regular_hours = $total_calculated;
            }
        } else {
            $regular_hours = $row['regular_hours'] ?? 0;
            $overtime_hours = $row['overtime_hours'] ?? 0;
        }

        // Status Logic
        $status = !empty($row['time_out']) ? 'Completed' : (!empty($row['time_in']) ? 'Active' : 'Absent');
        if (!empty($row['time_in'])) {
            $actual_in = strtotime(date('H:i:s', strtotime($row['time_in'])));
            $expected_in = strtotime($shift_start);
            if ($actual_in > $expected_in) {
                $status = 'Late (' . floor(($actual_in - $expected_in) / 60) . 'm)';
            }
        }
        if (!empty($row['time_out'])) {
            $actual_out = strtotime(date('H:i:s', strtotime($row['time_out'])));
            $expected_out = strtotime($shift_end);
            if ($actual_out < $expected_out) {
                $status = 'Early Out (' . floor(($expected_out - $actual_out) / 60) . 'm)';
            }
        }

        if ($view === 'dtr') {
            fputcsv($output, [$date, $shiftDisplay, $tIn, $tOut, number_format($regular_hours, 2), number_format($overtime_hours, 2), $status]);
        } else {
            fputcsv($output, [$date, $full_name, $row['role'], $shiftDisplay, $tIn, $tOut, number_format($regular_hours, 2), number_format($overtime_hours, 2), $status]);
        }
    }
}

fclose($output);
exit();
?>