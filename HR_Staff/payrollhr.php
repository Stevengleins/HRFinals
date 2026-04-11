<?php
session_start();
require '../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

$title = "Payroll | WorkForcePro";
include '../includes/hr_header.php';

$employeesQuery = $mysql->query("
    SELECT user_id, first_name, last_name
    FROM user
    WHERE role = 'Employee'
    ORDER BY first_name ASC
");

$payrollQuery = $mysql->query("
    SELECT p.*, u.first_name, u.last_name
    FROM payroll p
    JOIN user u ON p.user_id = u.user_id
    ORDER BY p.payroll_id DESC
");

$totalEmployeesQuery = $mysql->query("SELECT COUNT(user_id) AS total FROM user WHERE role = 'Employee'");
$totalEmployees = $totalEmployeesQuery->fetch_assoc()['total'] ?? 0;

$processedQuery = $mysql->query("SELECT COUNT(payroll_id) AS total FROM payroll WHERE status = 'Released'");
$processedPayroll = $processedQuery->fetch_assoc()['total'] ?? 0;

$pendingQuery = $mysql->query("SELECT COUNT(payroll_id) AS total FROM payroll WHERE status = 'Pending'");
$pendingPayroll = $pendingQuery->fetch_assoc()['total'] ?? 0;

$totalAmountQuery = $mysql->query("SELECT COALESCE(SUM(net_salary), 0) AS total_amount FROM payroll");
$totalPayrollAmount = $totalAmountQuery->fetch_assoc()['total_amount'] ?? 0;
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<style>
    .table-custom thead th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; border-top: none; color: #495057; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
    .table-custom td { vertical-align: middle !important; border-top: 1px solid #e9ecef; font-size: 0.95rem; }
    .text-success-custom { color: #28a745 !important; font-weight: bold; }
    .text-danger-custom { color: #dc3545 !important; font-weight: bold; }
    .popup-table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
    .popup-table th, .popup-table td { border-bottom: 1px solid #eee; padding: 10px 5px; }
    .popup-table th { color: #fff; font-weight: normal; background-color: #555; text-transform: uppercase; font-size: 12px; }
</style>

<div class="content-header pb-2">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Payroll Management</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="payroll_processing.php" class="btn btn-dark btn-sm shadow-sm font-weight-bold mr-2 px-3" style="border-radius: 4px;">
          <i class="fas fa-calculator mr-1"></i> Calculate Payroll
        </a>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-users"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Employees</span>
            <span class="info-box-number text-lg"><?php echo $totalEmployees; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Payroll Processed</span>
            <span class="info-box-number text-lg text-success"><?php echo $processedPayroll; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock text-white"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Pending Payroll</span>
            <span class="info-box-number text-lg text-warning"><?php echo $pendingPayroll; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-money-bill-wave"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Payroll Disbursed</span>
            <span class="info-box-number text-lg text-primary">₱<?php echo number_format($totalPayrollAmount, 2); ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mt-3 mb-5" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-file-invoice-dollar mr-2"></i> Employee Payroll Records
        </h3>
      </div>

      <div class="card-body p-0 bg-white">
        <div class="table-responsive p-3">
          <table id="hrPayrollTable" class="table table-hover table-custom w-100 text-center align-middle">
            <thead>
              <tr>
                <th class="text-left">Employee Name</th>
                <th>Payroll Period</th>
                <th>Total Earnings</th>
                <th>Total Deductions</th>
                <th>Net Salary</th>
                <th>Date Logged</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($payrollQuery && $payrollQuery->num_rows > 0): ?>
                <?php while($row = $payrollQuery->fetch_assoc()): 
                    $raw_period = $row['payroll_period'];
                    $p_parts = explode(' to ', $raw_period);
                    $fmt_period = (count($p_parts) === 2) 
                        ? date('M d, Y', strtotime($p_parts[0])) . ' - ' . date('M d, Y', strtotime($p_parts[1])) 
                        : htmlspecialchars($raw_period);

                    // MATHEMATICALLY FLAWLESS TABLE MATH
                    $total_earnings = $row['gross_salary'] + $row['overtime_pay'];
                    $net_salary = $row['net_salary'];
                    $total_deductions = max(0, $total_earnings - $net_salary);
                ?>
                  <tr>
                    <td class="font-weight-bold text-dark text-left">
                      <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                    </td>
                    <td><?php echo $fmt_period; ?></td>
                    <td class="text-success-custom">₱<?php echo number_format($total_earnings, 2); ?></td>
                    <td class="text-danger-custom">- ₱<?php echo number_format($total_deductions, 2); ?></td>
                    <td class="text-success-custom" style="font-size: 1.05rem;">₱<?php echo number_format($net_salary, 2); ?></td>
                    <td class="text-muted"><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                    <td>
                      <?php if ($row['status'] === 'Released'): ?>
                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Released</span>
                      <?php else: ?>
                        <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-clock mr-1"></i> Pending</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-sm btn-outline-dark font-weight-bold shadow-sm"
                          onclick="viewPayslip(
                            <?php echo (int)$row['payroll_id']; ?>,
                            '<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES); ?>',
                            '<?php echo htmlspecialchars($fmt_period, ENT_QUOTES); ?>',
                            '<?php echo date('M d, Y', strtotime($row['date_created'])); ?>',
                            '<?php echo (int)$row['days_worked']; ?>',
                            '<?php echo number_format($row['gross_salary'], 2); ?>',
                            '<?php echo number_format($row['sss_employee_share'], 2); ?>',
                            '<?php echo number_format($row['philhealth_employee_share'], 2); ?>',
                            '<?php echo number_format($row['pagibig_employee_share'], 2); ?>',
                            '<?php echo number_format($row['overtime_pay'], 2); ?>',
                            '<?php echo number_format($row['undertime_deduction'] + $row['late_deduction'], 2); ?>',
                            '<?php echo number_format($row['withholding_tax'] ?? 0, 2); ?>',
                            '<?php echo number_format($row['deductions'], 2); ?>',
                            '<?php echo number_format($net_salary, 2); ?>',
                            '<?php echo number_format($total_earnings, 2); ?>',
                            '<?php echo number_format($total_deductions, 2); ?>',
                            '<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>'
                          )">
                          <i class="fas fa-eye mr-1"></i> View
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(document).ready(function () {
      $('#hrPayrollTable').DataTable({
          "responsive": true, 
          "lengthChange": true, 
          "autoWidth": false,
          "searching": true,
          "ordering": true,
          "pageLength": 10,
          "order": [[ 5, "desc" ]], // Order by Date Processed
          "language": { "search": "_INPUT_", "searchPlaceholder": "Search records..." }
      });
  });

  // HR View Payslip Function (No Release Button)
  function viewPayslip(payrollId, employeeName, period, dateGenerated, daysWorked, grossSalary, sss, philhealth, pagibig, overtime, lates, withholdingTax, deductions, netSalary, totalEarnings, totalDeductions, status) {
      
      let statusBadge = status === 'Released' 
          ? `<span style="color: #28a745; font-weight: bold; border: 2px solid #28a745; padding: 2px 8px; border-radius: 4px; font-size: 12px; letter-spacing: 1px;">RELEASED</span>` 
          : `<span style="color: #ffc107; font-weight: bold; border: 2px solid #ffc107; padding: 2px 8px; border-radius: 4px; font-size: 12px; letter-spacing: 1px;">PENDING</span>`;

      Swal.fire({
          title: `
              <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 5px;">
                  <img src="../logo.png" alt="WORKFORCEPRO" style="opacity: 1; max-height: 35px; border-radius: 4px; margin-right: 8px;">
                  <h2 style="margin:0; font-size: 22px; color: #333;"><strong>WORK</strong><span style="font-weight: normal;">FORCEPRO</span></h2>
              </div>
              <p style="font-size: 14px; color: #666; margin: 5px 0 15px 0;">Statement of Earning & Deductions</p>
          `,
          html: `
              <div style="text-align:left; font-family: Arial, sans-serif;">
                  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px;">
                      <div>
                          <p style="margin:0; font-size: 15px;"><strong>${employeeName}</strong></p>
                          <p style="margin:0; font-size: 13px; color: #555;">Period: ${period}</p>
                      </div>
                      <div style="text-align: right;">
                          ${statusBadge}
                          <p style="margin:5px 0 0 0; font-size: 11px; color: #888;">Date Logged: ${dateGenerated}</p>
                      </div>
                  </div>

                  <table class="popup-table">
                      <thead>
                          <tr>
                              <th style="border-right: 1px solid #777;">Gross Earnings</th>
                              <th style="text-align: right;">Amount</th>
                          </tr>
                      </thead>
                      <tbody>
                          <tr><td>Basic Pay (${daysWorked} Days)</td><td align="right" style="color: #28a745;">+ ₱${grossSalary}</td></tr>
                          <tr><td>Overtime Pay</td><td align="right" style="color: #28a745;">+ ₱${overtime}</td></tr>
                      </tbody>
                  </table>

                  <table class="popup-table" style="margin-top: 15px;">
                      <thead>
                          <tr>
                              <th style="border-right: 1px solid #777;">Statutory Deductions</th>
                              <th style="text-align: right;">Amount</th>
                          </tr>
                      </thead>
                      <tbody>
                          <tr><td>FICA - SSS</td><td align="right" style="color: #dc3545;">- ₱${sss}</td></tr>
                          <tr><td>FICA - PhilHealth</td><td align="right" style="color: #dc3545;">- ₱${philhealth}</td></tr>
                          <tr><td>FICA - Pag-IBIG</td><td align="right" style="color: #dc3545;">- ₱${pagibig}</td></tr>
                          <tr><td>Withholding Tax</td><td align="right" style="color: #dc3545;">- ₱${withholdingTax}</td></tr>
                          <tr><td>Lates & Undertime</td><td align="right" style="color: #dc3545;">- ₱${lates}</td></tr>
                          <tr><td>Other Deductions</td><td align="right" style="color: #dc3545;">- ₱${deductions}</td></tr>
                      </tbody>
                  </table>

                  <table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px;">
                      <tr style="border-top: 2px solid #333; background-color: #f8f9fa;">
                          <td style="padding: 12px; border-right: 1px solid #ddd;">
                              <strong>Total Earnings</strong><br>
                              <span style="font-size: 16px;">₱${totalEarnings}</span>
                          </td>
                          <td style="padding: 12px; border-right: 1px solid #ddd;">
                              <strong>Total Deductions</strong><br>
                              <span style="font-size: 16px; color: #dc3545;">- ₱${totalDeductions}</span>
                          </td>
                          <td style="padding: 12px; text-align: right;">
                              <strong>Net Pay</strong><br>
                              <strong style="font-size: 20px; color: #28a745;">₱${netSalary}</strong>
                          </td>
                      </tr>
                  </table>
              </div>
          `,
          icon: null,
          showConfirmButton: false,
          showCloseButton: true,
          width: '600px',
          customClass: { popup: 'rounded-0' }
      });
  }
</script>

<?php
if (isset($_SESSION['status_icon']) && isset($_SESSION['status_title']) && isset($_SESSION['status_text'])) {
    $icon = $_SESSION['status_icon'];
    $titleMsg = $_SESSION['status_title'];
    $text = $_SESSION['status_text'];
    echo "<script>Swal.fire({icon: '$icon', title: '$titleMsg', text: '$text', confirmButtonColor: '#212529'});</script>";
    unset($_SESSION['status_icon'], $_SESSION['status_title'], $_SESSION['status_text']);
}
?>