<?php
session_start();
require '../database.php';

// HR Staff only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

date_default_timezone_set('Asia/Manila');

// Filter by selected date, default = today
$selectedDate = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Summary counts
$totalEmployeesQuery = $mysql->query("SELECT COUNT(user_id) AS total FROM user WHERE role = 'Employee'");
$totalEmployees = $totalEmployeesQuery->fetch_assoc()['total'] ?? 0;

$presentQuery = $mysql->query("SELECT COUNT(id) AS total_present FROM attendance WHERE date = '$selectedDate'");
$totalPresent = $presentQuery->fetch_assoc()['total_present'] ?? 0;

$totalAbsent = max(0, $totalEmployees - $totalPresent);

// Attendance records
$query = "
    SELECT 
        a.*,
        u.first_name,
        u.last_name
    FROM attendance a
    JOIN user u ON a.user_id = u.user_id
    WHERE u.role = 'Employee' AND a.date = '$selectedDate'
    ORDER BY a.time_in ASC
";

$result = $mysql->query($query);

$title = "Attendance | WorkForcePro";
include '../includes/hr_header.php';
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Attendance</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <!-- Summary Cards -->
    <div class="row mb-3">
      <div class="col-12 col-sm-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-users"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Employees</span>
            <span class="info-box-number text-lg"><?php echo $totalEmployees; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-success elevation-1"><i class="fas fa-user-check"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Present</span>
            <span class="info-box-number text-lg"><?php echo $totalPresent; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-user-times"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Absent</span>
            <span class="info-box-number text-lg"><?php echo $totalAbsent; ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Attendance Table -->
    <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-clipboard-check mr-2"></i> Employee Attendance Records
        </h3>

        <form method="GET" class="form-inline m-0">
          <input 
            type="date" 
            name="date" 
            value="<?php echo htmlspecialchars($selectedDate); ?>" 
            class="form-control form-control-sm mr-2"
            required
          >
          <button type="submit" class="btn btn-sm btn-outline-light">Filter</button>
        </form>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped m-0 text-center align-middle">
            <thead class="bg-light">
              <tr>
                <th>Employee Name</th>
                <th>Date</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <?php
                    $timeIn = !empty($row['time_in']) ? date('g:i A', strtotime($row['time_in'])) : '--';
                    $timeOut = !empty($row['time_out']) ? date('g:i A', strtotime($row['time_out'])) : '--';

                    $status = 'Present';
                    if (!empty($row['time_in'])) {
                        $timeInOnly = strtotime($row['time_in']);
                        $lateThreshold = strtotime($selectedDate . ' 08:00:00');
                        if ($timeInOnly > $lateThreshold) {
                            $status = 'Late';
                        }
                    }
                  ?>
                  <tr>
                    <td class="font-weight-bold text-left pl-4">
                      <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                    <td><?php echo $timeIn; ?></td>
                    <td><?php echo $timeOut; ?></td>
                    <td>
                      <?php if ($status === 'Late'): ?>
                        <span class="badge badge-warning px-3 py-2 text-dark">Late</span>
                      <?php else: ?>
                        <span class="badge badge-success px-3 py-2">Present</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">
                    No attendance records found for <?php echo date('F d, Y', strtotime($selectedDate)); ?>.
                  </td>
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