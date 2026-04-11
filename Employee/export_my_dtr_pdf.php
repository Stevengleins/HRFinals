<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    exit("Unauthorized Access");
}

$user_id = $_SESSION['user_id'];
$month = $_GET['month'] ?? date('Y-m');

// Fetch Employee Details safely (no 'department' column to prevent crash)
$uQuery = $mysql->query("
    SELECT u.first_name, u.last_name, u.email, u.role 
    FROM `user` u 
    WHERE u.user_id = '$user_id'
");
$user = $uQuery->fetch_assoc();
$fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);

$title = "My_DTR_" . $month;
$displayTitle = "Official DTR: " . $fullName . " (" . date('F Y', strtotime($month . '-01')) . ")";
$reportData = '';

// Fetch DTR Data
$query = $mysql->query("
    SELECT a.*, e.shift_start, e.shift_end 
    FROM `attendance` a
    LEFT JOIN `employee_details` e ON a.user_id = e.user_id
    WHERE a.user_id = '$user_id' AND DATE_FORMAT(a.date, '%Y-%m') = '$month'
    ORDER BY a.date ASC
");

$reportData .= "<table><thead><tr><th>Date</th><th>Shift</th><th>Time In</th><th>Time Out</th><th>Reg. Hrs</th><th>OT Hrs</th><th>Status</th></tr></thead><tbody>";

if ($query && $query->num_rows > 0) {
    while ($row = $query->fetch_assoc()) {
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

        $reportData .= "<tr>
            <td>{$date}</td>
            <td>{$shiftDisplay}</td>
            <td>{$tIn}</td>
            <td>{$tOut}</td>
            <td>" . number_format($regular_hours, 2) . "</td>
            <td>" . number_format($overtime_hours, 2) . "</td>
            <td>{$status}</td>
        </tr>";
    }
} else {
    $reportData .= "<tr><td colspan='7' style='text-align:center; color: #7f8c8d;'>No attendance records found for this month.</td></tr>";
}
$reportData .= "</tbody></table>";
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
                    <img src="../logo.png" alt="WORKFORCEPRO" class="brand-image" onerror="this.style.display='none'" />
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