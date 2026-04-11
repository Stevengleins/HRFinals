<?php
session_start();

// Strict Security Check: ONLY Employees can access their own data
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}

require '../database.php';

$user_id = $_SESSION['user_id'];
$month = $_GET['month'] ?? date('Y-m');

// Fetch user info to name the file
$uQuery = $mysql->query("SELECT first_name, last_name FROM `user` WHERE user_id = '$user_id'");
$user = $uQuery->fetch_assoc();
$empName = str_replace(' ', '_', $user['first_name'] . '_' . $user['last_name']);
$filename = "DTR_" . $empName . "_" . $month . ".csv";

// Force browser to download a CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open the output stream
$output = fopen('php://output', 'w');

// Write the Column Headers
fputcsv($output, ['Date', 'Shift', 'Time In', 'Time Out', 'Regular Hours', 'Overtime Hours', 'Daily Status']);

// Fetch the DTR Data
$query = "
    SELECT a.*, e.shift_start, e.shift_end 
    FROM `attendance` a
    LEFT JOIN `employee_details` e ON a.user_id = e.user_id
    WHERE a.user_id = '$user_id' AND DATE_FORMAT(a.date, '%Y-%m') = '$month'
    ORDER BY a.date ASC
";
$result = $mysql->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $date = date('M d, Y', strtotime($row['date']));
        $shift_start = !empty($row['shift_start']) ? $row['shift_start'] : '08:00:00';
        $shift_end = !empty($row['shift_end']) ? $row['shift_end'] : '17:00:00';
        $shiftDisplay = date('h:i A', strtotime($shift_start)) . ' - ' . date('h:i A', strtotime($shift_end));

        $tIn = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '--:--';
        $tOut = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '--:--';
        
        $regular_hours = 0.00;
        $overtime_hours = 0.00;
        
        if ($row['time_in'] && $row['time_out']) {
            $diff = strtotime($row['time_out']) - strtotime($row['time_in']);
            $total_calculated = round($diff / 3600, 2);
            if ($total_calculated > 8) {
                $regular_hours = 8.00;
                $overtime_hours = $total_calculated - 8.00;
            } else {
                $regular_hours = $total_calculated;
            }
        }

        // Calculate Status
        $statusStr = 'Absent';
        if (!empty($row['time_out'])) $statusStr = 'Completed';
        elseif (!empty($row['time_in'])) $statusStr = 'Active / Working';

        // Lateness / Early Out Modifiers
        if (!empty($row['time_in'])) {
            $actual_in = strtotime(date('H:i:s', strtotime($row['time_in'])));
            $expected_in = strtotime($shift_start);
            if ($actual_in > $expected_in) {
                $mins_late = floor(($actual_in - $expected_in) / 60);
                if ($mins_late > 0) $statusStr .= " (Late: {$mins_late}m)";
            }
        }
        
        if (!empty($row['time_out'])) {
            $actual_out = strtotime(date('H:i:s', strtotime($row['time_out'])));
            $expected_out = strtotime($shift_end);
            if ($actual_out < $expected_out) {
                $mins_early = floor(($expected_out - $actual_out) / 60);
                if ($mins_early > 0) $statusStr .= " (Early Out: {$mins_early}m)";
            }
        }

        // Write row to CSV
        fputcsv($output, [
            $date,
            $shiftDisplay,
            $tIn,
            $tOut,
            number_format($regular_hours, 2),
            number_format($overtime_hours, 2),
            $statusStr
        ]);
    }
} else {
    fputcsv($output, ['No attendance records found for this month.']);
}

fclose($output);
exit();
?>