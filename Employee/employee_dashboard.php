<?php
session_start();

// 1. Strict Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}

// 2. THE FIX: Nuclear Cache-Busting Headers
header("Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Forces immediate expiration

require '../database.php'; 

$title = "Employee Dashboard | WorkForcePro";
require '../includes/employee_header.php';

$user_id = $_SESSION['user_id'];
$query = "
    SELECT u.*, e.shift_start, e.shift_end 
    FROM `user` u 
    LEFT JOIN `employee_details` e ON u.user_id = e.user_id 
    WHERE u.user_id = '$user_id'
";
$result = $mysql->query($query);
$employee = $result->fetch_assoc();

$myShiftStart = !empty($employee['shift_start']) ? $employee['shift_start'] : '08:00:00';
$myShiftEnd = !empty($employee['shift_end']) ? $employee['shift_end'] : '17:00:00';
$shiftDisplay = date('h:i A', strtotime($myShiftStart)) . ' - ' . date('h:i A', strtotime($myShiftEnd));

// Check Today's Attendance Status
date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
$serverTimeNow = date('Y-m-d H:i:s'); 

$checkAttendance = $mysql->query("SELECT * FROM `attendance` WHERE user_id = '$user_id' AND date = '$today'");
$attendance = $checkAttendance->fetch_assoc();
$hasClockedIn = !empty($attendance['time_in']) && $attendance['time_in'] !== '0000-00-00 00:00:00';
$hasClockedOut = !empty($attendance['time_out']) && $attendance['time_out'] !== '0000-00-00 00:00:00';

// Bulletproof JS Time Sync
$timeInMs = $hasClockedIn ? (strtotime($attendance['time_in']) * 1000) : 'null';
$timeOutMs = $hasClockedOut ? (strtotime($attendance['time_out']) * 1000) : 'null';
$serverTimeMs = strtotime($serverTimeNow) * 1000;

// ==========================================================
// DTR FILTER LOGIC
// ==========================================================
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$filterDate = $selectedYear . '-' . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT);
$displayMonthYear = date('F Y', strtotime($filterDate . '-01'));

