<?php
session_start();
require '../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

$title = "Payroll | WorkForcePro";
include '../includes/hr_header.php';

// Get all employees
$employeesQuery = $mysql->query("
    SELECT user_id, first_name, last_name
    FROM user
    WHERE role = 'Employee'
    ORDER BY first_name ASC
");

// Get payroll records with employee names
$payrollQuery = $mysql->query("
    SELECT p.*, u.first_name, u.last_name
    FROM payroll p
    JOIN user u ON p.user_id = u.user_id
    ORDER BY p.payroll_id DESC
");

// Summary
$totalEmployeesQuery = $mysql->query("SELECT COUNT(user_id) AS total FROM user WHERE role = 'Employee'");
$totalEmployees = $totalEmployeesQuery->fetch_assoc()['total'] ?? 0;

$processedQuery = $mysql->query("SELECT COUNT(payroll_id) AS total FROM payroll WHERE status = 'Released'");
$processedPayroll = $processedQuery->fetch_assoc()['total'] ?? 0;

$pendingQuery = $mysql->query("SELECT COUNT(payroll_id) AS total FROM payroll WHERE status = 'Pending'");
$pendingPayroll = $pendingQuery->fetch_assoc()['total'] ?? 0;

$totalAmountQuery = $mysql->query("SELECT COALESCE(SUM(net_salary), 0) AS total_amount FROM payroll");
$totalPayrollAmount = $totalAmountQuery->fetch_assoc()['total_amount'] ?? 0;
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Payroll</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="payroll_processing.php" class="btn btn-primary shadow-sm mr-2">
          <i class="fas fa-plus-circle mr-1"></i> Calculate Payroll
        </a>
        <a href="../payroll_demo.php" class="btn btn-info shadow-sm" target="_blank">
          <i class="fas fa-flask mr-1"></i> Test Calculator
        </a>
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
          <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Payroll Processed</span>
            <span class="info-box-number text-lg"><?php echo $processedPayroll; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock text-white"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Pending Payroll</span>
            <span class="info-box-number text-lg"><?php echo $pendingPayroll; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-info elevation-1"><i class="fas fa-money-bill-wave"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Payroll</span>
            <span class="info-box-number text-lg">₱<?php echo number_format($totalPayrollAmount, 2); ?></span>
          </div>
        </div>
      </div>
    </div>

   
    <!-- Payroll Table -->
    <div class="card shadow-sm border-0 mt-3" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-file-invoice-dollar mr-2"></i> Employee Payroll Records
        </h3>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped m-0 text-center align-middle">
            <thead class="bg-light">
              <tr>
                <th>Employee Name</th>
                <th>Payroll Period</th>
                <th>Days Worked</th>
                <th>Daily Rate</th>
                <th>Gross Salary</th>
                <th>Deductions</th>
                <th>Net Salary</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($payrollQuery && $payrollQuery->num_rows > 0): ?>
                <?php while($row = $payrollQuery->fetch_assoc()): ?>
                  <tr>
                    <td class="font-weight-bold">
                      <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['payroll_period']); ?></td>
                    <td><?php echo (int)$row['days_worked']; ?></td>
                    <td>₱<?php echo number_format($row['daily_rate'], 2); ?></td>
                    <td>₱<?php echo number_format($row['gross_salary'], 2); ?></td>
                    <td>₱<?php echo number_format($row['deductions'], 2); ?></td>
                    <td>₱<?php echo number_format($row['net_salary'], 2); ?></td>
                    <td>
                      <?php if ($row['status'] === 'Released'): ?>
                        <span class="badge badge-success px-3 py-2">Released</span>
                      <?php else: ?>
                        <span class="badge badge-warning px-3 py-2 text-dark">Pending</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center py-4 text-muted">No payroll records found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</section>

<?php include '../includes/footer.php'; ?>

<?php
if (isset($_SESSION['status_icon']) && isset($_SESSION['status_title']) && isset($_SESSION['status_text'])) {
    $icon = $_SESSION['status_icon'];
    $titleMsg = $_SESSION['status_title'];
    $text = $_SESSION['status_text'];

    echo "
    <script>
        Swal.fire({
            icon: '$icon',
            title: '$titleMsg',
            text: '$text',
            confirmButtonColor: '#212529'
        });
    </script>
    ";

    unset($_SESSION['status_icon'], $_SESSION['status_title'], $_SESSION['status_text']);
}
?>