<?php
session_start();
require '../database.php';
require '../includes/PayrollCalculator.php';
require '../includes/PayrollProcessor.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

$title = "Payroll Processing | WorkForcePro";
include '../includes/hr_header.php';

$processor = new PayrollProcessor($mysql);
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'process_payroll') {
            $userId = (int)$_POST['employee_id'];
            $grossSalary = (float)$_POST['gross_salary'];
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            $payPeriod = $_POST['pay_period'] ?? 'semi-monthly';
            $processedBy = $_SESSION['user_id'] ?? null;

            // Validate dates
            if (strtotime($startDate) >= strtotime($endDate)) {
                throw new Exception("End date must be after start date");
            }

            // Calculate payroll
            $summary = $processor->calculateEmployeePayroll(
                $userId,
                $grossSalary,
                $startDate,
                $endDate,
                $payPeriod
            );

            // Save to database
            $payrollId = $processor->savePayrollRecord(
                $userId,
                $startDate,
                $endDate,
                $summary,
                'Pending',
                $processedBy
            );

            $message = "Payroll processed successfully! Payroll ID: #" . $payrollId;
            $messageType = 'success';

            // Store summary for display
            $_SESSION['payroll_summary'] = $summary;
            $_SESSION['payroll_id'] = $payrollId;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all employees
$employeesQuery = $mysql->query("
    SELECT user_id, first_name, last_name
    FROM user
    WHERE role = 'Employee'
    ORDER BY first_name ASC
");

// Get recent payroll records
$recentPayrollQuery = $mysql->query("
    SELECT p.payroll_id, p.user_id, p.payroll_period, p.gross_salary, 
           p.net_salary, p.status, p.date_created, u.first_name, u.last_name
    FROM payroll p
    JOIN user u ON p.user_id = u.user_id
    ORDER BY p.date_created DESC
    LIMIT 10
");
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Payroll Processing</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <!-- Status Messages -->
    <?php if ($message): ?>
      <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
        <?php echo $message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php endif; ?>

    <!-- Payroll Calculation Form -->
    <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold"><i class="fas fa-calculator mr-2"></i> Calculate Employee Payroll</h3>
      </div>
      <div class="card-body">
        <form method="POST" action="">
          <input type="hidden" name="action" value="process_payroll">

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="employee_id" class="font-weight-bold">Select Employee</label>
                <select name="employee_id" id="employee_id" class="form-control" required>
                  <option value="">-- Choose an Employee --</option>
                  <?php if ($employeesQuery && $employeesQuery->num_rows > 0): ?>
                    <?php while ($emp = $employeesQuery->fetch_assoc()): ?>
                      <option value="<?php echo $emp['user_id']; ?>">
                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                      </option>
                    <?php endwhile; ?>
                  <?php endif; ?>
                </select>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="pay_period" class="font-weight-bold">Pay Period Type</label>
                <select name="pay_period" id="pay_period" class="form-control" required>
                  <option value="monthly">Monthly</option>
                  <option value="semi-monthly" selected>Semi-Monthly</option>
                  <option value="daily">Daily</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="gross_salary" class="font-weight-bold">Gross Salary (₱)</label>
                <input type="number" name="gross_salary" id="gross_salary" class="form-control" 
                       step="0.01" min="0" placeholder="Enter gross salary" required>
                <small class="form-text text-muted">Employee's base salary for this period</small>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label for="start_date" class="font-weight-bold">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" required>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label for="end_date" class="font-weight-bold">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-play mr-2"></i> Calculate & Process Payroll
            </button>
            <button type="reset" class="btn btn-secondary btn-lg">
              <i class="fas fa-redo mr-2"></i> Clear Form
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Payroll Summary Preview -->
    <?php if (isset($_SESSION['payroll_summary'])): 
        $summary = $_SESSION['payroll_summary'];
        $payrollId = $_SESSION['payroll_id'] ?? 0;
        $formatter = new PayrollCalculator();
    ?>
      <div class="card shadow-sm border-0 mt-4" style="border-radius: 8px; overflow: hidden;">
        <div class="card-header bg-success text-white py-3 border-bottom-0">
          <h3 class="card-title m-0 font-weight-bold"><i class="fas fa-receipt mr-2"></i> Payroll Summary #<?php echo $payrollId; ?></h3>
        </div>
        <div class="card-body">
          <div class="row">
            <!-- Gross Compensation -->
            <div class="col-md-6">
              <h5 class="font-weight-bold mb-3"><i class="fas fa-dollar-sign text-primary"></i> Gross Compensation</h5>
              <table class="table table-sm table-borderless">
                <tr>
                  <td>Base Salary:</td>
                  <td class="text-right font-weight-bold">₱<?php echo number_format($summary['gross_salary'], 2); ?></td>
                </tr>
                <?php if ($summary['overtime_pay'] > 0): ?>
                <tr class="table-success">
                  <td><i class="fas fa-plus text-success"></i> Overtime Pay (1.25x):</td>
                  <td class="text-right font-weight-bold text-success">+₱<?php echo number_format($summary['overtime_pay'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($summary['undertime_deduction'] > 0): ?>
                <tr class="table-danger">
                  <td><i class="fas fa-minus text-danger"></i> Undertime Deduction:</td>
                  <td class="text-right font-weight-bold text-danger">-₱<?php echo number_format($summary['undertime_deduction'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($summary['late_deduction'] > 0): ?>
                <tr class="table-danger">
                  <td><i class="fas fa-minus text-danger"></i> Late Deduction:</td>
                  <td class="text-right font-weight-bold text-danger">-₱<?php echo number_format($summary['late_deduction'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="border-top">
                  <td class="font-weight-bold">Adjusted Gross:</td>
                  <td class="text-right font-weight-bold">₱<?php echo number_format($summary['adjusted_gross_salary'], 2); ?></td>
                </tr>
              </table>
            </div>

            <!-- Mandatory Deductions -->
            <div class="col-md-6">
              <h5 class="font-weight-bold mb-3"><i class="fas fa-hand-holding-heart text-info"></i> Mandatory Deductions</h5>
              <table class="table table-sm table-borderless">
                <tr>
                  <td>SSS (Employee 4.5%):</td>
                  <td class="text-right font-weight-bold">₱<?php echo number_format($summary['sss']['employee_share'], 2); ?></td>
                </tr>
                <tr>
                  <td>PhilHealth (Employee 2.5%):</td>
                  <td class="text-right font-weight-bold">₱<?php echo number_format($summary['philhealth']['employee_share'], 2); ?></td>
                </tr>
                <tr>
                  <td>Pag-IBIG (Employee Fixed ₱200):</td>
                  <td class="text-right font-weight-bold">₱<?php echo number_format($summary['pagibig']['employee_share'], 2); ?></td>
                </tr>
                <tr class="border-top">
                  <td class="font-weight-bold">Total Mandatory:</td>
                  <td class="text-right font-weight-bold">₱<?php echo number_format($summary['total_mandatory_deductions'], 2); ?></td>
                </tr>
              </table>
            </div>
          </div>

          <hr>

          <div class="row">
            <!-- Taxable Income & Withholding Tax -->
            <div class="col-md-6">
              <h5 class="font-weight-bold mb-3"><i class="fas fa-chart-line text-warning"></i> Tax Computation</h5>
              <table class="table table-sm table-borderless">
                <tr>
                  <td>Taxable Income:</td>
                  <td class="text-right font-weight-bold">₱<?php echo number_format($summary['taxable_income'], 2); ?></td>
                </tr>
                <tr>
                  <td>Withholding Tax (TRAIN):</td>
                  <td class="text-right font-weight-bold text-danger">₱<?php echo number_format($summary['withholding_tax'], 2); ?></td>
                </tr>
              </table>
            </div>

            <!-- Attendance Summary -->
            <div class="col-md-6">
              <h5 class="font-weight-bold mb-3"><i class="fas fa-calendar-check text-info"></i> Attendance</h5>
              <table class="table table-sm table-borderless">
                <tr>
                  <td>Regular Hours:</td>
                  <td class="text-right"><?php echo $summary['attendance']['regular_hours']; ?> hrs</td>
                </tr>
                <tr>
                  <td>Approved Overtime:</td>
                  <td class="text-right text-success"><?php echo $summary['attendance']['approved_overtime_hours']; ?> hrs</td>
                </tr>
                <tr>
                  <td>Days Present:</td>
                  <td class="text-right"><?php echo $summary['attendance']['days_present']; ?></td>
                </tr>
                <tr>
                  <td>Days Absent:</td>
                  <td class="text-right text-danger"><?php echo $summary['attendance']['days_absent']; ?></td>
                </tr>
              </table>
            </div>
          </div>

          <hr>

          <!-- Final Summary -->
          <div class="row">
            <div class="col-md-12">
              <table class="table table-sm table-borderless" style="font-size: 1.1rem;">
                <tr class="border-top border-bottom" style="background-color: #f8f9fa;">
                  <td class="font-weight-bold">Total Deductions:</td>
                  <td class="text-right font-weight-bold">₱<?php echo number_format($summary['total_deductions'], 2); ?></td>
                </tr>
                <tr style="background-color: #e8f5e9; font-size: 1.25rem;">
                  <td class="font-weight-bold"><i class="fas fa-hand-holding-usd text-success"></i> NET TAKE-HOME PAY:</td>
                  <td class="text-right font-weight-bold text-success">₱<?php echo number_format($summary['net_take_home_pay'], 2); ?></td>
                </tr>
                <tr>
                  <td colspan="2" class="text-muted text-center small">
                    <i class="fas fa-info-circle"></i> For reference only. Employer contributions: ₱<?php echo number_format($summary['employer_contributions'], 2); ?>
                  </td>
                </tr>
              </table>
            </div>
          </div>
        </div>
        <div class="card-footer bg-light py-3">
          <a href="payrollhr.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Payroll List
          </a>
          <a href="export_leaves_csv.php?payroll_id=<?php echo $payrollId; ?>" class="btn btn-success" title="Export payroll details">
            <i class="fas fa-download mr-2"></i> Export Details
          </a>
        </div>
      </div>

      <?php unset($_SESSION['payroll_summary'], $_SESSION['payroll_id']); endif; ?>

    <!-- Recent Payroll Records -->
    <div class="card shadow-sm border-0 mt-4" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold"><i class="fas fa-history mr-2"></i> Recent Payroll Records</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped m-0 text-center align-middle">
            <thead class="bg-light">
              <tr>
                <th>Payroll ID</th>
                <th>Employee</th>
                <th>Payroll Period</th>
                <th>Gross Salary</th>
                <th>Net Salary</th>
                <th>Status</th>
                <th>Date Created</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recentPayrollQuery && $recentPayrollQuery->num_rows > 0): ?>
                <?php while ($row = $recentPayrollQuery->fetch_assoc()): ?>
                  <tr>
                    <td class="font-weight-bold">#<?php echo $row['payroll_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['payroll_period']); ?></td>
                    <td>₱<?php echo number_format($row['gross_salary'], 2); ?></td>
                    <td class="text-success font-weight-bold">₱<?php echo number_format($row['net_salary'], 2); ?></td>
                    <td>
                      <span class="badge badge-<?php echo $row['status'] === 'Released' ? 'success' : 'warning'; ?>">
                        <?php echo $row['status']; ?>
                      </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No payroll records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</section>

<?php include '../includes/footer.php'; ?>
