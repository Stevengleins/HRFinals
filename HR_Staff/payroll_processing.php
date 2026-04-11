<?php
session_start();
require '../database.php';
require '../includes/PayrollCalculator.php';
require '../includes/PayrollProcessor.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

$processor = new PayrollProcessor($mysql);
$message = '';
$messageType = '';

// =========================================================================
// AJAX & POST HANDLER FOR PAYROLL CALCULATION
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'preview_payroll' || $_POST['action'] === 'process_payroll_confirmed') {
            
            $userId = (int)$_POST['employee_id'];
            $grossSalary = (float)$_POST['gross_salary'];
            $payrollMonth = $_POST['payroll_month']; 
            $payrollHalf = $_POST['payroll_half'];   
            $processedBy = $_SESSION['user_id'] ?? null;

            if (empty($payrollMonth)) throw new Exception("Please select a valid Payroll Month.");
            
            if ($payrollHalf === '1') {
                $startDate = $payrollMonth . '-01';
                $endDate = $payrollMonth . '-15';
            } else {
                $startDate = $payrollMonth . '-16';
                $endDate = date('Y-m-t', strtotime($startDate)); 
            }

            $periodString = "$startDate to $endDate";

            // 1. STRICT DUPLICATE PAYROLL PROTECTION
            $checkDuplicate = $mysql->query("SELECT payroll_id FROM payroll WHERE user_id = $userId AND payroll_period = '$periodString'");
            if ($checkDuplicate && $checkDuplicate->num_rows > 0) {
                $fmtStart = date('M d, Y', strtotime($startDate));
                $fmtEnd = date('M d, Y', strtotime($endDate));
                throw new Exception("<strong>Duplicate Blocked:</strong> Payroll for this employee from $fmtStart to $fmtEnd has <b>already been processed</b>. You cannot process the same period twice.");
            }

            $empQuery = $mysql->query("SELECT shift_start, shift_end FROM employee_details WHERE user_id = $userId");
            $empDetails = $empQuery->fetch_assoc();
            $shiftStart = $empDetails['shift_start'] ?? '08:00:00';
            $shiftEnd = $empDetails['shift_end'] ?? '17:00:00';

            // 2. FETCH PHYSICAL ATTENDANCE
            $attQuery = $mysql->query("
                SELECT date, time_in, time_out 
                FROM attendance 
                WHERE user_id = $userId 
                AND date BETWEEN '$startDate' AND '$endDate' 
                AND time_in IS NOT NULL
            ");

            $attendanceMap = [];
            if ($attQuery && $attQuery->num_rows > 0) {
                while ($att = $attQuery->fetch_assoc()) {
                    if (!empty($att['time_out'])) { 
                        $attendanceMap[$att['date']] = $att;
                    }
                }
            }

            // 3. FETCH APPROVED PAID LEAVES
            $leaveQuery = $mysql->query("
                SELECT leave_type, start_date, end_date 
                FROM leave_requests 
                WHERE user_id = $userId 
                AND status = 'Approved'
                AND start_date <= '$endDate' 
                AND end_date >= '$startDate'
            ");

            $approvedLeaves = [];
            if ($leaveQuery && $leaveQuery->num_rows > 0) {
                while ($l = $leaveQuery->fetch_assoc()) {
                    $approvedLeaves[] = $l;
                }
            }

            $daysPresent = 0;
            $daysOnPaidLeave = 0;
            $totalOtHours = 0;
            $totalLateMins = 0;
            $totalUtMins = 0;
            $regularHours = 0;
            $expectedDays = 0;

            $currentTs = strtotime($startDate);
            $endTs = strtotime($endDate);

            // 4. SCAN EVERY WEEKDAY IN THE PERIOD
            while ($currentTs <= $endTs) {
                if (date('N', $currentTs) < 6) { // Weekdays only
                    $expectedDays++;
                    $dateStr = date('Y-m-d', $currentTs);

                    if (isset($attendanceMap[$dateStr])) {
                        $daysPresent++;
                        $att = $attendanceMap[$dateStr];

                        // FOOLPROOF TIME PARSING
                        $tInStr = date('H:i:s', strtotime($att['time_in']));
                        $tOutStr = date('H:i:s', strtotime($att['time_out']));
                        
                        $tIn = strtotime($dateStr . ' ' . $tInStr);
                        if (strtotime($tOutStr) < strtotime($tInStr)) {
                            $tOut = strtotime($dateStr . ' ' . $tOutStr . ' +1 day');
                        } else {
                            $tOut = strtotime($dateStr . ' ' . $tOutStr);
                        }

                        $sStart = strtotime($dateStr . ' ' . $shiftStart);
                        if (strtotime($shiftEnd) <= strtotime($shiftStart)) {
                            $sEnd = strtotime($dateStr . ' ' . $shiftEnd . ' +1 day');
                        } else {
                            $sEnd = strtotime($dateStr . ' ' . $shiftEnd);
                        }

                        // Lates & Undertimes
                        if ($tIn > $sStart) {
                            $totalLateMins += floor(($tIn - $sStart) / 60);
                        }
                        if ($tOut < $sEnd) {
                            $totalUtMins += floor(($sEnd - $tOut) / 60);
                        }

                        // OVERTIME FIX: Deduct 1 hr lunch if worked more than 5 hours
                        $workedSecs = $tOut - $tIn;
                        if ($workedSecs >= (5 * 3600)) {
                            $workedSecs -= 3600; // 1 hour unpaid break
                        }
                        
                        $workedHours = round($workedSecs / 3600, 2);
                        if ($workedHours > 8) {
                            $regularHours += 8;
                            $totalOtHours += ($workedHours - 8);
                        } else {
                            $regularHours += $workedHours;
                        }
                    } 
                    else {
                        // Check Paid Leaves
                        $isPaidLeave = false;
                        foreach ($approvedLeaves as $leave) {
                            if ($leave['leave_type'] !== 'Unpaid Leave' && $leave['leave_type'] !== 'Unpaid Leave / LWOP') {
                                if ($dateStr >= $leave['start_date'] && $dateStr <= $leave['end_date']) {
                                    $isPaidLeave = true; break;
                                }
                            }
                        }
                        if ($isPaidLeave) $daysOnPaidLeave++;
                    }
                }
                $currentTs = strtotime('+1 day', $currentTs);
            }

            if ($expectedDays == 0) $expectedDays = 1;

            $totalCompensableDays = $daysPresent + $daysOnPaidLeave;
            $daysAbsent = max(0, $expectedDays - $totalCompensableDays);

            if ($totalCompensableDays === 0) {
                $fmtStart = date('M d, Y', strtotime($startDate));
                $fmtEnd = date('M d, Y', strtotime($endDate));
                throw new Exception("<strong>Action Denied:</strong> This employee has 0 attendance and 0 approved paid leaves between $fmtStart and $fmtEnd.");
            }
            
            // 5. MATHEMATICALLY FLAWLESS FINANCIAL FORMULAS
            $dailyRate = round($grossSalary / $expectedDays, 2);
            $hourlyRate = round($dailyRate / 8, 2);
            $minuteRate = round($hourlyRate / 60, 2);

            $actualGross = round($totalCompensableDays * $dailyRate, 2); 
            $otPay = round($totalOtHours * ($hourlyRate * 1.25), 2);
            
            $adjustedGross = $actualGross + $otPay; 

            $lateDed = round($totalLateMins * $minuteRate, 2);
            $utDed = round($totalUtMins * $minuteRate, 2);

            $sss = ($actualGross > 0) ? round($actualGross * 0.045, 2) : 0;
            $philhealth = ($actualGross > 0) ? round($actualGross * 0.025, 2) : 0;
            $pagibig = ($actualGross > 0) ? 200.00 : 0;
            $totalMandatory = $sss + $philhealth + $pagibig;

            $taxableIncome = max(0, $adjustedGross - $lateDed - $utDed - $totalMandatory);
            $tax = ($taxableIncome > 20833) ? round($taxableIncome * 0.10, 2) : 0; 

            $totalDeductions = $totalMandatory + $tax + $lateDed + $utDed;
            $netPay = max(0, $adjustedGross - $totalDeductions);

            $summary = [
                'gross_salary' => $actualGross,
                'daily_rate' => $dailyRate,
                'overtime_pay' => $otPay,
                'undertime_deduction' => $utDed,
                'late_deduction' => $lateDed,
                'adjusted_gross_salary' => $adjustedGross,
                'sss' => ['employee_share' => $sss, 'employer_share' => $sss * 2],
                'philhealth' => ['employee_share' => $philhealth, 'employer_share' => $philhealth],
                'pagibig' => ['employee_share' => $pagibig, 'employer_share' => $pagibig],
                'total_mandatory_deductions' => $totalMandatory,
                'taxable_income' => $taxableIncome,
                'withholding_tax' => $tax,
                'attendance' => [
                    'regular_hours' => $regularHours,
                    'approved_overtime_hours' => $totalOtHours,
                    'days_present' => $daysPresent,
                    'paid_leave_days' => $daysOnPaidLeave,
                    'days_absent' => $daysAbsent
                ],
                'deductions' => 0, 
                'total_deductions' => $totalDeductions,
                'net_take_home_pay' => $netPay,
                'employer_contributions' => ($sss * 2) + $philhealth + $pagibig
            ];

            // AJAX PREVIEW RESPONSE
            if ($_POST['action'] === 'preview_payroll') {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success', 
                    'data' => $summary, 
                    'fmt_period' => date('M d, Y', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate))
                ]);
                exit();
            }

            // ACTUAL DB SAVE RESPONSE
            if ($_POST['action'] === 'process_payroll_confirmed') {
                $payrollId = $processor->savePayrollRecord($userId, $startDate, $endDate, $summary, 'Pending', $processedBy);
                $message = "Payroll processed successfully! Ref ID: #" . $payrollId;
                $messageType = 'success';
            }

        }
    } catch (Exception $e) {
        if (isset($_POST['action']) && $_POST['action'] === 'preview_payroll') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

$title = "Payroll Processing | WorkForcePro";
include '../includes/hr_header.php';

$employeesQuery = $mysql->query("SELECT user_id, first_name, last_name FROM user WHERE role = 'Employee' ORDER BY first_name ASC");

$recentPayrollQuery = $mysql->query("
    SELECT p.payroll_id, p.user_id, p.payroll_period, p.gross_salary, 
           p.net_salary, p.status, p.date_created, u.first_name, u.last_name
    FROM payroll p
    JOIN user u ON p.user_id = u.user_id
    ORDER BY p.date_created DESC
    LIMIT 100
");
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<style>
    .table-custom thead th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; border-top: none; color: #495057; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
    .table-custom td { vertical-align: middle !important; border-top: 1px solid #e9ecef; font-size: 0.95rem; }
    .text-success-custom { color: #28a745 !important; font-weight: bold; }
    .text-danger-custom { color: #dc3545 !important; font-weight: bold; }
    .ledger-table td { padding: 12px 15px; font-size: 14px; border-bottom: 1px solid #e9ecef; }
    .ledger-table .sub-header { background-color: #f8f9fa; font-weight: bold; font-size: 13px; color: #212529; text-transform: uppercase; }
</style>

<div class="content-header pb-2">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Payroll Processing</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="payrollhr.php" class="btn btn-outline-dark btn-sm shadow-sm font-weight-bold px-3" style="border-radius: 4px;">
          <i class="fas fa-arrow-left mr-1"></i> Back to Payroll List
        </a>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <?php if ($message && $messageType !== 'success'): ?>
      <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="border-left: 4px solid #dc3545; background: #fff; color: #333;">
        <i class="fas fa-exclamation-circle text-danger mr-2"></i>
        <span><?php echo $message; ?></span>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;"><i class="fas fa-calculator mr-2"></i> Calculate Employee Payroll</h3>
      </div>
      <div class="card-body bg-light">
        
        <form method="POST" action="" id="payrollForm" class="needs-validation" novalidate>
          <input type="hidden" name="action" value="preview_payroll">

          <div class="row">
            <div class="col-md-4 form-group">
              <label for="employee_id" class="font-weight-bold text-dark">Select Employee <span class="text-danger">*</span></label>
              <select name="employee_id" id="employee_id" class="form-control shadow-sm" required>
                <option value="" disabled selected>-- Choose an Employee --</option>
                <?php if ($employeesQuery && $employeesQuery->num_rows > 0): ?>
                  <?php while ($emp = $employeesQuery->fetch_assoc()): ?>
                    <option value="<?php echo $emp['user_id']; ?>">
                      <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
              <div class="invalid-feedback">Please select an employee.</div>
            </div>

            <div class="col-md-4 form-group">
              <label for="gross_salary" class="font-weight-bold text-dark">Gross Salary Base (₱) <span class="text-danger">*</span></label>
              <input type="number" name="gross_salary" id="gross_salary" class="form-control shadow-sm" step="0.01" min="1" placeholder="e.g. 15000" required>
              <div class="invalid-feedback">Valid gross base is required.</div>
              <small class="form-text text-muted">The foundational salary before attendance formulas.</small>
            </div>

            <div class="col-md-2 form-group">
              <label for="payroll_month" class="font-weight-bold text-dark">Payroll Month <span class="text-danger">*</span></label>
              <input type="month" name="payroll_month" id="payroll_month" class="form-control shadow-sm" value="<?php echo date('Y-m'); ?>" required>
              <div class="invalid-feedback">Select a month.</div>
            </div>

            <div class="col-md-2 form-group">
              <label for="payroll_half" class="font-weight-bold text-dark">Payroll Period <span class="text-danger">*</span></label>
              <select name="payroll_half" id="payroll_half" class="form-control shadow-sm" required>
                <option value="1">1st Half (1st-15th)</option>
                <option value="2">2nd Half (16th-End)</option>
              </select>
              <div class="invalid-feedback">Select a period.</div>
            </div>
          </div>

          <hr class="mt-2 mb-4" style="opacity: 0.1;">

          <div class="form-group mb-0 text-right">
            <button type="reset" class="btn btn-outline-secondary font-weight-bold px-4 mr-2" style="border-radius: 4px;">
              <i class="fas fa-redo mr-1"></i> Reset
            </button>
            <button type="submit" class="btn btn-dark font-weight-bold px-4 shadow-sm" style="border-radius: 4px;">
              <i class="fas fa-search mr-1"></i> Scan & Preview Calculation
            </button>
          </div>
        </form>

      </div>
    </div>

    <div class="card shadow-sm border-0 mb-5" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;"><i class="fas fa-history mr-2"></i> Recent Processing Log</h3>
      </div>
      <div class="card-body p-0 bg-white">
        <div class="table-responsive p-3">
          <table id="recentTable" class="table table-hover table-custom w-100 text-center align-middle">
            <thead>
              <tr>
                <th class="text-left">Employee Name</th>
                <th>Payroll Period</th>
                <th>Gross Salary</th>
                <th>Net Salary</th>
                <th>Date Processed</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recentPayrollQuery && $recentPayrollQuery->num_rows > 0): ?>
                <?php while ($row = $recentPayrollQuery->fetch_assoc()): 
                    $raw_period = $row['payroll_period'];
                    $p_parts = explode(' to ', $raw_period);
                    $fmt_period = (count($p_parts) === 2) 
                        ? date('M d, Y', strtotime($p_parts[0])) . ' - ' . date('M d, Y', strtotime($p_parts[1])) 
                        : htmlspecialchars($raw_period);
                ?>
                  <tr>
                    <td class="font-weight-bold text-dark text-left"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo $fmt_period; ?></td>
                    <td class="text-success-custom">₱<?php echo number_format($row['gross_salary'], 2); ?></td>
                    <td class="text-success-custom" style="font-size: 1.05rem;">₱<?php echo number_format($row['net_salary'], 2); ?></td>
                    <td class="text-muted"><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                    <td>
                      <?php if ($row['status'] === 'Released'): ?>
                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Released</span>
                      <?php else: ?>
                        <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-clock mr-1"></i> Pending</span>
                      <?php endif; ?>
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
      $('#recentTable').DataTable({
          "responsive": true, 
          "lengthChange": true, 
          "autoWidth": false,
          "searching": true,
          "ordering": true,
          "pageLength": 10,
          "order": [[ 4, "desc" ]], 
          "language": { "search": "_INPUT_", "searchPlaceholder": "Search records..." }
      });

      $('#payrollForm').on('submit', function(e) {
          e.preventDefault();
          const form = this;

          if (!form.checkValidity()) {
              e.stopPropagation();
              form.classList.add('was-validated');
              return;
          }
          
          Swal.fire({
              title: 'Scan & Calculate?',
              text: 'The system will now scan the attendance logs and calculate deductions.',
              icon: 'question',
              showCancelButton: true,
              confirmButtonColor: '#212529',
              cancelButtonColor: '#6c757d',
              confirmButtonText: '<i class="fas fa-search"></i> Yes, Scan it!'
          }).then((result) => {
              if (result.isConfirmed) {
                  
                  const btn = $(form).find('button[type="submit"]');
                  btn.prop('disabled', true);
                  let formData = new FormData(form);

                  Swal.fire({
                      title: 'Scanning Attendance...',
                      text: 'Analyzing timecards and calculating deductions...',
                      allowOutsideClick: false,
                      didOpen: () => { Swal.showLoading(); }
                  });

                  fetch('payroll_processing.php', {
                      method: 'POST',
                      body: formData
                  })
                  .then(res => res.json())
                  .then(data => {
                      btn.prop('disabled', false);

                      if (data.status === 'error') {
                          Swal.fire({ 
                              icon: 'error', 
                              title: 'Calculation Aborted', 
                              html: data.message, 
                              confirmButtonColor: '#212529' 
                          });
                      } else {
                          let s = data.data; 
                          const formatMoney = (num) => '₱' + parseFloat(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                          let timeDeductions = parseFloat(s.late_deduction) + parseFloat(s.undertime_deduction);
                          let taxAndMandatory = parseFloat(s.total_mandatory_deductions) + parseFloat(s.withholding_tax);
                          let totalDeds = parseFloat(s.total_deductions);

                          let overviewHtml = `
                              <div style="text-align: left; font-size: 14px;">
                                  <div style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                                      <span class="text-muted">Period:</span> <strong>${data.fmt_period}</strong>
                                  </div>
                                  
                                  <table class="table table-sm table-borderless" style="margin-bottom:0;">
                                      <tr><td class="text-muted">Base Gross Selected:</td><td class="text-right text-muted">${formatMoney(formData.get('gross_salary'))}</td></tr>
                                      <tr><td>Days Present:</td><td class="text-right"><strong>${s.attendance.days_present}</strong></td></tr>
                                      <tr><td>Paid Leaves:</td><td class="text-right text-success"><strong>+${s.attendance.paid_leave_days}</strong></td></tr>
                                      <tr><td>Unpaid Absences:</td><td class="text-right text-danger"><strong>${s.attendance.days_absent}</strong></td></tr>
                                      
                                      <tr style="border-top:1px dashed #ccc;"><td class="pt-2"><b>Actual Basic Earned:</b></td><td class="text-right text-success pt-2"><b>${formatMoney(s.gross_salary)}</b></td></tr>
                                      <tr><td>Overtime Pay:</td><td class="text-right text-success">+${formatMoney(s.overtime_pay)}</td></tr>
                                      
                                      <tr style="border-top:1px dashed #ccc;"><td class="pt-2 text-dark"><b>Total Earnings:</b></td><td class="text-right text-dark pt-2"><b>${formatMoney(s.adjusted_gross_salary)}</b></td></tr>
                                      
                                      <tr><td>Late/Undertime Ded:</td><td class="text-right text-danger">-${formatMoney(timeDeductions)}</td></tr>
                                      <tr><td>Mandatory/Tax Ded:</td><td class="text-right text-danger">-${formatMoney(taxAndMandatory)}</td></tr>
                                      
                                      <tr style="border-top:1px dashed #ccc;"><td class="pt-2 text-danger"><b>Total Deductions:</b></td><td class="text-right text-danger pt-2"><b>-${formatMoney(totalDeds)}</b></td></tr>
                                  </table>
                                  
                                  <div style="background: #212529; color: #fff; padding: 15px; border-radius: 4px; text-align: right; margin-top: 15px;">
                                      <p style="margin:0; font-size:11px; text-transform:uppercase; opacity:0.8;">Final Net Take-Home Pay</p>
                                      <h4 style="margin:0; font-size:24px; font-weight:bold;">${formatMoney(s.net_take_home_pay)}</h4>
                                  </div>
                              </div>
                          `;

                          Swal.fire({
                              title: 'Payroll Overview',
                              html: overviewHtml,
                              icon: 'info',
                              showCancelButton: true,
                              confirmButtonColor: '#28a745',
                              cancelButtonColor: '#6c757d',
                              confirmButtonText: '<i class="fas fa-check-circle mr-1"></i> Confirm & Process',
                              cancelButtonText: 'Cancel'
                          }).then((result) => {
                              if (result.isConfirmed) {
                                  Swal.fire({
                                      title: 'Saving Database Record...',
                                      allowOutsideClick: false,
                                      didOpen: () => { Swal.showLoading(); }
                                  });
                                  
                                  let actionInput = form.querySelector('input[name="action"]');
                                  actionInput.value = 'process_payroll_confirmed';
                                  form.submit();
                              }
                          });
                      }
                  })
                  .catch(err => {
                      btn.prop('disabled', false);
                      Swal.fire('Error', 'A server error occurred. Please check console logs.', 'error');
                  });
              }
          });
      });
  });
</script>

<?php if (!empty($message)): ?>
<script>
    Swal.fire({
        icon: '<?php echo $messageType === 'success' ? 'success' : 'error'; ?>',
        title: '<?php echo $messageType === 'success' ? 'Processed!' : 'Error!'; ?>',
        html: '<?php echo addslashes($message); ?>',
        confirmButtonColor: '#212529'
    });
</script>
<?php endif; ?>