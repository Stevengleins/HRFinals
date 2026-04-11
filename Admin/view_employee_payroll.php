<?php
session_start();
require '../database.php';

// Load Composer's autoloader for PHPMailer
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: payroll_admin.php");
    exit();
}

$user_id = intval($_GET['id']);

// =========================================================
// HANDLE RELEASE PAYROLL & SEND EMAIL LOGIC
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['release_payroll_id'])) {
    $releasePayrollId = intval($_POST['release_payroll_id']);
    $adminId = $_SESSION['user_id'];
    
    $releaseStmt = $mysql->prepare("UPDATE payroll SET status = 'Released', processed_by = ?, processed_date = NOW() WHERE payroll_id = ? AND user_id = ?");
    if ($releaseStmt) {
        $releaseStmt->bind_param('iii', $adminId, $releasePayrollId, $user_id);
        $releaseStmt->execute();
        $releaseStmt->close();
    }

    $emailQuery = $mysql->query("
        SELECT p.*, u.first_name, u.last_name, u.email 
        FROM payroll p 
        JOIN user u ON p.user_id = u.user_id 
        WHERE p.payroll_id = $releasePayrollId
    ");
    $slipData = $emailQuery->fetch_assoc();
    
    $empName = htmlspecialchars($slipData['first_name'] . ' ' . $slipData['last_name']);
    $empEmail = $slipData['email'];
    
    // FORMAT THE PAYROLL PERIOD DATE STRING
    $raw_period = $slipData['payroll_period'];
    $p_parts = explode(' to ', $raw_period);
    $payPeriod = (count($p_parts) === 2) 
        ? date('M d, Y', strtotime($p_parts[0])) . ' - ' . date('M d, Y', strtotime($p_parts[1])) 
        : htmlspecialchars($raw_period);
        
    $dateGenerated = date('M d, Y', strtotime($slipData['date_created']));
    
    // MATHEMATICALLY FLAWLESS EMAIL MATH
    $total_earnings_num = $slipData['gross_salary'] + $slipData['overtime_pay'];
    $net_salary_num = $slipData['net_salary'];
    $total_deductions_num = max(0, $total_earnings_num - $net_salary_num);

    $basicPay = number_format($slipData['gross_salary'], 2);
    $overtime = number_format($slipData['overtime_pay'], 2);
    $gross = number_format($total_earnings_num, 2);
    
    $sss = number_format($slipData['sss_employee_share'], 2);
    $philhealth = number_format($slipData['philhealth_employee_share'], 2);
    $pagibig = number_format($slipData['pagibig_employee_share'], 2);
    $tax = number_format($slipData['withholding_tax'] ?? 0, 2);
    $lates = number_format($slipData['late_deduction'] + $slipData['undertime_deduction'], 2);
    $otherDed = number_format($slipData['deductions'], 2);
    
    $totalDeductions = number_format($total_deductions_num, 2);
    $net = number_format($net_salary_num, 2);

    // CRISP PURE WHITE EMAIL TEMPLATE (NOW WITH EMBEDDED LOGO)
    $emailBody = "
    <div style='background-color: #ffffff; padding: 30px; font-family: Helvetica, Arial, sans-serif;'>
        <div style='max-width: 800px; margin: 0 auto; border: 1px solid #cccccc; background-color: #ffffff;'>
            
            <table width='100%' cellpadding='20' cellspacing='0' style='border-bottom: 2px solid #222222; background-color: #ffffff;'>
                <tr>
                    <td align='left' valign='middle'>
                        <img src='cid:logo' alt='WORKFORCEPRO' style='opacity: 1; max-height: 35px; border-radius: 4px; vertical-align: middle; margin-right: 8px;' />
                        <h2 style='margin: 0; font-size: 22px; color: #111111; letter-spacing: 1px; display: inline-block; vertical-align: middle;'>
                            <strong>WORK</strong><span style='font-weight: normal;'>FORCEPRO</span>
                        </h2>
                        <p style='margin: 5px 0 0 0; font-size: 12px; color: #555555;'>Official Corporate Records</p>
                    </td>
                    <td align='right' valign='middle'>
                        <h2 style='margin: 0; font-size: 20px; color: #333333;'>Statement of Earning & Deductions</h2>
                    </td>
                </tr>
            </table>

            <table width='100%' cellpadding='10' cellspacing='0' style='border-bottom: 1px solid #cccccc; background-color: #ffffff;'>
                <thead style='background-color: #ffffff; color: #111111;'>
                    <tr>
                        <th align='left' style='font-size: 13px; font-weight: bold; border-right: 1px solid #cccccc; border-bottom: 1px solid #cccccc;'>Employee Name & Email</th>
                        <th align='center' style='font-size: 13px; font-weight: bold; border-right: 1px solid #cccccc; border-bottom: 1px solid #cccccc;'>Pay Period</th>
                        <th align='center' style='font-size: 13px; font-weight: bold; border-bottom: 1px solid #cccccc;'>Date Generated</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td align='left' style='font-size: 13px; border-right: 1px solid #cccccc; padding: 15px 10px; color: #111;'>
                            <strong>{$empName}</strong><br><span style='color: #555;'>{$empEmail}</span>
                        </td>
                        <td align='center' style='font-size: 13px; border-right: 1px solid #cccccc; padding: 15px 10px; color: #111;'>{$payPeriod}</td>
                        <td align='center' style='font-size: 13px; padding: 15px 10px; color: #111;'>{$dateGenerated}</td>
                    </tr>
                </tbody>
            </table>

            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #ffffff;'>
                <tr>
                    <td width='50%' valign='top' style='border-right: 1px solid #cccccc;'>
                        <table width='100%' cellpadding='10' cellspacing='0'>
                            <thead style='background-color: #ffffff; color: #111111;'>
                                <tr>
                                    <th align='left' style='font-size: 13px; font-weight: bold; border-bottom: 1px solid #cccccc;'>Gross Earnings</th>
                                    <th align='right' style='font-size: 13px; font-weight: bold; border-bottom: 1px solid #cccccc;'>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style='font-size: 13px; padding: 12px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>Basic Pay ({$slipData['days_worked']} Days)</td>
                                    <td align='right' style='font-size: 13px; padding: 12px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>+ ₱{$basicPay}</td>
                                </tr>
                                <tr>
                                    <td style='font-size: 13px; padding: 12px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>Overtime Pay</td>
                                    <td align='right' style='font-size: 13px; padding: 12px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>+ ₱{$overtime}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    
                    <td width='50%' valign='top'>
                        <table width='100%' cellpadding='10' cellspacing='0'>
                            <thead style='background-color: #ffffff; color: #111111;'>
                                <tr>
                                    <th align='left' style='font-size: 13px; font-weight: bold; border-bottom: 1px solid #cccccc;'>Statutory Deductions</th>
                                    <th align='right' style='font-size: 13px; font-weight: bold; border-bottom: 1px solid #cccccc;'>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>FICA - SSS</td><td align='right' style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>- ₱{$sss}</td></tr>
                                <tr><td style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>FICA - PhilHealth</td><td align='right' style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>- ₱{$philhealth}</td></tr>
                                <tr><td style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>FICA - Pag-IBIG</td><td align='right' style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>- ₱{$pagibig}</td></tr>
                                <tr><td style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>Withholding Tax</td><td align='right' style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>- ₱{$tax}</td></tr>
                                <tr><td style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>Lates / Undertime</td><td align='right' style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>- ₱{$lates}</td></tr>
                                <tr><td style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>Other Deductions</td><td align='right' style='font-size: 13px; padding: 8px 10px; border-bottom: 1px solid #eeeeee; color: #111;'>- ₱{$otherDed}</td></tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            <table width='100%' cellpadding='15' cellspacing='0' style='background-color: #ffffff; border-top: 2px solid #222222;'>
                <tr>
                    <td align='center' style='font-size: 13px; border-right: 1px solid #cccccc; color: #111;'>
                        <strong>Total Earnings</strong><br><span style='font-size: 16px;'>₱{$gross}</span>
                    </td>
                    <td align='center' style='font-size: 13px; border-right: 1px solid #cccccc; color: #111;'>
                        <strong>Total Deductions</strong><br><span style='font-size: 16px;'>- ₱{$totalDeductions}</span>
                    </td>
                    <td align='center' style='font-size: 14px; color: #111;'>
                        <strong>Final Net Pay</strong><br><span style='font-size: 18px; font-weight: bold;'>₱{$net}</span>
                    </td>
                </tr>
            </table>
            
            <div style='padding: 20px; text-align: center; font-size: 11px; color: #888888; background-color: #ffffff;'>
                This is an automatically generated electronic statement. Do not reply to this email.
            </div>
        </div>
    </div>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'samontetn@gmail.com'; 
        $mail->Password   = 'knekilwtrlbjcfkw';    
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('samontetn@gmail.com', 'WorkForcePro Admin'); 
        $mail->addAddress($empEmail, $empName);

        $mail->isHTML(true);
        $mail->Subject = "Official Payslip Released: $payPeriod";
        $mail->Body    = $emailBody;

        // EMBED THE LOGO FOR THE EMAIL
        $mail->addEmbeddedImage('../logo.png', 'logo');

        $mail->send();
        
        $_SESSION['status_icon'] = 'success';
        $_SESSION['status_title'] = 'Payroll Released!';
        $_SESSION['status_text'] = "The payslip has been finalized and emailed to $empName.";
    } catch (Exception $e) {
        $_SESSION['status_icon'] = 'warning';
        $_SESSION['status_title'] = 'Released, but Email Failed';
        $_SESSION['status_text'] = "Payroll released, but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

    header("Location: view_employee_payroll.php?id=$user_id");
    exit();
}

$userQuery = $mysql->query("SELECT first_name, last_name FROM user WHERE user_id = $user_id");
$user = $userQuery->fetch_assoc();
$employeeName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);

