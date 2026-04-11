<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// ==========================================
// 0. BULLETPROOF DATA NORMALIZER
// This stops HTML5 inputs from crashing/resetting
// ==========================================
function normalizeDate($view, $raw_val) {
    $time = time(); // Default to today
    
    if (!empty($raw_val)) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_val)) {
            $time = strtotime($raw_val);
        } elseif (preg_match('/^\d{4}-\d{2}$/', $raw_val)) {
            $time = strtotime($raw_val . '-01');
        } elseif (preg_match('/^\d{4}$/', $raw_val)) {
            $time = strtotime($raw_val . '-01-01');
        } elseif (strpos($raw_val, '-W') !== false) {
            $parts = explode('-W', $raw_val);
            if (count($parts) == 2) {
                $dto = new DateTime();
                $dto->setISODate((int)$parts[0], (int)$parts[1]);
                $time = $dto->getTimestamp();
            }
        }
    }

    if (!$time) $time = time(); 

    // Force perfect HTML5 formatting
    switch ($view) {
        case 'daily': return date('Y-m-d', $time);
        case 'weekly': return date('Y-\WW', $time);
        case 'monthly': return date('Y-m', $time);
        case 'dtr': return date('Y-m', $time);
        case 'yearly': return date('Y', $time);
        default: return date('Y-m-d', $time);
    }
}

// ==========================================
// 1. TABLE DYNAMIC FILTERING LOGIC
// ==========================================
$view = $_GET['view'] ?? 'daily';
$raw_filter_val = $_GET['filter_val'] ?? '';
$emp_id = $_GET['emp_id'] ?? '';

$filter_val = normalizeDate($view, $raw_filter_val);

// NEW: Force the query to ONLY look at Employees
$whereClause = "WHERE u.role = 'Employee'";
$titleContext = "";
$t_inputType = "date"; 

