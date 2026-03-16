<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$attendanceQuery = $mysql->prepare("
    SELECT a.*, u.first_name, u.last_name
    FROM attendance a
    JOIN user u ON a.user_id = u.user_id
    WHERE a.date = ?
    ORDER BY a.time_in ASC
");
$attendanceQuery->bind_param("s", $filter_date);
$attendanceQuery->execute();
$attendanceResult = $attendanceQuery->get_result();
$attendanceRecords = $attendanceResult->fetch_all(MYSQLI_ASSOC);
$attendanceQuery->close();

$title = "Attendance Management | WorkForcePro";
include('../includes/hr_header.php');
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Attendance Management</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
          <div class="card-header bg-dark text-white py-3 border-bottom-0">
            <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
              <i class="fas fa-calendar-check mr-2"></i> Daily Attendance Records
            </h3>
          </div>

          <div class="card-body bg-light">
            <!-- Date Filter -->
            <form method="GET" action="attendance.php" class="mb-4">
              <div class="row">
                <div class="col-md-4">
                  <label class="text-dark">Select Date</label>
                  <input type="date" name="date" class="form-control shadow-sm" value="<?php echo htmlspecialchars($filter_date); ?>" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button type="submit" class="btn btn-dark shadow-sm px-4" style="border-radius: 6px;">
                    <i class="fas fa-search mr-1"></i> Filter
                  </button>
                </div>
              </div>
            </form>

            <!-- Attendance Table -->
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead class="thead-dark">
                  <tr>
                    <th>Employee Name</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($attendanceRecords)): ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-info-circle mr-2"></i> No attendance records found for <?php echo date('F j, Y', strtotime($filter_date)); ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($attendanceRecords as $record): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                        <td><?php echo $record['time_in'] ? date('g:i A', strtotime($record['time_in'])) : '-'; ?></td>
                        <td><?php echo $record['time_out'] ? date('g:i A', strtotime($record['time_out'])) : '-'; ?></td>
                        <td>
                          <?php if ($record['time_out']): ?>
                            <span class="badge badge-success">Present</span>
                          <?php elseif ($record['time_in']): ?>
                            <span class="badge badge-warning">Checked In</span>
                          <?php else: ?>
                            <span class="badge badge-secondary">Absent</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include('../includes/footer.php'); ?>