<?php
session_start();
require '../database.php';

// Restrict access to Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$title = "Admin Payroll Management | WorkForcePro";
include '../includes/admin_header.php'; 

// Summary Metrics
$totalEmployeesQuery = $mysql->query("SELECT COUNT(user_id) AS total FROM user WHERE role = 'Employee'");
$totalEmployees = $totalEmployeesQuery->fetch_assoc()['total'] ?? 0;

$processedQuery = $mysql->query("SELECT COUNT(payroll_id) AS total FROM payroll WHERE status = 'Released'");
$processedPayroll = $processedQuery->fetch_assoc()['total'] ?? 0;

$totalAmountQuery = $mysql->query("SELECT COALESCE(SUM(net_salary), 0) AS total_amount FROM payroll WHERE status = 'Released'");
$totalPayrollAmount = $totalAmountQuery->fetch_assoc()['total_amount'] ?? 0;

// Get Employees with a summary of their payroll
$employeesQuery = $mysql->query("
    SELECT u.user_id, u.first_name, u.last_name, 
           COUNT(p.payroll_id) as total_records,
           SUM(CASE WHEN p.status = 'Released' THEN p.net_salary ELSE 0 END) as total_paid
    FROM user u
    LEFT JOIN payroll p ON u.user_id = p.user_id
    WHERE u.role = 'Employee'
    GROUP BY u.user_id
    ORDER BY u.first_name ASC
");
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<style>
    /* Premium Table Styling */
    .table-custom thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        border-top: none;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .table-custom td {
        vertical-align: middle !important;
        border-top: 1px solid #e9ecef;
        font-size: 0.95rem;
    }
</style>

<div class="content-header pb-2">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Payroll Management</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="card shadow-sm border-0 mb-5" style="border-radius: 8px; overflow: hidden;">
      
      <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-file-invoice-dollar mr-2"></i> Employee Payroll Roster
        </h6>
        
        <div class="btn-group shadow-sm">
            <a href="export_payroll.php?type=all" class="btn btn-sm btn-light border-0" title="Export Payroll to CSV">
                <i class="fas fa-file-csv text-success mr-1"></i> CSV
            </a>
            <a href="export_pdf.php?type=payroll" target="_blank" class="btn btn-sm btn-light border-left border-0" title="Export Payroll to PDF">
                <i class="fas fa-file-pdf text-danger mr-1"></i> PDF
            </a>
        </div>
      </div>
      
      <div class="card-body p-0 bg-white">
        <div class="p-3 table-responsive">
          <table id="payrollTable" class="table table-hover table-custom w-100 text-center">
            <thead>
              <tr>
                <th class="text-left">Employee Name</th>
                <th>Total Payroll Records</th>
                <th>Total Lifetime Paid</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($employeesQuery && $employeesQuery->num_rows > 0): ?>
                <?php while($row = $employeesQuery->fetch_assoc()): ?>
                  <tr>
                    <td class="font-weight-bold text-dark text-left align-middle">
                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                    </td>
                    <td class="align-middle"><?php echo (int)$row['total_records']; ?> Records</td>
                    <td class="text-success font-weight-bold align-middle">
                        ₱ <?php echo number_format($row['total_paid'] ?? 0, 2); ?>
                    </td>
                    <td class="align-middle">
                      <a href="view_employee_payroll.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-dark shadow-sm font-weight-bold px-3">
                        <i class="fas fa-search mr-1"></i> Review
                      </a>
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

<script>
  $(document).ready(function () {
      // Initialize Premium DataTables
      $('#payrollTable').DataTable({
          "responsive": true, 
          "lengthChange": true, 
          "autoWidth": false,
          "searching": true,
          "ordering": true,
          "pageLength": 10,
          "order": [[ 0, "asc" ]], // Sort alphabetically by Employee Name by default
          "language": {
              "search": "_INPUT_",
              "searchPlaceholder": "Search employees..."
          }
      });
  });
</script>