if ($view === 'daily') {
    $t_inputType = 'date';
    $whereClause .= " AND a.date = '$filter_val'";
    $titleContext = "Daily Attendance: " . date('M d, Y', strtotime($filter_val));
} elseif ($view === 'weekly') {
    $t_inputType = 'week';
    $db_week = str_replace('-W', '', $filter_val); 
    $whereClause .= " AND YEARWEEK(a.date, 1) = '$db_week'";
    $titleContext = "Weekly Attendance (" . htmlspecialchars($filter_val) . ")"; 
} elseif ($view === 'monthly') {
    $t_inputType = 'month';
    $whereClause .= " AND DATE_FORMAT(a.date, '%Y-%m') = '$filter_val'";
    $titleContext = "Monthly Attendance: " . date('F Y', strtotime($filter_val . '-01'));
} elseif ($view === 'yearly') {
    $t_inputType = 'number';
    $whereClause .= " AND YEAR(a.date) = '$filter_val'";
    $titleContext = "Yearly Attendance: $filter_val";
} elseif ($view === 'dtr') {
    $t_inputType = 'month';
    if (!empty($emp_id)) {
        $whereClause .= " AND a.user_id = '$emp_id' AND DATE_FORMAT(a.date, '%Y-%m') = '$filter_val'";
        $titleContext = "Employee DTR: " . date('F Y', strtotime($filter_val . '-01'));
    } else {
        $whereClause .= " AND 1=0"; // Prevents loading all data if no employee is selected
        $titleContext = "Employee DTR (Select an Employee)";
    }
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

$attendanceResult = $mysql->query($queryStr);
$attendanceRecords = [];
if ($attendanceResult && $attendanceResult->num_rows > 0) {
    while ($row = $attendanceResult->fetch_assoc()) {
        $attendanceRecords[] = $row;
    }
}

// ==========================================
// 2. CHART DYNAMIC FILTERING LOGIC 
// ==========================================
$c_view = $_GET['c_view'] ?? 'weekly'; 
$raw_c_val = $_GET['c_val'] ?? ''; 
$c_emp = $_GET['c_emp'] ?? '';

$c_val = normalizeDate($c_view, $raw_c_val);

// NEW: Force the chart to ONLY look at Employees
$c_whereClause = "WHERE u.role = 'Employee'";
$c_titleContext = "";
$chartYAxisLabel = "Employees Present";
$c_inputType = "week";

if ($c_view === 'daily') {
    $c_inputType = 'date';
    $c_whereClause .= " AND a.date = '$c_val'";
    $c_titleContext = "Daily Role Distribution: " . date('M d, Y', strtotime($c_val));
} elseif ($c_view === 'weekly') {
    $c_inputType = 'week';
    $db_c_week = str_replace('-W', '', $c_val);
    $c_whereClause .= " AND YEARWEEK(a.date, 1) = '$db_c_week'";
    $c_titleContext = "Weekly Attendance (" . htmlspecialchars($c_val) . ")";
} elseif ($c_view === 'monthly') {
    $c_inputType = 'month';
    $c_whereClause .= " AND DATE_FORMAT(a.date, '%Y-%m') = '$c_val'";
    $c_titleContext = "Monthly Attendance: " . date('F Y', strtotime($c_val . '-01'));
} elseif ($c_view === 'yearly') {
    $c_inputType = 'number';
    $c_whereClause .= " AND YEAR(a.date) = '$c_val'";
    $c_titleContext = "Yearly Attendance: $c_val";
} elseif ($c_view === 'dtr') {
    $c_inputType = 'month';
    if (!empty($c_emp)) {
        $c_whereClause .= " AND a.user_id = '$c_emp' AND DATE_FORMAT(a.date, '%Y-%m') = '$c_val'";
        $c_titleContext = "Employee Hours Logged: " . date('F Y', strtotime($c_val . '-01'));
        $chartYAxisLabel = "Total Hours";
    } else {
        $c_whereClause .= " AND 1=0";
        $c_titleContext = "Employee Hours Logged (Select an Employee)";
    }
}

$c_queryStr = "SELECT a.date, a.time_in, a.time_out, a.total_hours, u.role FROM attendance a JOIN user u ON a.user_id = u.user_id $c_whereClause ORDER BY a.date ASC";
$c_result = $mysql->query($c_queryStr);

$chartLabels = [];
$chartData = [];
$tempData = [];

if ($c_result && $c_result->num_rows > 0) {
    while ($row = $c_result->fetch_assoc()) {
        if ($c_view === 'yearly') {
            $label = date('M Y', strtotime($row['date']));
        } elseif ($c_view === 'daily') {
            $label = $row['role']; 
        } else {
            $label = date('M d', strtotime($row['date']));
        }

        if (!isset($tempData[$label])) {
            $tempData[$label] = 0;
        }
        
        if (!empty($row['time_in'])) {
            if ($c_view === 'dtr') {
                $hours = $row['total_hours'] ?? 0;
                if ($hours == 0 && !empty($row['time_out'])) {
                    $hours = round((strtotime($row['time_out']) - strtotime($row['time_in'])) / 3600, 2);
                }
                $tempData[$label] += $hours;
            } else {
                $tempData[$label]++;
            }
        }
    }
    foreach ($tempData as $lbl => $val) {
        $chartLabels[] = $lbl;
        $chartData[] = $val;
    }
}

// NEW: Force dropdown to only load Employees
$empQuery = $mysql->query("SELECT user_id, first_name, last_name FROM user WHERE role = 'Employee' AND status = 1 ORDER BY first_name");
$employees = [];
while($e = $empQuery->fetch_assoc()) {
    $employees[] = $e;
}

$title = "Attendance Reports | WorkForcePro";
include('../includes/admin_header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<style>
    .table-custom thead th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; color: #495057; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
    .table-custom td { vertical-align: middle !important; border-top: 1px solid #e9ecef; font-size: 0.95rem; }
    .filter-box { background-color: #ffffff; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
</style>

<div class="content-header pb-2">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Attendance Reports</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <div class="row">
        <div class="col-md-12">
            <div class="filter-box">
                <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-table mr-2 text-primary"></i> Table Data Filters </h6>
                <form method="GET" action="attendance.php" id="tableFilterForm">
                    <input type="hidden" name="c_view" value="<?php echo htmlspecialchars($c_view); ?>">
                    <input type="hidden" name="c_val" value="<?php echo htmlspecialchars($c_val); ?>">
                    <input type="hidden" name="c_emp" value="<?php echo htmlspecialchars($c_emp); ?>">

                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="text-muted text-xs font-weight-bold text-uppercase">Table View Type</label>
                            <select name="view" id="t_viewSelector" class="form-control shadow-sm font-weight-bold">
                                <option value="daily" <?php echo $view == 'daily' ? 'selected' : ''; ?>>Daily View</option>
                                <option value="weekly" <?php echo $view == 'weekly' ? 'selected' : ''; ?>>Weekly View</option>
                                <option value="monthly" <?php echo $view == 'monthly' ? 'selected' : ''; ?>>Monthly View</option>
                                <option value="yearly" <?php echo $view == 'yearly' ? 'selected' : ''; ?>>Yearly View</option>
                                <option value="dtr" <?php echo $view == 'dtr' ? 'selected' : ''; ?>>Specific Employee DTR</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3 mb-md-0" id="t_employeeSelectDiv" style="<?php echo $view == 'dtr' ? 'display:block;' : 'display:none;'; ?>">
                            <label class="text-muted text-xs font-weight-bold text-uppercase">Select Employee</label>
                            <select name="emp_id" class="form-control shadow-sm">
                                <?php foreach($employees as $emp): ?>
                                    <option value="<?php echo $emp['user_id']; ?>" <?php echo $emp_id == $emp['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="text-muted text-xs font-weight-bold text-uppercase">Period</label>
                            <input type="<?php echo $t_inputType; ?>" name="filter_val" id="t_filterInput" class="form-control shadow-sm" value="<?php echo htmlspecialchars($filter_val); ?>" <?php if($t_inputType == 'number') echo 'placeholder="YYYY"'; ?> required>
                        </div>
                        
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-dark shadow-sm px-4 w-100 font-weight-bold"><i class="fas fa-sync-alt mr-1"></i> Update Table</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card shadow-sm border-0 mb-5" style="border-radius: 8px; overflow: hidden;">
                <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-clock mr-2"></i> <?php echo $titleContext; ?></h6>
                    
                    <div class="btn-group shadow-sm">
                        <a href="export_attendance_csv.php?view=<?php echo $view; ?>&filter_val=<?php echo htmlspecialchars($filter_val); ?>&emp_id=<?php echo $emp_id; ?>" class="btn btn-sm btn-light border-0" title="Export CSV"><i class="fas fa-file-csv text-success mr-1"></i> CSV</a>
                        <a href="export_pdf.php?type=dynamic_attendance&view=<?php echo $view; ?>&filter_val=<?php echo htmlspecialchars($filter_val); ?>&emp_id=<?php echo $emp_id; ?>" target="_blank" class="btn btn-sm btn-light border-left border-0" title="Export PDF"><i class="fas fa-file-pdf text-danger mr-1"></i> PDF</a>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="p-3 table-responsive">
                        <table id="attendanceTable" class="table table-hover table-custom w-100">
                            <thead>
                                <tr>
                                    <?php if($view !== 'dtr') echo "<th>Employee</th>"; ?>
                                    <th>Date</th>
                                    <th>Shift</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Reg. Hrs</th>
                                    <th>OT Hrs</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceRecords as $record): 
                                    $f_name = !empty($record['e_first']) ? $record['e_first'] : $record['u_first'];
                                    $m_name = !empty($record['middle_name']) ? $record['middle_name'] : '';
                                    $l_name = !empty($record['e_last']) ? $record['e_last'] : $record['u_last'];
                                    $s_name = !empty($record['suffix']) ? $record['suffix'] : '';
                                    $full_name = trim("$f_name $m_name $l_name $s_name");

                                    $shift_start = !empty($record['shift_start']) ? $record['shift_start'] : '08:00:00';
                                    $shift_end = !empty($record['shift_end']) ? $record['shift_end'] : '17:00:00';
                                    $shiftDisplay = date('h:i A', strtotime($shift_start)) . ' - ' . date('h:i A', strtotime($shift_end));

                                    $tIn = $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '--:--';
                                    $tOut = $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '--:--';
                                    
                                    $regular_hours = 0.00;
                                    $overtime_hours = 0.00;
                                    
                                    if ($record['time_in'] && $record['time_out']) {
                                        $diff = strtotime($record['time_out']) - strtotime($record['time_in']);
                                        $total_calculated = round($diff / 3600, 2);
                                        if ($total_calculated > 8) {
                                            $regular_hours = 8.00;
                                            $overtime_hours = $total_calculated - 8.00;
                                        } else {
                                            $regular_hours = $total_calculated;
                                        }
                                    } else {
                                        $regular_hours = isset($record['regular_hours']) ? $record['regular_hours'] : 0;
                                        $overtime_hours = isset($record['overtime_hours']) ? $record['overtime_hours'] : 0;
                                    }

                                    // STATUS BADGE (Arrival/Departure Status)
                                    $statusBadge = '<span class="badge badge-secondary px-2 py-1">Absent</span>';
                                    if (!empty($record['time_out'])) {
                                        $statusBadge = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Completed</span>';
                                    } elseif (!empty($record['time_in'])) {
                                        $statusBadge = '<span class="badge badge-warning px-2 py-1"><i class="fas fa-spinner fa-spin mr-1"></i> Active</span>';
                                    }

                                    // Lateness / Early Out Modifiers
                                    if (!empty($record['time_in'])) {
                                        $actual_in_time = strtotime(date('H:i:s', strtotime($record['time_in'])));
                                        $expected_in_time = strtotime($shift_start);
                                        if ($actual_in_time > $expected_in_time) {
                                            $mins_late = floor(($actual_in_time - $expected_in_time) / 60);
                                            if ($mins_late > 0) $statusBadge .= '<span class="badge badge-danger px-2 py-1 mt-1 d-block text-xs">Late (' . $mins_late . 'm)</span>';
                                        }
                                    }

                                    if (!empty($record['time_out'])) {
                                        $actual_out_time = strtotime(date('H:i:s', strtotime($record['time_out'])));
                                        $expected_out_time = strtotime($shift_end);
                                        if ($actual_out_time < $expected_out_time) {
                                            $mins_early = floor(($expected_out_time - $actual_out_time) / 60);
                                            if ($mins_early > 0) {
                                                $statusBadge .= '<span class="badge badge-warning px-2 py-1 mt-1 d-block text-xs" style="color: #856404 !important; background-color: #ffeeba !important; border: 1px solid #ffeeba;">Early Out (' . $mins_early . 'm)</span>';
                                            }
                                        }
                                    }

                                    // OVERTIME STATUS BADGE
                                    $ot_badge = '';
                                    if ($overtime_hours > 0) {
                                        $db_ot_status = $record['overtime_status'] ?? 'None';
                                        if ($db_ot_status === 'None') $db_ot_status = 'Pending'; // Default logic if untouched
                                        
                                        if ($db_ot_status === 'Pending') {
                                            $ot_badge = '<span class="badge badge-warning d-block mt-1" style="font-size: 0.7rem;"><i class="fas fa-clock"></i> Pending OT</span>';
                                        } elseif ($db_ot_status === 'Approved') {
                                            $ot_badge = '<span class="badge badge-success d-block mt-1" style="font-size: 0.7rem;"><i class="fas fa-check-double"></i> Approved OT</span>';
                                        } elseif ($db_ot_status === 'Rejected') {
                                            $ot_badge = '<span class="badge badge-danger d-block mt-1" style="font-size: 0.7rem;"><i class="fas fa-ban"></i> Rejected OT</span>';
                                        }
                                    }
                                ?>
                                <tr>
                                    <?php if($view !== 'dtr'): ?>
                                        <td class="align-middle">
                                            <a href="attendance.php?view=dtr&emp_id=<?php echo $record['user_id']; ?>&filter_val=<?php echo date('Y-m'); ?>&c_view=<?php echo $c_view; ?>&c_val=<?php echo $c_val; ?>" class="text-dark font-weight-bold text-decoration-none">
                                                <?php echo htmlspecialchars($full_name); ?>
                                            </a>
                                            <small class="d-block text-muted"><?php echo $record['role']; ?></small>
                                        </td>
                                    <?php endif; ?>
                                    <td class="font-weight-bold text-dark align-middle"><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    
                                    <td class="text-muted text-sm align-middle"><?php echo $shiftDisplay; ?></td>
                                    <td class="text-primary font-weight-bold align-middle"><?php echo $tIn; ?></td>
                                    <td class="text-danger font-weight-bold align-middle"><?php echo $tOut; ?></td>
                                    <td class="font-weight-bold text-dark align-middle"><?php echo number_format($regular_hours, 2); ?>h</td>
                                    
                                    <td class="align-middle">
                                        <?php if($overtime_hours > 0): ?>
                                            <span class="text-danger font-weight-bold">+<?php echo number_format($overtime_hours, 2); ?>h</span>
                                            <?php echo $ot_badge; ?>
                                        <?php else: ?>
                                            <span class="text-muted">0.00h</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="align-middle"><?php echo $statusBadge; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="filter-box">
                <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-chart-area mr-2 text-info"></i> Attendance Report Chart </h6>
                <form method="GET" action="attendance.php" id="chartFilterForm">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                    <input type="hidden" name="filter_val" value="<?php echo htmlspecialchars($filter_val); ?>">
                    <input type="hidden" name="emp_id" value="<?php echo htmlspecialchars($emp_id); ?>">

                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="text-muted text-xs font-weight-bold text-uppercase">Chart View Type</label>
                            <select name="c_view" id="c_viewSelector" class="form-control shadow-sm font-weight-bold">
                                <option value="daily" <?php echo $c_view == 'daily' ? 'selected' : ''; ?>>Daily View</option>
                                <option value="weekly" <?php echo $c_view == 'weekly' ? 'selected' : ''; ?>>Weekly View</option>
                                <option value="monthly" <?php echo $c_view == 'monthly' ? 'selected' : ''; ?>>Monthly View</option>
                                <option value="yearly" <?php echo $c_view == 'yearly' ? 'selected' : ''; ?>>Yearly View</option>
                                <option value="dtr" <?php echo $c_view == 'dtr' ? 'selected' : ''; ?>>Specific Employee DTR</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3 mb-md-0" id="c_employeeSelectDiv" style="<?php echo $c_view == 'dtr' ? 'display:block;' : 'display:none;'; ?>">
                            <label class="text-muted text-xs font-weight-bold text-uppercase">Select Employee</label>
                            <select name="c_emp" class="form-control shadow-sm">
                                <?php foreach($employees as $emp): ?>
                                    <option value="<?php echo $emp['user_id']; ?>" <?php echo $c_emp == $emp['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="text-muted text-xs font-weight-bold text-uppercase">Period</label>
                            <input type="<?php echo $c_inputType; ?>" name="c_val" id="c_filterInput" class="form-control shadow-sm" value="<?php echo htmlspecialchars($c_val); ?>" <?php if($c_inputType == 'number') echo 'placeholder="YYYY"'; ?> required>
                        </div>
                        
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-dark shadow-sm px-4 w-100 font-weight-bold"><i class="fas fa-sync-alt mr-1"></i> Update Chart</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card shadow-sm border-0 mb-4" style="border-radius: 8px; overflow: hidden;">
                <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-bar mr-2"></i> <?php echo $c_titleContext; ?></h6>
                    <div class="btn-group shadow-sm">
                        <button class="btn btn-sm btn-light border-0" onclick="updateChartType('bar')" title="Bar Chart"><i class="fas fa-chart-bar text-primary"></i></button>
                        <button class="btn btn-sm btn-light border-left border-right" onclick="updateChartType('line')" title="Line Chart"><i class="fas fa-chart-line text-success"></i></button>
                        <button class="btn btn-sm btn-light border-0" onclick="updateChartType('doughnut')" title="Pie Chart"><i class="fas fa-chart-pie text-warning"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="dynamicChart" style="min-height: 350px; height: 350px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

  </div>
</section>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // JS Logic to handle input type changing dynamically without confusing the browser
    function updateInputType(prefix, isUserAction = false) {
        const view = document.getElementById(prefix + '_viewSelector').value;
        const input = document.getElementById(prefix + '_filterInput');
        const empDiv = document.getElementById(prefix + '_employeeSelectDiv');
        
        if (empDiv) empDiv.style.display = (view === 'dtr') ? 'block' : 'none';
        
        const oldType = input.type;
        let newType = 'date';

        if (view === 'daily') { newType = 'date'; } 
        else if (view === 'weekly') { newType = 'week'; } 
        else if (view === 'monthly' || view === 'dtr') { newType = 'month'; } 
        else if (view === 'yearly') { newType = 'number'; input.placeholder = "YYYY"; }

        if (oldType !== newType) {
            input.type = newType;
            if (isUserAction) {
                input.value = ''; // Clears the input so the user can type the new correct format
            }
        }
    }

    // Bind event listeners safely
    document.getElementById('t_viewSelector').addEventListener('change', function() { updateInputType('t', true); });
    document.getElementById('c_viewSelector').addEventListener('change', function() { updateInputType('c', true); });

    // CRITICAL: Ensure JS runs on page load so dropdowns align with URL parameters
    window.onload = function() {
        updateInputType('t', false);
        updateInputType('c', false);
    };

    $(document).ready(function () {
        $('#attendanceTable').DataTable({ 
            "responsive": true, 
            "lengthChange": false, 
            "pageLength": 10, 
            "order": [[ 0, "desc" ]], 
            "language": { "search": "", "searchPlaceholder": "Search records..." } 
        });
    });

    const rawLabels = <?php echo json_encode(array_values($chartLabels)); ?>;
    const rawData = <?php echo json_encode(array_values($chartData)); ?>;
    const yAxisLabelText = "<?php echo $chartYAxisLabel; ?>";
    let currentChart;
    const ctx = document.getElementById('dynamicChart').getContext('2d');

    function updateChartType(type) {
        if (currentChart) currentChart.destroy();
        let bgColor = '#4e73df';
        if(type === 'doughnut') bgColor = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
        currentChart = new Chart(ctx, { 
            type: type, 
            data: { 
                labels: rawLabels, 
                datasets: [{ 
                    label: yAxisLabelText, 
                    data: rawData, 
                    backgroundColor: bgColor, 
                    borderColor: type === 'line' ? '#4e73df' : '#fff', 
                    borderWidth: 2, 
                    fill: type === 'line' ? false : true, 
                    tension: 0.3 
                }] 
            }, 
            options: { 
                maintainAspectRatio: false, 
                plugins: { legend: { display: type === 'doughnut' } }, 
                scales: type !== 'doughnut' ? { y: { beginAtZero: true, title: { display: true, text: yAxisLabelText }, ticks: { stepSize: 1 } } } : {} 
            } 
        });
    }
    updateChartType('bar');
</script>