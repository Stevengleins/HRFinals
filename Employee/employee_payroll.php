<?php
session_start();
require '../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$firstName = $_SESSION['first_name'] ?? 'Employee';
// Get full name for the payslip
$userQuery = $mysql->query("SELECT first_name, last_name FROM user WHERE user_id = $user_id");
$user = $userQuery->fetch_assoc();
$employeeName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);

$title = "My Payroll | WorkForcePro";
include '../includes/employee_header.php';

// Fetch payroll records for logged-in employee only
$stmt = $mysql->prepare("
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
        withholding_tax,
        deductions,
        net_salary,
        status, 
        date_created
    FROM payroll
    WHERE user_id = ?
    ORDER BY payroll_id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Summary counts and totals
$summaryStmt = $mysql->prepare("
    SELECT 
        COUNT(*) AS total_records,
        SUM(CASE WHEN status = 'Released' THEN 1 ELSE 0 END) AS released_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
        COALESCE(SUM(net_salary), 0) AS total_net_salary,
        COALESCE(SUM(gross_salary + overtime_pay), 0) AS total_gross_salary,
        COALESCE(SUM(total_mandatory_deductions), 0) AS total_mandatory,
        COALESCE(SUM(withholding_tax), 0) AS total_withholding,
        COALESCE(SUM(sss_employee_share), 0) AS total_sss,
        COALESCE(SUM(philhealth_employee_share), 0) AS total_philhealth,
        COALESCE(SUM(pagibig_employee_share), 0) AS total_pagibig,
        COALESCE(SUM(deductions + late_deduction + undertime_deduction), 0) AS total_other_deductions
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
$totalGrossSalary = $summary['total_gross_salary'] ?? 0;
$totalMandatory = $summary['total_mandatory'] ?? 0;
$totalWithholding = $summary['total_withholding'] ?? 0;
$totalSSS = $summary['total_sss'] ?? 0;
$totalPhilHealth = $summary['total_philhealth'] ?? 0;
$totalPagIBIG = $summary['total_pagibig'] ?? 0;
$totalOtherDeductions = $summary['total_other_deductions'] ?? 0;
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
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">My Payroll History</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <div class="row">
      <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Payroll Records</span>
            <span class="info-box-number text-lg"><?php echo $totalRecords; ?></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Released</span>
            <span class="info-box-number text-lg text-success"><?php echo $releasedCount; ?></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock text-white"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Pending</span>
            <span class="info-box-number text-lg text-warning"><?php echo $pendingCount; ?></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-money-bill-wave"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Net Pay</span>
            <span class="info-box-number text-lg text-primary">₱<?php echo number_format($totalNetSalary, 0); ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mt-2" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-chart-pie mr-2"></i> Lifetime Financial Summary
        </h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div style="background: #e8f4f8; border-left: 4px solid #36b9cc; color: #2c3e50; padding: 20px; border-radius: 4px; margin-bottom: 15px;">
              <h5 class="font-weight-bold mb-2">Total Earnings</h5>
              <p style="font-size: 28px; margin: 0; color: #28a745; font-weight: bold;">₱<?php echo number_format($totalGrossSalary, 2); ?></p>
              <small class="text-muted">Across all payroll periods</small>
            </div>
          </div>
          <div class="col-md-6">
            <div style="background: #e8faea; border-left: 4px solid #1cc88a; color: #2c3e50; padding: 20px; border-radius: 4px; margin-bottom: 15px;">
              <h5 class="font-weight-bold mb-2">Total Net Take-Home</h5>
              <p style="font-size: 28px; margin: 0; color: #28a745; font-weight: bold;">₱<?php echo number_format($totalNetSalary, 2); ?></p>
              <small class="text-muted">After all deductions</small>
            </div>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-md-6">
            <h6 class="font-weight-bold mb-3 text-dark">Mandatory Contributions Breakdown</h6>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">SSS:</span>
                <strong class="text-dark">₱<?php echo number_format($totalSSS, 2); ?></strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">PhilHealth:</span>
                <strong class="text-dark">₱<?php echo number_format($totalPhilHealth, 2); ?></strong>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">Pag-IBIG:</span>
                <strong class="text-dark">₱<?php echo number_format($totalPagIBIG, 2); ?></strong>
              </div>
              <hr>
              <div class="d-flex justify-content-between" style="font-size: 16px;">
                <span class="font-weight-bold">Total Mandatory:</span>
                <strong class="text-danger-custom">- ₱<?php echo number_format($totalMandatory, 2); ?></strong>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <h6 class="font-weight-bold mb-3 text-dark">Deduction & Tax Summary</h6>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total Other Deductions (Lates/Loans):</span>
                <strong class="text-danger-custom">- ₱<?php echo number_format($totalOtherDeductions, 2); ?></strong>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">Total Lifetime Deductions:</span>
                <strong class="text-danger-custom">- ₱<?php echo number_format($totalMandatory + $totalOtherDeductions, 2); ?></strong>
              </div>
              <hr>
              <div class="d-flex justify-content-between" style="font-size: 14px;">
                <span class="text-muted">Avg. Deduction Rate:</span>
                <strong class="text-dark"><?php $rate = ($totalGrossSalary > 0) ? (($totalMandatory + $totalOtherDeductions) / $totalGrossSalary * 100) : 0; echo number_format($rate, 2); ?>%</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mt-4 mb-5" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-list-ul mr-2"></i> My Payslips
        </h3>
      </div>

      <div class="card-body p-0 bg-white">
        <div class="table-responsive p-3">
          <table id="payrollTable" class="table table-hover table-custom w-100 text-center align-middle">
            <thead>
              <tr>
                <th class="text-left">Payroll Period</th>
                <th>Total Earnings</th>
                <th>Total Deductions</th>
                <th>Net Salary</th>
                <th>Status</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                
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
                    <td class="font-weight-bold text-dark text-left"><?php echo $fmt_period; ?></td>
                    <td class="text-success-custom">₱<?php echo number_format($total_earnings, 2); ?></td>
                    <td class="text-danger-custom">- ₱<?php echo number_format($total_deductions, 2); ?></td>
                    <td class="text-success-custom" style="font-size: 1.05rem;">₱<?php echo number_format($net_salary, 2); ?></td>
                    <td>
                      <?php if ($row['status'] === 'Released'): ?>
                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Released</span>
                      <?php else: ?>
                        <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-clock mr-1"></i> Pending</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-sm btn-outline-dark font-weight-bold shadow-sm w-100"
                        onclick="viewPayslip(
                          '<?php echo htmlspecialchars($employeeName, ENT_QUOTES); ?>',
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
                          '<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>'
                        )">
                        <i class="fas fa-eye mr-1"></i> View Payslip
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
      $('#payrollTable').DataTable({
          "responsive": true, 
          "lengthChange": true, 
          "autoWidth": false,
          "searching": true,
          "ordering": true,
          "pageLength": 10,
          "order": [[ 0, "desc" ]], 
          "language": { "search": "_INPUT_", "searchPlaceholder": "Search records..." }
      });
  });

  function viewPayslip(employeeName, period, dateGenerated, daysWorked, grossSalary, sss, philhealth, pagibig, overtime, lates, withholdingTax, deductions, netSalary, status) {
      
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
                          <p style="margin:5px 0 0 0; font-size: 11px; color: #888;">Date Generated: ${dateGenerated}</p>
                      </div>
                  </div>

                  <table class="popup-table">
                      <thead>
                          <tr>
                              <th style="border-right: 1px solid #777;">Earnings</th>
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

                  <div style="background-color: #f4f4f4; padding: 15px; margin-top: 20px; border-top: 2px solid #333; text-align: right;">
                      <span style="font-size: 14px; text-transform: uppercase; color: #555; margin-right: 15px;">Net Pay</span>
                      <strong style="font-size: 24px; color: #212529;">₱${netSalary}</strong>
                  </div>
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
