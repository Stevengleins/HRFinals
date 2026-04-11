<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

function normalizeDate($view, $raw_val) {
    $time = time(); 
    if (!empty($raw_val)) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_val)) $time = strtotime($raw_val);
        elseif (preg_match('/^\d{4}-\d{2}$/', $raw_val)) $time = strtotime($raw_val . '-01');
        elseif (preg_match('/^\d{4}$/', $raw_val)) $time = strtotime($raw_val . '-01-01');
        elseif (strpos($raw_val, '-W') !== false) {
            $parts = explode('-W', $raw_val);
            if (count($parts) == 2) {
                $dto = new DateTime(); $dto->setISODate((int)$parts[0], (int)$parts[1]); $time = $dto->getTimestamp();
            }
        }
    }
    if (!$time) $time = time(); 
    switch ($view) {
        case 'daily': return date('Y-m-d', $time);
        case 'weekly': return date('Y-\WW', $time);
        case 'monthly': return date('Y-m', $time);
        case 'dtr': return date('Y-m', $time);
        case 'yearly': return date('Y', $time);
        default: return date('Y-m-d', $time);
    }
}

$view = $_GET['view'] ?? 'daily';
$raw_filter_val = $_GET['filter_val'] ?? '';
$emp_id = $_GET['emp_id'] ?? '';
$filter_val = normalizeDate($view, $raw_filter_val);

$whereClause = "WHERE u.role = 'Employee'";
$titleContext = "";
$t_inputType = "date"; 

