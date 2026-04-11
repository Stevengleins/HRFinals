<?php
session_start();
require '../database.php';

// Strict Security Check: ONLY HR Staff can access this dashboard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// 1. Get Total Employees (SAFE QUERY WITH BACKTICKS)
$empQuery = $mysql->query("SELECT COUNT(user_id) AS total_emp FROM `user` WHERE role = 'Employee' AND status = 1");
$totalEmployees = $empQuery ? $empQuery->fetch_assoc()['total_emp'] : 0;

// 2. Get Pending Leave Requests
$leaveQuery = $mysql->query("SELECT COUNT(leave_id) AS total_leaves FROM `leave_requests` WHERE status = 'Pending'");
$pendingLeaves = $leaveQuery ? $leaveQuery->fetch_assoc()['total_leaves'] : 0;

// 3. Get Present Today (Attendance)
$attQuery = $mysql->query("SELECT COUNT(DISTINCT user_id) AS present_today FROM `attendance` WHERE date = '$today' AND time_in IS NOT NULL");
$presentToday = $attQuery ? $attQuery->fetch_assoc()['present_today'] : 0;

// 4. Get Pending Payroll Count
$payrollQuery = $mysql->query("SELECT COUNT(payroll_id) AS pending_pay FROM `payroll` WHERE status = 'Pending'");
$pendingPayroll = $payrollQuery ? $payrollQuery->fetch_assoc()['pending_pay'] : 0;

// 5. Fetch the 5 most recent pending leave requests for the quick-view table
$recentLeavesQuery = $mysql->query("
    SELECT lr.*, u.first_name, u.last_name 
    FROM `leave_requests` lr 
    JOIN `user` u ON lr.user_id = u.user_id 
    WHERE lr.status = 'Pending' 
    ORDER BY lr.date_applied DESC 
    LIMIT 5
");

$title = "HR Dashboard | WorkForcePro";
include '../includes/hr_header.php';
?>

<style>
    /* Premium Admin-Style Metric Cards */
    .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
    .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
    .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
    .border-left-info { border-left: 0.25rem solid #36b9cc !important; }
    .text-gray-300 { color: #dddfeb !important; }
    .text-xs { font-size: .7rem; }
    .font-weight-bold { font-weight: 700 !important; }
</style>

<div class="content-header pb-1">
  <div class="container-fluid">
    <div class="row mb-3 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.5rem;">HR Dashboard</h1>
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
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Employees</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $totalEmployees; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-users fa-2x text-gray-300"></i>
              </div>
            </div>
            <div class="mt-2 text-right">
                <span class="text-xs text-muted">Active in System</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-success h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present Today</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $presentToday; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-user-check fa-2x text-gray-300"></i>
              </div>
            </div>
            <div class="mt-2 text-right">
                <a href="attendance.php" class="text-xs text-muted text-decoration-none">View Attendance <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-warning h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Leaves</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $pendingLeaves; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-envelope-open-text fa-2x text-gray-300"></i>
              </div>
            </div>
            <div class="mt-2 text-right">
                <a href="leave_management.php" class="text-xs text-muted text-decoration-none">Review Leaves <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-info h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pending Payroll</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $pendingPayroll; ?></div>
              </div>
              <div class="col-auto">
                <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
              </div>
            </div>
            <div class="mt-2 text-right">
                <a href="payrollhr.php" class="text-xs text-muted text-decoration-none">Process Payroll <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-3">
      
      <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 8px; overflow: hidden;">
          <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
                <i class="fas fa-bell mr-2"></i> Recent Leave Requests
            </h3>
            <a href="leave_management.php" class="btn btn-sm btn-outline-light font-weight-bold shadow-sm" style="border-radius: 6px;">View All</a>
          </div>
          
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle m-0 text-sm">
                <thead class="bg-light text-muted">
                  <tr>
                    <th class="border-top-0 pl-4">Employee</th>
                    <th class="border-top-0">Type</th>
                    <th class="border-top-0">Date Applied</th>
                    <th class="border-top-0">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if($recentLeavesQuery && $recentLeavesQuery->num_rows > 0): while($row = $recentLeavesQuery->fetch_assoc()): ?>
                  <tr>
                    <td class="font-weight-bold text-dark pl-4">
                        <i class="fas fa-user-circle text-muted mr-2" style="font-size: 1.2rem;"></i> 
                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border px-2 py-1"><?php echo htmlspecialchars($row['leave_type']); ?></span>
                    </td>
                    <td class="text-muted"><?php echo date('M d, Y', strtotime($row['date_applied'])); ?></td>
                    <td>
                        <a href="leave_management.php" class="btn btn-sm btn-light border text-primary shadow-sm font-weight-bold" style="border-radius: 4px;">Review</a>
                    </td>
                  </tr>
                  <?php endwhile; else: ?>
                  <tr>
                      <td colspan="4" class="text-center py-5 text-muted font-italic">No pending leave requests at the moment.</td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 8px; overflow: hidden;">
          <div class="card-header bg-dark text-white py-3 border-bottom-0">
            <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
                <i class="fas fa-bolt mr-2 text-warning"></i> Quick Actions
            </h3>
          </div>
          <div class="card-body bg-light d-flex flex-column justify-content-center">
            
            <a href="attendance.php" class="btn btn-white border border-secondary btn-block mb-3 shadow-sm py-3 text-left pl-4" style="border-radius: 6px; font-weight: 600; color: #495057; transition: all 0.2s;">
                <i class="fas fa-clipboard-check mr-3 text-primary" style="font-size: 1.2rem;"></i> Manage Attendance
            </a>
            
            <a href="leave_management.php" class="btn btn-white border border-secondary btn-block mb-3 shadow-sm py-3 text-left pl-4" style="border-radius: 6px; font-weight: 600; color: #495057; transition: all 0.2s;">
                <i class="fas fa-calendar-alt mr-3 text-warning" style="font-size: 1.2rem;"></i> Process Leaves
            </a>
            
            <a href="payrollhr.php" class="btn btn-white border border-secondary btn-block shadow-sm py-3 text-left pl-4" style="border-radius: 6px; font-weight: 600; color: #495057; transition: all 0.2s;">
                <i class="fas fa-money-check-alt mr-3 text-success" style="font-size: 1.2rem;"></i> Process Payroll
            </a>
            
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
    // Premium Admin-style clock update function
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

<?php include '../includes/footer.php'; ?>