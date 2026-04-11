<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    exit("Unauthorized Access");
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$title = "Official_Report";
$displayTitle = "Official Report";
$reportData = '';

// ==========================================
// 1. USERS DIRECTORY EXPORT
// ==========================================
if ($type === 'users') {
    $title = "Employee_Directory_" . date('Y_m_d');
    $displayTitle = "Official Employee Directory";
    
    $query = $mysql->query("
        SELECT 
            u.email as u_email, u.role as u_role, u.first_name as u_first, u.last_name as u_last,
            e.first_name as e_first, e.middle_name, e.last_name as e_last, e.suffix,
            e.position, e.mobile_number, e.join_date
        FROM user u 
        LEFT JOIN employee_details e ON u.user_id = e.user_id
        WHERE u.role != 'Admin' AND u.status = 1
        ORDER BY u.first_name ASC
    ");
    
    $reportData .= "<table><thead><tr><th>Employee Name</th><th>Position</th><th>Role</th><th>Email</th><th>Mobile</th><th>Join Date</th></tr></thead><tbody>";
    if($query->num_rows > 0) {
        while($row = $query->fetch_assoc()) {
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
            $email = !empty($row['u_email']) ? $row['u_email'] : 'N/A';
            $role = !empty($row['u_role']) ? $row['u_role'] : 'N/A';
            $position = !empty($row['position']) ? $row['position'] : 'Not Assigned';
            $mobile = !empty($row['mobile_number']) ? $row['mobile_number'] : 'N/A';
            $joinDate = !empty($row['join_date']) ? date('M d, Y', strtotime($row['join_date'])) : 'N/A';
            
            $reportData .= "<tr>
                <td style='font-weight: bold;'>{$full_name}</td>
                <td>{$position}</td>
                <td>{$role}</td>
                <td>{$email}</td>
                <td>{$mobile}</td>
                <td>{$joinDate}</td>
            </tr>";
        }
    } else {
        $reportData .= "<tr><td colspan='6' style='text-align:center; color: #7f8c8d;'>No active employees found.</td></tr>";
    }
    $reportData .= "</tbody></table>";

// ==========================================
// 2. DASHBOARD DAILY ATTENDANCE EXPORT
// ==========================================
} elseif ($type === 'attendance') {
    $date = date('Y-m-d');
    $title = "Daily_Attendance_" . date('Y_m_d');
    $displayTitle = "Daily Attendance Report - " . date('F d, Y');
    
    $query = $mysql->query("SELECT a.*, u.first_name, u.last_name, u.role FROM attendance a JOIN user u ON a.user_id = u.user_id WHERE a.date = '$date' ORDER BY a.time_in ASC");
    
    $reportData .= "<table><thead><tr><th>Employee Name</th><th>Role</th><th>Time In</th><th>Time Out</th><th>Status</th></tr></thead><tbody>";
    if($query->num_rows > 0) {
        while($row = $query->fetch_assoc()) {
            $status = !empty($row['time_out']) ? 'Present' : (!empty($row['time_in']) ? 'Checked In' : 'Absent');
            $tIn = $row['time_in'] ? date('g:i A', strtotime($row['time_in'])) : '-';
            $tOut = $row['time_out'] ? date('g:i A', strtotime($row['time_out'])) : '-';
            
            $reportData .= "<tr>
                <td style='font-weight: bold;'>{$row['first_name']} {$row['last_name']}</td>
                <td>{$row['role']}</td>
                <td>{$tIn}</td>
                <td>{$tOut}</td>
                <td><b>{$status}</b></td>
            </tr>";
        }
    } else {
        $reportData .= "<tr><td colspan='5' style='text-align:center; color: #7f8c8d;'>No attendance data available for today.</td></tr>";
    }
    $reportData .= "</tbody></table>";

// ==========================================
// 3. PAYROLL EXPORT
// ==========================================
} elseif ($type === 'payroll') {
    $title = "Master_Payroll_Expenses_" . date('Y_m_d');
    $displayTitle = "Master Payroll Expenses Report";
    $query = $mysql->query("SELECT u.first_name, u.last_name, p.payroll_period, p.net_salary, p.status FROM payroll p JOIN user u ON p.user_id = u.user_id ORDER BY p.payroll_id DESC");
    
    $reportData .= "<table><thead><tr><th>Employee Name</th><th>Payroll Period</th><th>Net Salary</th><th>Status</th></tr></thead><tbody>";
    if($query->num_rows > 0) {
        while($row = $query->fetch_assoc()) {
            $reportData .= "<tr>
                <td style='font-weight: bold;'>{$row['first_name']} {$row['last_name']}</td>
                <td>{$row['payroll_period']}</td>
                <td>₱ " . number_format($row['net_salary'], 2) . "</td>
                <td><b>{$row['status']}</b></td>
            </tr>";
        }
    } else {
        $reportData .= "<tr><td colspan='4' style='text-align:center; color: #7f8c8d;'>No payroll data available.</td></tr>";
    }
    $reportData .= "</tbody></table>";

// ==========================================
// 4. LEAVES EXPORT
// ==========================================
} elseif ($type === 'leaves') {
    $title = "Leave_Records_Masterlist_" . date('Y_m_d');
    $displayTitle = "Official Leave Records Masterlist";
    $query = $mysql->query("SELECT lr.*, u.first_name, u.last_name FROM leave_requests lr JOIN user u ON lr.user_id = u.user_id ORDER BY lr.date_applied DESC");
    
    $reportData .= "<table><thead><tr><th>Employee Name</th><th>Type</th><th>Duration</th><th>Status</th><th>Applied On</th></tr></thead><tbody>";
    if($query->num_rows > 0) {
        while($row = $query->fetch_assoc()) {
            $duration = date('M d', strtotime($row['start_date'])) . ' - ' . date('M d, Y', strtotime($row['end_date']));
            $applied = date('M d, Y', strtotime($row['date_applied']));
            
            $reportData .= "<tr>
                <td style='font-weight: bold;'>{$row['first_name']} {$row['last_name']}</td>
                <td>{$row['leave_type']}</td>
                <td>{$duration}</td>
                <td><b>{$row['status']}</b></td>
                <td>{$applied}</td>
            </tr>";
        }
    } else {
        $reportData .= "<tr><td colspan='5' style='text-align:center; color: #7f8c8d;'>No leave data available.</td></tr>";
    }
    $reportData .= "</tbody></table>";

// ==========================================
// 5. DYNAMIC ATTENDANCE / DTR EXPORT
// ==========================================
} elseif ($type === 'dynamic_attendance') {
    $view = $_GET['view'] ?? 'daily';
    $raw_filter_val = $_GET['filter_val'] ?? date('Y-m-d');
    $emp_id = $_GET['emp_id'] ?? '';

    // Safely parse incoming date
    $time_ref = strtotime($raw_filter_val) ?: time();

    $whereClause = "";
    if ($view === 'daily') { 
        $filter_val = date('Y-m-d', $time_ref);
        $whereClause = "WHERE a.date = '$filter_val'"; 
        $displayTitle = "Daily Attendance Record - " . date('M d, Y', strtotime($filter_val)); 
        $title = "Daily_Attendance_Record_" . $filter_val;
    } elseif ($view === 'weekly') { 
        $filter_val = date('Y-\WW', $time_ref);
        $db_week = str_replace('-W', '', $filter_val); 
        $whereClause = "WHERE YEARWEEK(a.date, 1) = '$db_week'"; 
        $displayTitle = "Weekly Attendance Record - " . $filter_val; 
        $title = "Weekly_Attendance_Record_" . $filter_val;
    } elseif ($view === 'monthly') { 
        $filter_val = date('Y-m', $time_ref);
        $whereClause = "WHERE DATE_FORMAT(a.date, '%Y-%m') = '$filter_val'"; 
        $displayTitle = "Monthly Attendance Record - " . date('F Y', strtotime($filter_val . '-01')); 
        $title = "Monthly_Attendance_Record_" . $filter_val;
    } elseif ($view === 'yearly') { 
        $filter_val = date('Y', $time_ref);
        $whereClause = "WHERE YEAR(a.date) = '$filter_val'"; 
        $displayTitle = "Yearly Attendance Record - " . $filter_val; 
        $title = "Yearly_Attendance_Record_" . $filter_val;
    } elseif ($view === 'dtr') { 
        $filter_val = date('Y-m', $time_ref);
        $whereClause = "WHERE a.user_id = '$emp_id' AND DATE_FORMAT(a.date, '%Y-%m') = '$filter_val'"; 
        $displayTitle = "Employee DTR Record - " . date('F Y', strtotime($filter_val . '-01')); 
        $title = "Employee_DTR_Record_" . $filter_val;
    }

    $query = $mysql->query("
        SELECT 
            a.*, u.role, u.first_name as u_first, u.last_name as u_last, 
            e.first_name as e_first, e.middle_name, e.last_name as e_last, e.suffix,
            e.shift_start, e.shift_end
        FROM attendance a 
        JOIN user u ON a.user_id = u.user_id 
        LEFT JOIN employee_details e ON u.user_id = e.user_id
        $whereClause ORDER BY a.date DESC, a.time_in ASC
    ");

    $reportData .= "<table><thead><tr>";
    if($view !== 'dtr') $reportData .= "<th>Employee Name</th>";
    $reportData .= "<th>Date</th><th>Shift</th><th>Time In</th><th>Time Out</th><th>Reg. Hrs</th><th>OT Hrs</th><th>Status</th></tr></thead><tbody>";
    
    if($query && $query->num_rows > 0) {
        while($row = $query->fetch_assoc()) {
            $f_name = !empty($row['e_first']) ? $row['e_first'] : $row['u_first'];
            $m_name = !empty($row['middle_name']) ? $row['middle_name'] : '';
            $l_name = !empty($row['e_last']) ? $row['e_last'] : $row['u_last'];
            $s_name = !empty($row['suffix']) ? $row['suffix'] : '';
            $full_name = trim("$f_name $m_name $l_name $s_name");

            $date = date('M d, Y', strtotime($row['date']));
            
            $shift_start = !empty($row['shift_start']) ? $row['shift_start'] : '08:00:00';
            $shift_end = !empty($row['shift_end']) ? $row['shift_end'] : '17:00:00';
            $shiftDisplay = date('h:i A', strtotime($shift_start)) . ' - ' . date('h:i A', strtotime($shift_end));

            $tIn = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '--:--';
            $tOut = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '--:--';
            
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
            
            $status = !empty($row['time_out']) ? 'Completed' : (!empty($row['time_in']) ? 'Active' : 'Absent');
            if (!empty($row['time_in'])) {
                $actual_in = strtotime(date('H:i:s', strtotime($row['time_in'])));
                $expected_in = strtotime($shift_start);
                if ($actual_in > $expected_in) {
                    $status = '<b>Late</b> (' . floor(($actual_in - $expected_in) / 60) . 'm)';
                }
            }
            if (!empty($row['time_out'])) {
                $actual_out = strtotime(date('H:i:s', strtotime($row['time_out'])));
                $expected_out = strtotime($shift_end);
                if ($actual_out < $expected_out) {
                    $status = '<b>Early Out</b> (' . floor(($expected_out - $actual_out) / 60) . 'm)';
                }
            }

            $reportData .= "<tr>";
            if($view !== 'dtr') $reportData .= "<td style='font-weight: bold;'>{$full_name}</td>";
            $reportData .= "<td>{$date}</td><td>{$shiftDisplay}</td><td>{$tIn}</td><td>{$tOut}</td><td>" . number_format($regular_hours, 2) . "</td><td>" . number_format($overtime_hours, 2) . "</td><td>{$status}</td></tr>";
        }
    } else {
        $cols = ($view === 'dtr') ? 7 : 8;
        $reportData .= "<tr><td colspan='{$cols}' style='text-align:center; color: #7f8c8d;'>No records found for this period.</td></tr>";
    }
    $reportData .= "</tbody></table>";

} else {
    die("Invalid Report Type.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generating PDF...</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #f4f6f9;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        
        #pdf-document {
            width: 1000px;
            padding: 0; 
            box-sizing: border-box;
            background: #fff;
        }
        
        /* THE CLEAN DESIGN: White Header with bottom border */
        .header {
            background-color: #ffffff; 
            color: #000000;
            padding: 25px 35px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid #2c3e50; 
        }
        .brand-container {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
        }
        .brand-image {
            max-height: 32px;
            border-radius: 4px;
            margin-right: 12px;
        }
        .header-left h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50; 
            letter-spacing: 1px;
            line-height: 1;
        }
        .header-left p {
            margin: 4px 0 0;
            font-size: 14px;
            color: #7f8c8d; 
            font-weight: bold;
        }
        .header-right {
            text-align: right;
            font-size: 12px;
            color: #555555;
        }
        
        .table-container {
            padding: 25px 35px;
        }
        
        /* SPREADSHEET TABLE STYLING */
        table {
            width: 100%;
            border-collapse: collapse; 
            margin-bottom: 20px;
        }
        tr {
            page-break-inside: avoid;
        }
        thead {
            display: table-header-group;
        }
        th, td {
            border: 1px solid #dee2e6; 
            padding: 10px 12px; 
            text-align: left;
            font-size: 13px; 
            word-wrap: break-word;
        }
        th {
            background-color: #343a40; 
            color: #ffffff;
            font-weight: bold;
            text-align: center; 
            border-color: #454d55;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) { 
            background-color: #f8f9fa; 
        }
        
        .footer {
            text-align: left;
            font-size: 11px;
            color: #6c757d;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="loader-overlay" id="loader">
        <h2 style="color: #2c3e50; margin-bottom: 5px;">Generating Spreadsheet PDF...</h2>
        <p style="color: #7f8c8d;">Please wait, your download will begin automatically.</p>
    </div>

    <div id="pdf-document">
        
        <div class="header">
            <div class="header-left">
                <div class="brand-container">
                    <img src="../logo.png" alt="WORKFORCEPRO" class="brand-image" />
                    <h1>WORKFORCEPRO</h1>
                </div>
                <p><?php echo $displayTitle; ?></p>
            </div>
            <div class="header-right">
                Date Exported: <?php echo date('M d, Y'); ?><br>
                Time: <?php echo date('h:i A'); ?>
            </div>
        </div>

        <div class="table-container">
            <?php echo $reportData; ?>

            <div class="footer">
                * This document is computer generated and serves as an official system record.
            </div>
        </div>
        
    </div>

    <script>
        window.onload = function() {
            const element = document.getElementById('pdf-document');
            
            const opt = {
                margin:       [0.4, 0.4, 0.4, 0.4], 
                filename:     '<?php echo $title; ?>.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 2, useCORS: true }, 
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                document.getElementById('loader').innerHTML = "<h2 style='color: #27ae60; margin-bottom: 5px;'><i class='fas fa-check-circle'></i> Download Complete!</h2><p style='color: #7f8c8d;'>You can safely close this tab.</p>";
                
                setTimeout(() => {
                    window.close();
                }, 1500);
            });
        };
    </script>

</body>
</html>