if ($view === 'daily') {
    $t_inputType = 'date'; $whereClause .= " AND a.date = '$filter_val'";
    $titleContext = "Daily Attendance: " . date('M d, Y', strtotime($filter_val));
} elseif ($view === 'weekly') {
    $t_inputType = 'week'; $db_week = str_replace('-W', '', $filter_val); 
    $whereClause .= " AND YEARWEEK(a.date, 1) = '$db_week'";
    $titleContext = "Weekly Attendance (" . htmlspecialchars($filter_val) . ")"; 
} elseif ($view === 'monthly') {
    $t_inputType = 'month'; $whereClause .= " AND DATE_FORMAT(a.date, '%Y-%m') = '$filter_val'";
    $titleContext = "Monthly Attendance: " . date('F Y', strtotime($filter_val . '-01'));
} elseif ($view === 'yearly') {
    $t_inputType = 'number'; $whereClause .= " AND YEAR(a.date) = '$filter_val'";
    $titleContext = "Yearly Attendance: $filter_val";
} elseif ($view === 'dtr') {
    $t_inputType = 'month';
    if (!empty($emp_id)) {
        $whereClause .= " AND a.user_id = '$emp_id' AND DATE_FORMAT(a.date, '%Y-%m') = '$filter_val'";
        $titleContext = "Employee DTR: " . date('F Y', strtotime($filter_val . '-01'));
    } else {
        $whereClause .= " AND 1=0"; $titleContext = "Employee DTR (Select an Employee)";
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
    while ($row = $attendanceResult->fetch_assoc()) $attendanceRecords[] = $row;
}

$empQuery = $mysql->query("SELECT user_id, first_name, last_name FROM user WHERE role = 'Employee' AND status = 1 ORDER BY first_name");
$employees = [];
if ($empQuery) {
    while($e = $empQuery->fetch_assoc()) $employees[] = $e;
}

$title = "Attendance Management | WorkForcePro";
include('../includes/hr_header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<style>
    /* RESTORED LIGHT CATEGORY HEADERS */
    .table-custom thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .table-custom td { vertical-align: middle !important; border-top: 1px solid #e9ecef; font-size: 0.95rem; }
    .filter-box { background-color: #ffffff; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
</style>

<div class="content-header pb-2">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Attendance Management</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <div class="row">
        <div class="col-md-12">
            
            <div class="filter-box">
                <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-filter mr-2 text-primary"></i> Attendance Filters</h6>
                <form method="GET" action="attendance.php" id="tableFilterForm">
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
                            <input type="<?php echo $t_inputType !== 'number' ? $t_inputType : 'hidden'; ?>" name="filter_val" id="t_filterInput" class="form-control shadow-sm" value="<?php echo htmlspecialchars($filter_val); ?>" <?php echo $t_inputType !== 'number' ? 'required' : 'disabled'; ?>>
                            
                            <select name="filter_val" id="t_filterYear" class="form-control shadow-sm font-weight-bold text-dark" <?php echo $t_inputType === 'number' ? 'required' : 'style="display:none;" disabled'; ?>>
                                <?php 
                                $startYear = 2022; $endYear = date('Y') + 2; 
                                for($y = $endYear; $y >= $startYear; $y--): 
                                ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($filter_val == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
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
                </div>

                <div class="card-body p-0 bg-white">
                    <div class="p-3 table-responsive">
                        <table id="attendanceTable" class="table table-hover table-custom w-100">
                            <thead>
                                <tr>
                                    <?php if($view !== 'dtr') echo "<th>Employee Name</th>"; ?>
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
                                    }

                                    $statusBadge = '<span class="badge badge-secondary px-2 py-1">Absent</span>';
                                    if (!empty($record['time_out'])) {
                                        $statusBadge = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Present</span>';
                                    } elseif (!empty($record['time_in'])) {
                                        $statusBadge = '<span class="badge badge-warning px-2 py-1"><i class="fas fa-spinner fa-spin mr-1"></i> Checked In</span>';
                                    }

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

                                    // HR OVERTIME REVIEW LOGIC
                                    $ot_badge = '';
                                    if ($overtime_hours > 0) {
                                        $db_ot_status = $record['overtime_status'] ?? 'None';
                                        if ($db_ot_status === 'None') {
                                            $ot_badge = '<span class="text-muted text-xs font-weight-bold">Not Filed</span>';
                                        } elseif ($db_ot_status === 'Pending') {
                                            $ot_badge = '<button onclick="reviewOT('.$record['attendance_id'].')" class="btn btn-sm btn-warning d-block mt-1 text-dark font-weight-bold shadow-sm px-2" style="font-size: 0.7rem; width: 100%;"><i class="fas fa-search mr-1"></i> Review OT</button>';
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
                                            <a href="attendance.php?view=dtr&emp_id=<?php echo $record['user_id']; ?>&filter_val=<?php echo date('Y-m'); ?>" class="text-dark font-weight-bold text-decoration-none">
                                                <?php echo htmlspecialchars($full_name); ?>
                                            </a>
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

  </div>
</section>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    function updateInputType(isUserAction = false) {
        const view = document.getElementById('t_viewSelector').value;
        const input = document.getElementById('t_filterInput');
        const yearSelect = document.getElementById('t_filterYear');
        const empDiv = document.getElementById('t_employeeSelectDiv');
        
        if (empDiv) empDiv.style.display = (view === 'dtr') ? 'block' : 'none';
        
        let newType = 'date';
        if (view === 'daily') { newType = 'date'; } 
        else if (view === 'weekly') { newType = 'week'; } 
        else if (view === 'monthly' || view === 'dtr') { newType = 'month'; } 

        if (view === 'yearly') {
            input.style.display = 'none'; input.disabled = true;
            yearSelect.style.display = 'block'; yearSelect.disabled = false;
            if (isUserAction) yearSelect.value = new Date().getFullYear();
        } else {
            yearSelect.style.display = 'none'; yearSelect.disabled = true;
            input.style.display = 'block'; input.disabled = false;
            if (input.type !== newType) {
                input.type = newType;
                if (isUserAction) input.value = ''; 
            }
        }
    }

    document.getElementById('t_viewSelector').addEventListener('change', function() { updateInputType(true); });
    window.onload = function() { updateInputType(false); };

    $(document).ready(function () {
        $('#attendanceTable').DataTable({ 
            "responsive": true, "lengthChange": false, "pageLength": 10, 
            "order": [[ <?php echo ($view !== 'dtr') ? '1' : '0'; ?>, "desc" ]], 
            "language": { "search": "", "searchPlaceholder": "Search records..." } 
        });
    });

    function reviewOT(attendanceId) {
        Swal.fire({
            title: 'Process Overtime Request',
            text: 'Do you want to officially approve or reject this overtime?',
            icon: 'info',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Approve',
            denyButtonText: 'Reject',
            confirmButtonColor: '#28a745',
            denyButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `process_overtime.php?id=${attendanceId}&action=approve`;
            } else if (result.isDenied) {
                window.location.href = `process_overtime.php?id=${attendanceId}&action=reject`;
            }
        });
    }
</script>

<?php
if (isset($_SESSION['status_icon'])) {
    echo "<script>Swal.fire({icon: '{$_SESSION['status_icon']}', title: '{$_SESSION['status_title']}', text: '{$_SESSION['status_text']}', confirmButtonColor: '#212529'});</script>";
    unset($_SESSION['status_icon'], $_SESSION['status_title'], $_SESSION['status_text']);
}
?>