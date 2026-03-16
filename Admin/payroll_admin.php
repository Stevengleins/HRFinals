<?php
session_start();
require '../database.php';

// Restrict access to Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$title = "Admin Payroll Management | WorkForcePro";
include '../includes/admin_header.php'; // Ensure this points to your admin layout

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

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Admin Payroll Overview</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="export_payroll.php?type=all" class="btn btn-success shadow-sm">
          <i class="fas fa-file-excel mr-1"></i> Export All Data to Excel
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
          <i class="fas fa-users mr-2"></i> Employee Roster
        </h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped m-0 text-center align-middle">
            <thead class="bg-light">
              <tr>
                <th>Employee Name</th>
                <th>Total Payroll Records</th>
                <th>Total Lifetime Paid</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($employeesQuery && $employeesQuery->num_rows > 0): ?>
                <?php while($row = $employeesQuery->fetch_assoc()): ?>
                  <tr>
                    <td class="font-weight-bold"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo (int)$row['total_records']; ?></td>
                    <td class="text-success font-weight-bold">₱<?php echo number_format($row['total_paid'] ?? 0, 2); ?></td>
                    <td>
                      <a href="view_employee_payroll.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> View Payroll
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="4" class="text-center py-4 text-muted">No employees found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>