// Fetch Filtered DTR (Ordered ASCENDING as requested)
$dtrQuery = $mysql->query("
    SELECT a.*, e.shift_start, e.shift_end 
    FROM `attendance` a
    LEFT JOIN `employee_details` e ON a.user_id = e.user_id
    WHERE a.user_id = '$user_id' AND DATE_FORMAT(a.date, '%Y-%m') = '$filterDate'
    ORDER BY a.date ASC
");
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<style>
    .tracker-card { background: #ffffff; border-left: 4px solid #000000; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-radius: 8px; }
    .tracker-card.active-shift { border-left: 4px solid #28a745; }
    .tracker-card.ended-shift { border-left: 4px solid #6c757d; }
    .live-timer { font-family: 'Courier New', Courier, monospace; font-size: 2.5rem; font-weight: 700; color: #212529; letter-spacing: 2px; }
    
    .table-custom thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .table-custom td {
        vertical-align: middle !important;
        border-top: 1px solid #f1f3f5;
        font-size: 0.95rem;
        padding: 1rem 0.75rem;
    }
</style>

<div class="content-header pb-3">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.5rem;">Employee Dashboard</h1>
        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($employee['first_name']); ?>.</p>
      </div>
      <div class="col-sm-6 text-right">
        <span id="liveDate" class="text-muted font-weight-bold mr-2 text-sm"></span>
        <span id="liveClock" class="badge badge-dark px-3 py-2 shadow-sm" style="font-size: 0.9rem;">00:00:00</span>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 tracker-card <?php echo $hasClockedOut ? 'ended-shift' : ($hasClockedIn ? 'active-shift' : ''); ?>">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-center p-4">
                    
                    <div class="text-center text-md-left mb-3 mb-md-0">
                        <h5 class="font-weight-bold text-dark mb-1">
                            <?php 
                                if($hasClockedOut) echo "Shift Ended";
                                elseif($hasClockedIn) echo "Currently Clocked In";
                                else echo "Ready to start your day?";
                            ?>
                        </h5>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">
                            <?php 
                                if($hasClockedOut) echo "You clocked out at " . date('h:i A', strtotime($attendance['time_out'])) . ".";
                                elseif($hasClockedIn) echo "Started at " . date('h:i A', strtotime($attendance['time_in'])) . ".";
                                else echo "Press the button to log your arrival time.";
                            ?>
                        </p>
                        <span class="badge bg-light text-dark border px-2 py-1 mt-2">
                            <i class="fas fa-calendar-alt text-primary mr-1"></i> Assigned Shift: <?php echo $shiftDisplay; ?>
                        </span>
                    </div>

                    <div class="text-center mx-md-4 mb-3 mb-md-0">
                        <div class="live-timer" id="live-duration">00:00:00</div>
                        <?php if($hasClockedIn && !$hasClockedOut): ?>
                            <span class="badge badge-success px-2 py-1 pulse-badge"><i class="fas fa-circle text-white text-xs mr-1"></i> Recording Time</span>
                        <?php elseif($hasClockedOut): ?>
                            <span class="text-muted text-xs font-weight-bold text-uppercase">Shift Ended</span>
                        <?php else: ?>
                            <span class="text-muted text-xs font-weight-bold text-uppercase">Elapsed Time</span>
                        <?php endif; ?>
                    </div>

                    <div class="text-center text-md-right">
                        <?php if(!$hasClockedIn): ?>
                            <button class="btn btn-dark btn-lg shadow-sm font-weight-bold px-5" style="border-radius: 6px;" onclick="timePunch('Time In')">
                                <i class="fas fa-play mr-2"></i> Clock In
                            </button>
                        <?php elseif($hasClockedIn && !$hasClockedOut): ?>
                            <button class="btn btn-danger btn-lg shadow-sm font-weight-bold px-5" style="border-radius: 6px;" onclick="timePunch('Time Out')">
                                <i class="fas fa-stop mr-2"></i> Clock Out
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg shadow-none font-weight-bold px-5" style="border-radius: 6px;" disabled>
                                <i class="fas fa-check-circle mr-2"></i> Shift Ended
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
                
                <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
                        <i class="fas fa-list-ul mr-2"></i> Daily Time Record (<?php echo $displayMonthYear; ?>)
                    </h3>
                    
                    <div class="d-flex align-items-center mt-3 mt-md-0">
                        <form method="GET" class="d-flex mr-3">
                            <select name="month" class="form-control form-control-sm mr-1 shadow-sm text-dark font-weight-bold" style="border-radius: 4px; min-width: 100px;">
                                <?php 
                                for($m=1; $m<=12; $m++) {
                                    $mStr = str_pad($m, 2, '0', STR_PAD_LEFT);
                                    $mName = date('M', mktime(0,0,0,$m,1));
                                    $sel = ($mStr === $selectedMonth) ? 'selected' : '';
                                    echo "<option value='$mStr' $sel>$mName</option>";
                                }
                                ?>
                            </select>
                            <select name="year" class="form-control form-control-sm mr-1 shadow-sm text-dark font-weight-bold" style="border-radius: 4px;">
                                <?php 
                                $currentY = date('Y');
                                for($y=$currentY; $y>=$currentY-5; $y--) {
                                    $sel = ($y == $selectedYear) ? 'selected' : '';
                                    echo "<option value='$y' $sel>$y</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-light font-weight-bold shadow-sm" style="border-radius: 4px;" title="Apply Filter">
                                <i class="fas fa-filter text-dark"></i>
                            </button>
                        </form>
                        
                        <div class="btn-group shadow-sm">
                            <a href="export_my_dtr_csv.php?month=<?php echo $filterDate; ?>" class="btn btn-sm btn-light border-0" title="Export to CSV"><i class="fas fa-file-csv text-success mr-1"></i> CSV</a>
                            <a href="export_my_dtr_pdf.php?month=<?php echo $filterDate; ?>" target="_blank" class="btn btn-sm btn-light border-0 border-left" title="Export to PDF"><i class="fas fa-file-pdf text-danger mr-1"></i> PDF</a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0 bg-white">
                    <div class="p-3 table-responsive">
                        <table id="dtrTable" class="table table-hover table-custom w-100 text-center">
                            <thead>
                                <tr>
                                    <th class="text-left">Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Regular Hrs</th>
                                    <th>Overtime Hrs</th>
                                    <th>Daily Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($dtrQuery && $dtrQuery->num_rows > 0): while($record = $dtrQuery->fetch_assoc()): 
                                    $shift_start = !empty($record['shift_start']) ? $record['shift_start'] : '08:00:00';
                                    $shift_end = !empty($record['shift_end']) ? $record['shift_end'] : '17:00:00';

                                    $tIn = ($record['time_in'] && $record['time_in'] !== '00:00:00' && $record['time_in'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($record['time_in'])) : '--:--';
                                    $tOut = ($record['time_out'] && $record['time_out'] !== '00:00:00' && $record['time_out'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($record['time_out'])) : '--:--';
                                    
                                    $regular_hours = 0.00;
                                    $overtime_hours = 0.00;
                                    
                                    if ($tIn !== '--:--' && $tOut !== '--:--') {
                                        $diff = strtotime($record['time_out']) - strtotime($record['time_in']);
                                        
                                        // PERFECT SYNC: Automatically deduct 1 hr lunch if shift is 5+ hours
                                        if ($diff >= (5 * 3600)) {
                                            $diff -= 3600; 
                                        }
                                        
                                        $total_calculated = round($diff / 3600, 2);
                                        if ($total_calculated > 8) {
                                            $regular_hours = 8.00;
                                            $overtime_hours = $total_calculated - 8.00;
                                        } else {
                                            $regular_hours = $total_calculated;
                                        }
                                    }

                                    // Lateness / Early Out Logic
                                    $statusBadge = '<span class="badge badge-secondary px-2 py-1 font-weight-normal">Absent</span>';
                                    if ($tOut !== '--:--') $statusBadge = '<span class="badge badge-success px-2 py-1 font-weight-normal"><i class="fas fa-check mr-1"></i> Present</span>';
                                    elseif ($tIn !== '--:--') $statusBadge = '<span class="badge badge-warning px-2 py-1 font-weight-normal"><i class="fas fa-spinner fa-spin mr-1"></i> Checked In</span>';

                                    if ($tIn !== '--:--') {
                                        $actual_in_time = strtotime(date('H:i:s', strtotime($record['time_in'])));
                                        $expected_in_time = strtotime($shift_start);
                                        if ($actual_in_time > $expected_in_time) {
                                            $mins_late = floor(($actual_in_time - $expected_in_time) / 60);
                                            if ($mins_late > 0) $statusBadge .= '<span class="badge badge-danger px-2 py-1 mt-1 d-block text-xs font-weight-normal">Late (' . $mins_late . 'm)</span>';
                                        }
                                    }
                                    
                                    if ($tOut !== '--:--') {
                                        $actual_out_time = strtotime(date('H:i:s', strtotime($record['time_out'])));
                                        $expected_out_time = strtotime($shift_end);
                                        if ($actual_out_time < $expected_out_time) {
                                            $mins_early = floor(($expected_out_time - $actual_out_time) / 60);
                                            if ($mins_early > 0) $statusBadge .= '<span class="badge badge-warning px-2 py-1 mt-1 d-block text-xs font-weight-normal" style="color: #856404 !important; background-color: #ffeeba !important;">Early Out (' . $mins_early . 'm)</span>';
                                        }
                                    }

                                    // OVERTIME APPLY LOGIC
                                    $ot_badge = '';
                                    if ($overtime_hours > 0) {
                                        $db_ot_status = $record['overtime_status'] ?? 'None';
                                        if ($db_ot_status === 'None') {
                                            $ot_badge = '<button onclick="applyOT('.$record['attendance_id'].')" class="btn btn-sm btn-dark d-block mx-auto mt-1 font-weight-bold" style="font-size: 0.7rem;"><i class="fas fa-paper-plane mr-1"></i> File OT</button>';
                                        } elseif ($db_ot_status === 'Pending') {
                                            $ot_badge = '<span class="badge bg-light text-dark border d-block mt-1 font-weight-normal" style="font-size: 0.7rem;"><i class="fas fa-clock"></i> Pending OT</span>';
                                        } elseif ($db_ot_status === 'Approved') {
                                            $ot_badge = '<span class="badge badge-success d-block mt-1 font-weight-normal" style="font-size: 0.7rem;"><i class="fas fa-check-double"></i> Approved OT</span>';
                                        } elseif ($db_ot_status === 'Rejected') {
                                            $ot_badge = '<span class="badge badge-danger d-block mt-1 font-weight-normal" style="font-size: 0.7rem;"><i class="fas fa-ban"></i> Rejected OT</span>';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="font-weight-bold text-dark text-left align-middle pl-4" data-sort="<?php echo $record['date']; ?>">
                                        <?php echo date('D, M d, Y', strtotime($record['date'])); ?>
                                    </td>
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
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<style>
    @keyframes pulse-animation { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    .pulse-badge { animation: pulse-animation 2s infinite; }
</style>

<script>
  $(document).ready(function () {
      $('#dtrTable').DataTable({ 
          "responsive": true, 
          "lengthChange": false, 
          "pageLength": 10, 
          "order": [[ 0, "asc" ]], // NEW: ASCENDING SORT by default
          "language": { "search": "", "searchPlaceholder": "Search my records..." } 
      });
  });

  function applyOT(attendanceId) {
      Swal.fire({
          title: 'Apply for Overtime?',
          text: 'Are you sure you want to file this overtime for HR approval?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#212529',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, submit request'
      }).then((result) => {
          if (result.isConfirmed) {
              window.location.href = `process_overtime.php?id=${attendanceId}&action=apply`;
          }
      });
  }

  function updateHeaderClock() {
      const now = new Date();
      const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
      document.getElementById('liveClock').innerText = now.toLocaleTimeString('en-US', timeOptions);
      const dateOptions = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
      document.getElementById('liveDate').innerText = now.toLocaleDateString('en-US', dateOptions);
  }
  setInterval(updateHeaderClock, 1000);
  updateHeaderClock();

  // BULLETPROOF TIMER SYNC FIX (Uses PHP Milliseconds to prevent JS NaN errors)
  const timeInMs = <?php echo $timeInMs; ?>;
  const timeOutMs = <?php echo $timeOutMs; ?>;
  let currentServerTime = <?php echo $serverTimeMs; ?>;

  function updateLiveDuration() {
      currentServerTime += 1000;
      
      // 1. If currently working (Active Shift)
      if(timeInMs !== null && timeOutMs === null) {
          let diff = Math.floor((currentServerTime - timeInMs) / 1000);
          if (diff < 0) diff = 0; 
          
          let hours = Math.floor(diff / 3600);
          let minutes = Math.floor((diff % 3600) / 60);
          let seconds = diff % 60;
          document.getElementById('live-duration').innerText = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
      } 
      // 2. If Shift is Completed (Static Duration)
      else if (timeInMs !== null && timeOutMs !== null) {
          let diff = Math.floor((timeOutMs - timeInMs) / 1000);
          if (diff < 0) diff = 0; 
          
          let hours = Math.floor(diff / 3600);
          let minutes = Math.floor((diff % 3600) / 60);
          let seconds = diff % 60;
          document.getElementById('live-duration').innerText = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
      }
  }
  setInterval(updateLiveDuration, 1000);
  updateLiveDuration();

  function timePunch(action) {
      let color = action === 'Time In' ? '#212529' : '#dc3545';
      let icon = action === 'Time In' ? 'fa-play' : 'fa-stop';
      Swal.fire({
          title: `Confirm ${action}`, text: `Are you ready to log your ${action.toLowerCase()}?`, icon: 'question',
          showCancelButton: true, confirmButtonColor: color, cancelButtonColor: '#6c757d',
          confirmButtonText: `<i class="fas ${icon} mr-1"></i> Yes, ${action}`
      }).then((result) => {
          if (result.isConfirmed) {
              let formData = new FormData(); formData.append('action', action);
              fetch('process_attendance.php', { method: 'POST', body: formData })
              .then(response => response.json())
              .then(data => {
                  if (data.status === 'success') {
                      Swal.fire({ icon: 'success', title: 'Success!', text: data.message, confirmButtonColor: '#212529' }).then(() => { location.reload(); });
                  } else { Swal.fire('Warning', data.message, 'warning'); }
              }).catch(() => { Swal.fire('Error', 'Connection failed.', 'error'); });
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