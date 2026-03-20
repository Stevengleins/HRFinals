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

// 1. Get Total Employees
$empQuery = $mysql->query("SELECT COUNT(user_id) AS total_emp FROM user WHERE role = 'Employee' AND status = 1");
$totalEmployees = $empQuery->fetch_assoc()['total_emp'] ?? 0;

// 2. Get Pending Leave Requests
$leaveQuery = $mysql->query("SELECT COUNT(leave_id) AS total_leaves FROM leave_requests WHERE status = 'Pending'");
$pendingLeaves = $leaveQuery->fetch_assoc()['total_leaves'] ?? 0;

// 3. Get Present Today (Attendance)
$attQuery = $mysql->query("SELECT COUNT(DISTINCT user_id) AS present_today FROM attendance WHERE date = '$today'");
$presentToday = $attQuery->fetch_assoc()['present_today'] ?? 0;

// 4. CONNECTION: Get Pending Payroll Count
$payrollQuery = $mysql->query("SELECT COUNT(payroll_id) AS pending_pay FROM payroll WHERE status = 'Pending'");
$pendingPayroll = $payrollQuery->fetch_assoc()['pending_pay'] ?? 0;

// 5. Fetch the 5 most recent pending leave requests for the quick-view table
$recentLeavesQuery = $mysql->query("
    SELECT lr.*, u.first_name, u.last_name 
    FROM leave_requests lr 
    JOIN user u ON lr.user_id = u.user_id 
    WHERE lr.status = 'Pending' 
    ORDER BY lr.date_applied DESC 
    LIMIT 5
");

$title = "HR Dashboard | WorkForcePro";
include '../includes/hr_header.php';
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">HR Overview</h1>
      </div>
      <div class="col-sm-6 text-right d-none d-sm-block">
        <div class="d-inline-block bg-white border px-3 py-2" style="border-radius: 8px;">
            <i class="far fa-clock mr-2 text-dark"></i>
            <span class="font-weight-bold" id="current-time"><?php echo date('h:i A'); ?></span>
            <span class="text-muted ml-2 small uppercase font-weight-bold">| <?php echo date('M d, Y'); ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="row">
      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-users"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Employees</span>
            <span class="info-box-number text-lg"><?php echo $totalEmployees; ?></span>
          </div>
        </div>
      </div>
      
      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-success elevation-1"><i class="fas fa-user-check"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Present Today</span>
            <span class="info-box-number text-lg"><?php echo $presentToday; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-envelope-open-text text-white"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Pending Leaves</span>
            <span class="info-box-number text-lg"><?php echo $pendingLeaves; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-info elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Pending Payroll</span>
            <span class="info-box-number text-lg"><?php echo $pendingPayroll; ?> <small>Records</small></span>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-3">
      
      <div class="col-md-8">
        <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
          <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
                <i class="fas fa-bell mr-2"></i> Recent Leave Requests
            </h3>
            <a href="leave_management.php" class="btn btn-sm btn-outline-light font-weight-bold shadow-sm" style="border-radius: 6px;">View All</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped m-0 text-center align-middle">
                <thead class="bg-light">
                  <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Date Applied</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if($recentLeavesQuery && $recentLeavesQuery->num_rows > 0): while($row = $recentLeavesQuery->fetch_assoc()): ?>
                  <tr>
                    <td class="font-weight-bold text-left pl-4"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><span class="badge bg-secondary px-2 py-1"><?php echo htmlspecialchars($row['leave_type']); ?></span></td>
                    <td class="text-muted"><?php echo date('M d, Y', strtotime($row['date_applied'])); ?></td>
                    <td>
                        <a href="leave_management.php" class="btn btn-sm btn-outline-dark shadow-sm" style="border-radius: 4px;">Review</a>
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
        <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
          <div class="card-header bg-dark text-white py-3 border-bottom-0">
            <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
                <i class="fas fa-bolt mr-2"></i> Quick Actions
            </h3>
          </div>
          <div class="card-body bg-light">
            <a href="attendance.php" class="btn btn-outline-dark btn-block mb-3 shadow-sm py-2" style="border-radius: 6px; font-weight: 600; background: white;">
                <i class="fas fa-clipboard-check mr-2"></i> View Today's Attendance
            </a>
            <a href="leave_management.php" class="btn btn-outline-dark btn-block mb-3 shadow-sm py-2" style="border-radius: 6px; font-weight: 600; background: white;">
                <i class="fas fa-calendar-alt mr-2"></i> Manage Leaves
            </a>
            <a href="payroll.php" class="btn btn-outline-dark btn-block shadow-sm py-2" style="border-radius: 6px; font-weight: 600; background: white;">
                <i class="fas fa-money-check-alt mr-2"></i> Process Payroll
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
    function updateClock() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; 
        document.getElementById('current-time').textContent = hours + ':' + minutes + ' ' + ampm;
    }
    setInterval(updateClock, 1000);
</script>

<?php include '../includes/footer.php'; ?>