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

// Handle payroll release action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['release_payroll_id'])) {
    $releasePayrollId = intval($_POST['release_payroll_id']);
    $adminId = $_SESSION['user_id'];
    $releaseStmt = $mysql->prepare("UPDATE payroll SET status = 'Released', processed_by = ?, processed_date = NOW() WHERE payroll_id = ? AND user_id = ?");
    if ($releaseStmt) {
        $releaseStmt->bind_param('iii', $adminId, $releasePayrollId, $user_id);
        $releaseStmt->execute();
        $releaseStmt->close();
    }
    header("Location: view_employee_payroll.php?id=$user_id");
    exit();
}

// Get Employee Details
$userQuery = $mysql->query("SELECT first_name, last_name FROM user WHERE user_id = $user_id");
$user = $userQuery->fetch_assoc();

// Get their specific payroll records
$payrollQuery = $mysql->query("
    SELECT 
        payroll_id, 
        payroll_period, 
        days_worked, 
        daily_rate, 
        gross_salary,
        sss_employee_share,
        philhealth_employee_share,
        pagibig_employee_share,
        overtime_pay,
        undertime_deduction,
        late_deduction,
        total_mandatory_deductions,
        deductions,
        net_salary,
        status, 
        date_created
    FROM payroll 
    WHERE user_id = $user_id 
    ORDER BY payroll_id DESC
");

$summaryQuery = $mysql->prepare("SELECT
        COUNT(*) AS total_records,
        COALESCE(SUM(gross_salary), 0) AS total_gross_salary,
        COALESCE(SUM(total_mandatory_deductions), 0) AS total_mandatory_deductions,
        COALESCE(SUM(deductions), 0) AS total_deductions,
        COALESCE(SUM(net_salary), 0) AS total_net_salary
    FROM payroll
    WHERE user_id = ?");
$summaryQuery->bind_param('i', $user_id);
$summaryQuery->execute();
$summary = $summaryQuery->get_result()->fetch_assoc();

$totalRecords = $summary['total_records'] ?? 0;
$totalGrossSalary = $summary['total_gross_salary'] ?? 0;
$totalMandatoryDeductions = $summary['total_mandatory_deductions'] ?? 0;
$totalDeductions = $summary['total_deductions'] ?? 0;
$totalNetSalary = $summary['total_net_salary'] ?? 0;

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
    <div class="row">
      <div class="col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Payroll Records</span>
            <span class="info-box-number text-lg"><?php echo $totalRecords; ?></span>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-coins"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Gross</span>
            <span class="info-box-number text-lg">₱<?php echo number_format($totalGrossSalary, 2); ?></span>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-file-alt"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Deductions</span>
            <span class="info-box-number text-lg">₱<?php echo number_format($totalDeductions, 2); ?></span>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Net Paid</span>
            <span class="info-box-number text-lg">₱<?php echo number_format($totalNetSalary, 2); ?></span>
          </div>
        </div>
      </div>
    </div>

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
                <th>Gross Salary</th>
                <th>Mandatory Deductions</th>
                <th>Net Salary</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($payrollQuery && $payrollQuery->num_rows > 0): ?>
                <?php while($row = $payrollQuery->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['payroll_period']); ?></td>
                    <td><?php echo (int)$row['days_worked']; ?></td>
                    <td>₱<?php echo number_format($row['gross_salary'], 2); ?></td>
                    <td>₱<?php echo number_format($row['total_mandatory_deductions'], 2); ?></td>
                    <td class="font-weight-bold">₱<?php echo number_format($row['net_salary'], 2); ?></td>
                    <td>
                      <?php if ($row['status'] === 'Released'): ?>
                        <span class="badge badge-success px-3 py-2">Released</span>
                      <?php else: ?>
                        <span class="badge badge-warning px-3 py-2 text-dark">Pending</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['status'] !== 'Released'): ?>
                        <form method="post" style="display:inline;">
                          <input type="hidden" name="release_payroll_id" value="<?php echo (int)$row['payroll_id']; ?>">
                          <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Release this payroll record?');">
                            <i class="fas fa-check-circle mr-1"></i> Release
                          </button>
                        </form>
                      <?php else: ?>
                        <span class="text-muted">No action</span>
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
