<?php
session_start();
require '../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: payroll_admin.php");
    exit();
}

$user_id = intval($_GET['id']);

// Get Employee Details
$userQuery = $mysql->query("SELECT first_name, last_name FROM user WHERE user_id = $user_id");
$user = $userQuery->fetch_assoc();

// Get their specific payroll records
$payrollQuery = $mysql->query("SELECT * FROM payroll WHERE user_id = $user_id ORDER BY payroll_id DESC");

$title = "Employee Payroll | WorkForcePro";
include '../includes/admin_header.php';
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Payroll History: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="payroll_admin.php" class="btn btn-secondary mr-2">Back to Overview</a>
        <a href="export_payroll.php?type=single&user_id=<?php echo $user_id; ?>" class="btn btn-success">
          <i class="fas fa-file-excel mr-1"></i> Export to Excel
        </a>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card shadow-sm border-0 mt-3" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-file-invoice-dollar mr-2"></i> Payroll Records
        </h3>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped m-0 text-center align-middle">
            <thead class="bg-light">
              <tr>
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
                    <td><?php echo htmlspecialchars($row['payroll_period']); ?></td>
                    <td><?php echo (int)$row['days_worked']; ?></td>
                    <td>₱<?php echo number_format($row['daily_rate'], 2); ?></td>
                    <td>₱<?php echo number_format($row['gross_salary'], 2); ?></td>
                    <td>₱<?php echo number_format($row['deductions'], 2); ?></td>
                    <td class="font-weight-bold">₱<?php echo number_format($row['net_salary'], 2); ?></td>
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
                  <td colspan="7" class="text-center py-4 text-muted">No payroll records found for this employee.</td>
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