<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// THE FIX: Clear Browser Cache for Secure Pages
header("Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Forces browser to treat the page as expired immediately

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

<style>
    .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
    .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
    .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
    .border-left-danger { border-left: 0.25rem solid #e74a3b !important; }
    .text-gray-300 { color: #dddfeb !important; }
    .text-xs { font-size: .7rem; }
    .font-weight-bold { font-weight: 700 !important; }
</style>

<div class="content-header pb-1">
  <div class="container-fluid">
    <div class="row mb-3 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.5rem;">Admin Dashboard</h1>
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
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-primary h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Employees</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $empCount; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-users fa-2x text-gray-300"></i>
              </div>
            </div>
            <div class="mt-2 text-right"><a href="user_management.php" class="text-xs text-muted text-decoration-none">View Directory <i class="fas fa-arrow-right"></i></a></div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-success h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">HR Staff</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $hrCount; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-user-tie fa-2x text-gray-300"></i>
              </div>
            </div>
            <div class="mt-2 text-right"><a href="user_management.php" class="text-xs text-muted text-decoration-none">Manage Staff <i class="fas fa-arrow-right"></i></a></div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-warning h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Leaves</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $leaveStatusData[0]; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
              </div>
            </div>
            <div class="mt-2 text-right"><a href="leave_management.php" class="text-xs text-muted text-decoration-none">Review Leaves <i class="fas fa-arrow-right"></i></a></div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-danger h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Pending Requests</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $pendingRequests; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
              </div>
            </div>
            <div class="mt-2 text-right"><a href="requests.php" class="text-xs text-muted text-decoration-none">Handle Requests <i class="fas fa-arrow-right"></i></a></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
        
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 8px; overflow: hidden;">
                <div class="card-header bg-dark text-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom-0">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-bar mr-2"></i> Attendance Overview (7 Days)</h6>
                    <div class="btn-group shadow-sm">
                        <a href="export_daily_attendance.php?date=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-light" title="Export CSV Data"><i class="fas fa-file-csv text-success"></i></a>
                        <a href="export_pdf.php?type=attendance" target="_blank" class="btn btn-sm btn-light border-left border-right" title="Print/Export PDF Data"><i class="fas fa-file-pdf text-danger"></i></a>
                        <button class="btn btn-sm btn-light" onclick="downloadChart('attendanceChart', 'Weekly_Attendance')" title="Download Chart Image"><i class="fas fa-image text-primary"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="attendanceChart" style="min-height: 300px; height: 300px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 8px; overflow: hidden;">
                <div class="card-header bg-dark text-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom-0">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-line mr-2"></i> Payroll Expenses (6 Months)</h6>
                    <div class="btn-group shadow-sm">
                        <a href="export_payroll.php?type=all" class="btn btn-sm btn-light" title="Export CSV Data"><i class="fas fa-file-csv text-success"></i></a>
                        <a href="export_pdf.php?type=payroll" target="_blank" class="btn btn-sm btn-light border-left border-right" title="Print/Export PDF Data"><i class="fas fa-file-pdf text-danger"></i></a>
                        <button class="btn btn-sm btn-light" onclick="downloadChart('payrollChart', 'Payroll_Expenses')" title="Download Chart Image"><i class="fas fa-image text-primary"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="payrollChart" style="min-height: 300px; height: 300px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 8px; overflow: hidden;">
                <div class="card-header bg-dark text-white py-3 d-flex flex-row align-items-center justify-content-between border-bottom-0">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-pie mr-2"></i> Leave Distribution</h6>
                    <div class="btn-group shadow-sm">
                        <a href="export_leaves.php" class="btn btn-sm btn-light" title="Export Excel Data"><i class="fas fa-file-excel text-success"></i></a>
                        <a href="export_pdf.php?type=leaves" target="_blank" class="btn btn-sm btn-light border-left border-right" title="Print/Export PDF Data"><i class="fas fa-file-pdf text-danger"></i></a>
                        <button class="btn btn-sm btn-light" onclick="downloadChart('leaveChart', 'Leave_Distribution')" title="Download Chart Image"><i class="fas fa-image text-primary"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="leaveChart" style="min-height: 300px; height: 300px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 8px; overflow: hidden;">
                <div class="card-header bg-dark text-white py-3 border-bottom-0">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-inbox mr-2"></i> Recent System Requests</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0 text-sm">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="border-top-0">Employee</th>
                                    <th class="border-top-0">Subject</th>
                                    <th class="border-top-0">Type</th>
                                    <th class="border-top-0">Date Submitted</th>
                                    <th class="border-top-0">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentReqQuery->num_rows > 0): ?>
                                    <?php while($req = $recentReqQuery->fetch_assoc()): ?>
                                        <tr>
                                            <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($req['subject']); ?></td>
                                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($req['request_type']); ?></span></td>
                                            <td class="text-muted"><?php echo date('M d, Y h:i A', strtotime($req['date_submitted'])); ?></td>
                                            <td>
                                                <?php if($req['status'] === 'Pending'): ?>
                                                    <span class="badge badge-warning px-2 py-1"><i class="fas fa-clock mr-1"></i> Pending</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Reviewed</span>
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
                <div class="card-footer bg-white text-center border-top-0 py-3">
                    <a href="requests.php" class="text-primary font-weight-bold text-decoration-none" style="font-size: 0.9rem;">View All Requests <i class="fas fa-angle-right ml-1"></i></a>
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

  // Formalized Chart Colors
  new Chart(document.getElementById('attendanceChart').getContext('2d'), {
      type: 'bar',
      data: {
          labels: <?php echo json_encode($attendanceLabels); ?>,
          datasets: [{
              label: 'Employees Checked In',
              data: <?php echo json_encode($attendanceData); ?>,
              backgroundColor: '#4e73df', // Corporate Blue
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
              backgroundColor: 'rgba(28, 200, 138, 0.1)', // Subtle Corporate Green
              borderColor: '#1cc88a',
              borderWidth: 3,
              pointBackgroundColor: '#fff',
              pointBorderColor: '#1cc88a',
              pointRadius: 4,
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
              backgroundColor: ['#f6c23e', '#1cc88a', '#e74a3b'], // Match Border Colors
              borderWidth: 0,
              hoverOffset: 5
          }]
      },
      options: { maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'bottom' } } },
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