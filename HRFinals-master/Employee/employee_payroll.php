<?php
session_start();
require '../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$firstName = $_SESSION['first_name'] ?? 'Employee';

$title = "My Payroll | WorkForcePro";
include '../includes/employee_header.php';

// Fetch payroll records for logged-in employee only
$stmt = $mysql->prepare("
    SELECT payroll_id, payroll_period, days_worked, daily_rate, gross_salary, deductions, net_salary, status, date_created
    FROM payroll
    WHERE user_id = ?
    ORDER BY payroll_id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Summary counts
$summaryStmt = $mysql->prepare("
    SELECT 
        COUNT(*) AS total_records,
        SUM(CASE WHEN status = 'Released' THEN 1 ELSE 0 END) AS released_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
        COALESCE(SUM(net_salary), 0) AS total_net_salary
    FROM payroll
    WHERE user_id = ?
");
$summaryStmt->bind_param("i", $user_id);
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();

$totalRecords = $summary['total_records'] ?? 0;
$releasedCount = $summary['released_count'] ?? 0;
$pendingCount = $summary['pending_count'] ?? 0;
$totalNetSalary = $summary['total_net_salary'] ?? 0;
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">My Payroll</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <div class="row">
      <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Payroll Records</span>
            <span class="info-box-number text-lg"><?php echo $totalRecords; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Released</span>
            <span class="info-box-number text-lg"><?php echo $releasedCount; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock text-white"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Pending</span>
            <span class="info-box-number text-lg"><?php echo $pendingCount; ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mt-3" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-money-check-alt mr-2"></i> Payroll Summary
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
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td class="font-weight-bold"><?php echo htmlspecialchars($row['payroll_period']); ?></td>
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
                    <td>
                      <button class="btn btn-sm btn-outline-dark shadow-sm"
                        onclick="viewPayroll(
                          '<?php echo htmlspecialchars($firstName, ENT_QUOTES); ?>',
                          '<?php echo htmlspecialchars($row['payroll_period'], ENT_QUOTES); ?>',
                          '<?php echo (int)$row['days_worked']; ?>',
                          '<?php echo number_format($row['daily_rate'], 2); ?>',
                          '<?php echo number_format($row['gross_salary'], 2); ?>',
                          '<?php echo number_format($row['deductions'], 2); ?>',
                          '<?php echo number_format($row['net_salary'], 2); ?>',
                          '<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>'
                        )">
                        <i class="fas fa-eye mr-1"></i> View
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center py-4 text-muted">No payroll records found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
            <tfoot class="bg-light">
              <tr>
                <th colspan="5" class="text-right">Total Net Salary</th>
                <th colspan="3" class="text-left">₱<?php echo number_format($totalNetSalary, 2); ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

  </div>
</section>

<?php include '../includes/footer.php'; ?>

<script>
function viewPayroll(employeeName, period, daysWorked, dailyRate, grossSalary, deductions, netSalary, status) {
    Swal.fire({
        title: 'Payroll Details',
        html: `
            <div style="text-align:left;">
                <p><strong>Employee:</strong> ${employeeName}</p>
                <p><strong>Payroll Period:</strong> ${period}</p>
                <p><strong>Days Worked:</strong> ${daysWorked}</p>
                <p><strong>Daily Rate:</strong> ₱${dailyRate}</p>
                <p><strong>Gross Salary:</strong> ₱${grossSalary}</p>
                <p><strong>Deductions:</strong> ₱${deductions}</p>
                <p><strong>Net Salary:</strong> ₱${netSalary}</p>
                <p><strong>Status:</strong> ${status}</p>
            </div>
        `,
        icon: 'info',
        confirmButtonColor: '#212529'
    });
}
</script>   