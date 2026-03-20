<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

include('../database.php'); 
include('../includes/admin_header.php');

$admin_id = $_SESSION['user_id'];

// --- 1. LIVE METRICS ---
$empCount = $mysql->query("SELECT COUNT(*) as count FROM user WHERE role = 'Employee' AND status = 1")->fetch_assoc()['count'];
$hrCount = $mysql->query("SELECT COUNT(*) as count FROM user WHERE role = 'HR Staff' AND status = 1")->fetch_assoc()['count'];
$pendingRequests = $mysql->query("SELECT COUNT(*) as count FROM employee_requests WHERE status = 'Pending'")->fetch_assoc()['count'];

// --- 2. LIVE ATTENDANCE DATA (Last 7 Days) ---
$attendanceLabels = [];
$attendanceData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('M d', strtotime("-$i days")); 
    $count = $mysql->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$date' AND time_in IS NOT NULL")->fetch_assoc()['count'];
    $attendanceLabels[] = $label;
    $attendanceData[] = $count;
}

// --- 3. LIVE PAYROLL EXPENSES (Last 6 Months) ---
$payrollLabels = [];
$payrollData = [];
for ($i = 5; $i >= 0; $i--) {
    $monthYear = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $sumQuery = $mysql->query("SELECT COALESCE(SUM(net_salary), 0) as total FROM payroll WHERE status = 'Released' AND DATE_FORMAT(date_created, '%Y-%m') = '$monthYear'");
    $payrollLabels[] = $label;
    $payrollData[] = $sumQuery->fetch_assoc()['total'];
}

// --- 4. LIVE LEAVE DISTRIBUTION (Status) ---
$leaveStatuses = ['Pending', 'Approved', 'Rejected'];
$leaveStatusData = [];
foreach($leaveStatuses as $status) {
    $count = $mysql->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = '$status'")->fetch_assoc()['count'];
    $leaveStatusData[] = $count;
}

// --- 5. RECENT REQUESTS (Limit 5) ---
$recentReqQuery = $mysql->query("
    SELECT r.request_type, r.subject, r.status, r.date_submitted, u.first_name, u.last_name 
    FROM employee_requests r 
    JOIN user u ON r.user_id = u.user_id 
    ORDER BY r.date_submitted DESC LIMIT 5
");
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 font-weight-bold text-dark">Admin Command Center</h1>
      </div>
      <div class="col-sm-6 text-right">
        <span id="liveDate" class="text-muted font-weight-bold mr-2"></span>
        <span id="liveClock" class="badge badge-dark px-3 py-2" style="font-size: 1rem;">00:00:00</span>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="row">
      <div class="col-lg-3 col-6">
        <div class="small-box bg-info shadow-sm">
          <div class="inner">
            <h3><?php echo $empCount; ?></h3>
            <p>Active Employees</p>
          </div>
          <div class="icon"><i class="fas fa-users"></i></div>
          <a href="user_management.php" class="small-box-footer">Manage Users <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-success shadow-sm">
          <div class="inner">
            <h3><?php echo $hrCount; ?></h3>
            <p>HR Staff</p>
          </div>
          <div class="icon"><i class="fas fa-user-tie"></i></div>
          <a href="user_management.php" class="small-box-footer">Manage Staff <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-warning shadow-sm">
          <div class="inner">
            <h3 class="text-white">Leaves</h3>
            <p class="text-white">Leave Directory</p>
          </div>
          <div class="icon"><i class="fas fa-calendar-alt"></i></div>
          <a href="leave_management.php" class="small-box-footer" style="color: white !important;">View Leaves <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-danger shadow-sm">
          <div class="inner">
            <h3><?php echo $pendingRequests; ?></h3>
            <p>Pending Requests</p>
          </div>
          <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
          <a href="requests.php" class="small-box-footer">View Requests <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold text-dark"><i class="fas fa-file-excel mr-2 text-success"></i> Database Excel Exports</h5>
            <div>
                <a href="export_daily_attendance.php?date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-success btn-sm mr-2 shadow-sm">
                    <i class="fas fa-clock mr-1"></i> Today's Attendance
                </a>
                <a href="export_payroll.php?type=all" class="btn btn-outline-primary btn-sm mr-2 shadow-sm">
                    <i class="fas fa-money-bill-wave mr-1"></i> Payroll Records
                </a>
                <a href="export_users.php" class="btn btn-outline-dark btn-sm shadow-sm">
                    <i class="fas fa-users mr-1"></i> Employee Directory
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold m-0"><i class="fas fa-chart-bar mr-2 text-primary"></i> Attendance (7 Days)</h3>
                    <button class="btn btn-sm btn-outline-secondary" onclick="downloadChart('attendanceChart', 'Weekly_Attendance')"><i class="fas fa-download"></i></button>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="attendanceChart" style="min-height: 350px; height: 350px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold m-0"><i class="fas fa-chart-line mr-2 text-success"></i> Payroll (6 Months)</h3>
                    <button class="btn btn-sm btn-outline-secondary" onclick="downloadChart('payrollChart', 'Payroll_Expenses')"><i class="fas fa-download"></i></button>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="payrollChart" style="min-height: 350px; height: 350px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold m-0"><i class="fas fa-chart-pie mr-2 text-warning"></i> Leave Statuses</h3>
                    <button class="btn btn-sm btn-outline-secondary" onclick="downloadChart('leaveChart', 'Leave_Distribution')"><i class="fas fa-download"></i></button>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="leaveChart" style="min-height: 350px; height: 350px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-bell mr-2 text-warning"></i> Recent System Requests</h3>
                </div>
                <div class="card-body p-0" style="min-height: 350px;">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped m-0 text-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Subject</th>
                                    <th>Type</th>
                                    <th>Date Submitted</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentReqQuery->num_rows > 0): ?>
                                    <?php while($req = $recentReqQuery->fetch_assoc()): ?>
                                        <tr>
                                            <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($req['subject']); ?></td>
                                            <td><?php echo htmlspecialchars($req['request_type']); ?></td>
                                            <td class="text-muted"><?php echo date('M d, Y h:i A', strtotime($req['date_submitted'])); ?></td>
                                            <td>
                                                <?php if($req['status'] === 'Pending'): ?>
                                                    <span class="badge badge-warning px-2 py-1">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success px-2 py-1">Reviewed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No recent requests.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-center border-top-0">
                    <a href="requests.php" class="btn btn-outline-dark btn-sm px-4">View All Requests</a>
                </div>
            </div>
        </div>
    </div>

  </div>
