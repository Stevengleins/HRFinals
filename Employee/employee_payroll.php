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
        COALESCE(SUM(gross_salary), 0) AS total_gross_salary,
        COALESCE(SUM(total_mandatory_deductions), 0) AS total_mandatory,
        COALESCE(SUM(withholding_tax), 0) AS total_withholding,
        COALESCE(SUM(sss_employee_share), 0) AS total_sss,
        COALESCE(SUM(philhealth_employee_share), 0) AS total_philhealth,
        COALESCE(SUM(pagibig_employee_share), 0) AS total_pagibig
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

    <!-- Overall Summary Cards -->
    <div class="row">
      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Payroll Records</span>
            <span class="info-box-number text-lg"><?php echo $totalRecords; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Released</span>
            <span class="info-box-number text-lg"><?php echo $releasedCount; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock text-white"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Pending</span>
            <span class="info-box-number text-lg"><?php echo $pendingCount; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-info elevation-1"><i class="fas fa-money-bill-wave"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Net Pay</span>
            <span class="info-box-number text-lg">₱<?php echo number_format($totalNetSalary, 0); ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Overall Payroll Summary -->
    <div class="card shadow-sm border-0 mt-3" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-info text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-chart-pie mr-2"></i> Overall Payroll Summary
        </h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
              <h5 class="font-weight-bold mb-3">Total Earnings</h5>
              <p style="font-size: 28px; margin: 0;">₱<?php echo number_format($totalGrossSalary, 2); ?></p>
              <small>Across all payroll periods</small>
            </div>
          </div>
          <div class="col-md-6">
            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
              <h5 class="font-weight-bold mb-3">Total Net Take-Home</h5>
              <p style="font-size: 28px; margin: 0;">₱<?php echo number_format($totalNetSalary, 2); ?></p>
              <small>After all deductions</small>
            </div>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-md-6">
            <h6 class="font-weight-bold mb-3">Mandatory Deductions Breakdown</h6>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
              <div class="d-flex justify-content-between mb-2">
                <span>SSS (4.5% EE):</span>
                <strong>₱<?php echo number_format($totalSSS, 2); ?></strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>PhilHealth (2.5% EE):</span>
                <strong>₱<?php echo number_format($totalPhilHealth, 2); ?></strong>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span>Pag-IBIG (Fixed ₱200):</span>
                <strong>₱<?php echo number_format($totalPagIBIG, 2); ?></strong>
              </div>
              <hr>
              <div class="d-flex justify-content-between" style="font-size: 16px;">
                <span class="font-weight-bold">Total Mandatory:</span>
                <strong class="text-danger">₱<?php echo number_format($totalMandatory, 2); ?></strong>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <h6 class="font-weight-bold mb-3">Tax Summary</h6>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
              <div class="d-flex justify-content-between mb-2">
                <span>Total Deductions:</span>
                <strong class="text-danger">₱<?php echo number_format($totalMandatory, 2); ?></strong>
              </div>
              <hr>
              <div class="d-flex justify-content-between" style="font-size: 14px;">
                <span>Deduction Rate:</span>
                <strong><?php $rate = ($totalGrossSalary > 0) ? (($totalMandatory) / $totalGrossSalary * 100) : 0; echo number_format($rate, 2); ?>%</strong>
              </div>
            </div>
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
                <th>Gross Salary</th>
                <th>Mandatory Deductions</th>
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
                    <td>₱<?php echo number_format($row['gross_salary'], 2); ?></td>
                    <td>₱<?php echo number_format($row['total_mandatory_deductions'], 2); ?></td>
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
                          '<?php echo number_format($row['gross_salary'], 2); ?>',
                          '<?php echo number_format($row['sss_employee_share'], 2); ?>',
                          '<?php echo number_format($row['philhealth_employee_share'], 2); ?>',
                          '<?php echo number_format($row['pagibig_employee_share'], 2); ?>',
                          '<?php echo number_format($row['overtime_pay'], 2); ?>',
                          '<?php echo number_format($row['undertime_deduction'], 2); ?>',
                          '<?php echo number_format($row['late_deduction'], 2); ?>',
                          '<?php echo number_format($row['total_mandatory_deductions'], 2); ?>',
                          '<?php echo number_format($row['withholding_tax'], 2); ?>',
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
                  <td colspan="7" class="text-center py-4 text-muted">No payroll records found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
            <tfoot class="bg-light">
              <tr>
                <th colspan="4" class="text-right">Total Net Salary</th>
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
function viewPayroll(employeeName, period, daysWorked, grossSalary, sss, philhealth, pagibig, overtime, undertime, late, totalMandatory, withholdingTax, deductions, netSalary, status) {
    Swal.fire({
        title: 'Payroll Details',
        html: `
            <div style="text-align:left; font-size: 14px;">
                <p><strong>Employee:</strong> ${employeeName}</p>
                <p><strong>Payroll Period:</strong> ${period}</p>
                <p><strong>Days Worked:</strong> ${daysWorked}</p>
                <hr>
                <p><strong>Gross Salary:</strong> ₱${grossSalary}</p>
                <p><strong>Overtime Pay:</strong> ₱${overtime}</p>
                <p><strong>Undertime Deduction:</strong> ₱${undertime}</p>
                <p><strong>Late Deduction:</strong> ₱${late}</p>
                <hr>
                <p><strong>Mandatory Deductions:</strong></p>
                <ul style="margin-left: 20px;">
                    <li>SSS: ₱${sss}</li>
                    <li>PhilHealth: ₱${philhealth}</li>
                    <li>Pag-IBIG: ₱${pagibig}</li>
                    <li><strong>Total: ₱${totalMandatory}</strong></li>
                </ul>
                <p><strong>Withholding Tax:</strong> ₱${withholdingTax}</p>
                <p><strong>Total Deductions:</strong> ₱${deductions}</p>
                <hr>
                <p><strong>Net Salary:</strong> ₱${netSalary}</p>
                <p><strong>Status:</strong> ${status}</p>
            </div>
        `,
        icon: 'info',
        confirmButtonColor: '#212529',
        width: '600px'
    });
}
</script>   