$payrollQuery = $mysql->query("
    SELECT 
        payroll_id, payroll_period, days_worked, daily_rate, gross_salary,
        sss_employee_share, philhealth_employee_share, pagibig_employee_share,
        overtime_pay, undertime_deduction, late_deduction,
        total_mandatory_deductions, withholding_tax, deductions,
        net_salary, status, date_created
    FROM payroll 
    WHERE user_id = $user_id 
    ORDER BY payroll_id DESC
");

$summaryStmt = $mysql->prepare("
    SELECT 
        COUNT(*) AS total_records,
        SUM(CASE WHEN status = 'Released' THEN 1 ELSE 0 END) AS released_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
        COALESCE(SUM(net_salary), 0) AS total_net_salary,
        COALESCE(SUM(gross_salary + overtime_pay), 0) AS total_gross_salary
    FROM payroll
    WHERE user_id = ?
");
$summaryStmt->bind_param('i', $user_id);
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();

$totalRecords = $summary['total_records'] ?? 0;
$releasedCount = $summary['released_count'] ?? 0;
$pendingCount = $summary['pending_count'] ?? 0;
$totalNetSalary = $summary['total_net_salary'] ?? 0;
$totalGrossSalary = $summary['total_gross_salary'] ?? 0;

$title = "Employee Payroll | WorkForcePro";
include '../includes/admin_header.php';
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
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Payroll History</h1>
        <p class="text-muted mb-0">Employee: <?php echo $employeeName; ?></p>
      </div>
      <div class="col-sm-6 text-right">
        <a href="payroll_admin.php" class="btn btn-outline-dark btn-sm shadow-sm font-weight-bold px-3" style="border-radius: 4px;">
          <i class="fas fa-arrow-left mr-1"></i> Back to Roster
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
          <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Records</span>
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
            <span class="info-box-text font-weight-bold">Total Net Paid</span>
            <span class="info-box-number text-lg text-primary">₱<?php echo number_format($totalNetSalary, 0); ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mt-4 mb-5" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-list-ul mr-2"></i> Payroll Records
        </h3>
        <div class="btn-group shadow-sm">
            <a href="export_payroll.php?type=single&user_id=<?php echo $user_id; ?>" class="btn btn-sm btn-light border-0"><i class="fas fa-file-csv text-success mr-1"></i> CSV</a>
            <a href="export_pdf.php?type=payroll&user_id=<?php echo $user_id; ?>" target="_blank" class="btn btn-sm btn-light border-left border-0"><i class="fas fa-file-pdf text-danger mr-1"></i> PDF</a>
        </div>
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
                    <td class="font-weight-bold text-dark text-left"><?php echo $fmt_period; ?></td>
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
                        <button class="btn btn-sm btn-outline-dark font-weight-bold shadow-sm w-100"
                            onclick="viewPayslip(
                              <?php echo (int)$row['payroll_id']; ?>,
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
                              '<?php echo number_format($total_earnings, 2); ?>',
                              '<?php echo number_format($total_deductions, 2); ?>',
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
          "order": [[ 4, "desc" ]], 
          "language": { "search": "_INPUT_", "searchPlaceholder": "Search records..." }
      });
  });

  function viewPayslip(payrollId, employeeName, period, dateGenerated, daysWorked, grossSalary, sss, philhealth, pagibig, overtime, lates, withholdingTax, deductions, netSalary, totalEarnings, totalDeductions, status) {
      
      let statusBadge = status === 'Released' 
          ? `<span style="color: #28a745; font-weight: bold; border: 2px solid #28a745; padding: 2px 8px; border-radius: 4px; font-size: 12px; letter-spacing: 1px;">RELEASED</span>` 
          : `<span style="color: #ffc107; font-weight: bold; border: 2px solid #ffc107; padding: 2px 8px; border-radius: 4px; font-size: 12px; letter-spacing: 1px;">PENDING</span>`;

      let releaseButtonHTML = '';
      if (status !== 'Released') {
          releaseButtonHTML = `
              <div style="margin-top: 20px; text-align: center;">
                  <button type="button" class="btn btn-success font-weight-bold px-4 py-2 shadow-sm w-100" onclick="confirmRelease(${payrollId})">
                      <i class="fas fa-paper-plane mr-1"></i> Release & Email Payslip
                  </button>
              </div>
          `;
      }

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

                  ${releaseButtonHTML}
              </div>
          `,
          icon: null,
          showConfirmButton: false,
          showCloseButton: true,
          width: '600px',
          customClass: { popup: 'rounded-0' }
      });
  }

  function confirmRelease(payrollId) {
      Swal.fire({
          title: 'Release & Email Payslip?',
          text: 'Are you sure you want to finalize this payroll? The employee will instantly receive a formal email containing their Statement of Earnings and Deductions.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#28a745',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, Release and Send!'
      }).then((result) => {
          if (result.isConfirmed) {
              Swal.fire({
                  title: 'Sending Email...',
                  html: 'Please wait while the server connects to SMTP.',
                  allowOutsideClick: false,
                  didOpen: () => { Swal.showLoading(); }
              });
              
              const form = document.createElement('form');
              form.method = 'POST';
              form.action = window.location.href; 
              
              const input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'release_payroll_id';
              input.value = payrollId;
              
              form.appendChild(input);
              document.body.appendChild(form);
              form.submit();
          }
      });
  }
</script>

<?php
if (isset($_SESSION['status_icon'])) {
    echo "<script>
        Swal.fire({
            icon: '{$_SESSION['status_icon']}', 
            title: '{$_SESSION['status_title']}', 
            text: '{$_SESSION['status_text']}', 
            confirmButtonColor: '#212529'
        });
    </script>";
    unset($_SESSION['status_icon'], $_SESSION['status_title'], $_SESSION['status_text']);
}
?>