</section>

<?php include('../includes/footer.php');?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  function downloadChart(chartId, filename) {
      const canvas = document.getElementById(chartId);
      const link = document.createElement('a');
      link.download = filename + '.png';
      link.href = canvas.toDataURL("image/png", 1.0);
      link.click();
  }

  const customBackgroundPlugin = {
      id: 'customCanvasBackgroundColor',
      beforeDraw: (chart) => {
          const ctx = chart.canvas.getContext('2d');
          ctx.save();
          ctx.globalCompositeOperation = 'destination-over';
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(0, 0, chart.width, chart.height);
          ctx.restore();
      }
  };

  new Chart(document.getElementById('attendanceChart').getContext('2d'), {
      type: 'bar',
      data: {
          labels: <?php echo json_encode($attendanceLabels); ?>,
          datasets: [{
              label: 'Employees Checked In',
              data: <?php echo json_encode($attendanceData); ?>,
              backgroundColor: 'rgba(54, 162, 235, 0.7)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 1,
              borderRadius: 4
          }]
      },
      options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } },
      plugins: [customBackgroundPlugin]
  });

  new Chart(document.getElementById('payrollChart').getContext('2d'), {
      type: 'line',
      data: {
          labels: <?php echo json_encode($payrollLabels); ?>,
          datasets: [{
              label: 'Total Paid (₱)',
              data: <?php echo json_encode($payrollData); ?>,
              backgroundColor: 'rgba(40, 167, 69, 0.2)',
              borderColor: 'rgba(40, 167, 69, 1)',
              borderWidth: 2,
              pointBackgroundColor: '#fff',
              pointBorderColor: 'rgba(40, 167, 69, 1)',
              fill: true,
              tension: 0.3
          }]
      },
      options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } },
      plugins: [customBackgroundPlugin]
  });

  new Chart(document.getElementById('leaveChart').getContext('2d'), {
      type: 'doughnut', 
      data: {
          labels: <?php echo json_encode($leaveStatuses); ?>,
          datasets: [{
              data: <?php echo json_encode($leaveStatusData); ?>,
              backgroundColor: ['#ffc107', '#28a745', '#dc3545'],
              borderWidth: 1
          }]
      },
      options: { maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } },
      plugins: [customBackgroundPlugin]
  });

  function updateClock() {
      const now = new Date();
      const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
      document.getElementById('liveClock').innerText = now.toLocaleTimeString('en-US', timeOptions);
      const dateOptions = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
      document.getElementById('liveDate').innerText = now.toLocaleDateString('en-US', dateOptions);
  }
  setInterval(updateClock, 1000);
  updateClock();
</script>