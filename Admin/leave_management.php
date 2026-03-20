<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch all leave requests grouped by status
$query = "
    SELECT lr.*, u.first_name, u.last_name 
    FROM leave_requests lr
    JOIN user u ON lr.user_id = u.user_id
    ORDER BY lr.date_applied DESC
";
$result = $mysql->query($query);

$leaves = ['Pending' => [], 'Approved' => [], 'Rejected' => []];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Fallback in case a status is empty or unusual
        $status = $row['status'] ?? 'Pending';
        if (isset($leaves[$status])) {
            $leaves[$status][] = $row;
        }
    }
}

$title = "Leave Management | WorkForcePro";
include('../includes/admin_header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Leave Management Directory</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="export_leaves.php" class="btn btn-success shadow-sm">
          <i class="fas fa-file-excel mr-1"></i> Export All Leaves
        </a>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
        <div class="card-header bg-dark text-white p-0 border-bottom-0">
            <ul class="nav nav-tabs" id="leaveTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active font-weight-bold text-dark bg-white" id="pending-tab" data-toggle="tab" href="#pending" role="tab">
                        <i class="fas fa-clock mr-1 text-warning"></i> Pending <span class="badge badge-warning ml-1"><?php echo count($leaves['Pending']); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold text-white" id="approved-tab" data-toggle="tab" href="#approved" role="tab">
                        <i class="fas fa-check-circle mr-1 text-success"></i> Approved <span class="badge badge-success ml-1"><?php echo count($leaves['Approved']); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold text-white" id="rejected-tab" data-toggle="tab" href="#rejected" role="tab">
                        <i class="fas fa-times-circle mr-1 text-danger"></i> Rejected <span class="badge badge-danger ml-1"><?php echo count($leaves['Rejected']); ?></span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="card-body bg-light">
            <div class="tab-content" id="leaveTabsContent">
                
                <div class="tab-pane fade show active" id="pending" role="tabpanel">
                    <table class="table table-bordered table-hover bg-white text-center align-middle datatable w-100">
                        <thead class="bg-light">
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Date Filed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($leaves['Pending'] as $row): ?>
                            <tr>
                                <td class="font-weight-bold text-left"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><span class="badge badge-info px-2 py-1"><?php echo htmlspecialchars($row['leave_type']); ?></span></td>
                                <td>
                                    <?php echo date('M d', strtotime($row['start_date'])); ?> - <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td class="text-muted"><?php echo date('M d, Y', strtotime($row['date_applied'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="approved" role="tabpanel">
                    <table class="table table-bordered table-hover bg-white text-center align-middle datatable w-100">
                        <thead class="bg-light">
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>HR Remarks</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($leaves['Approved'] as $row): ?>
                            <tr>
                                <td class="font-weight-bold text-left"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                <td><?php echo date('M d', strtotime($row['start_date'])); ?> - <?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                                <td class="text-muted font-italic"><?php echo htmlspecialchars($row['remarks'] ?? 'No remarks provided.'); ?></td>
                                <td><span class="badge badge-success px-2 py-1">Approved</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="rejected" role="tabpanel">
                    <table class="table table-bordered table-hover bg-white text-center align-middle datatable w-100">
                        <thead class="bg-light">
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Reason for Rejection</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($leaves['Rejected'] as $row): ?>
                            <tr>
                                <td class="font-weight-bold text-left"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                <td><?php echo date('M d', strtotime($row['start_date'])); ?> - <?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                                <td class="text-danger font-weight-bold font-italic"><?php echo htmlspecialchars($row['remarks'] ?? 'No reason provided.'); ?></td>
                                <td><span class="badge badge-danger px-2 py-1">Rejected</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

  </div>
</section>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

<script>
  $(document).ready(function () {
      // Initialize DataTables
      $('.datatable').DataTable({
          "responsive": true, 
          "lengthChange": true, 
          "autoWidth": false,
          "searching": true,
          "ordering": true,
          "order": [[ 4, "desc" ]] // Sort by date column by default
      });

      // Custom Tab Styling Logic
      $('.nav-tabs a').on('shown.bs.tab', function (e) {
          $('.nav-tabs a').removeClass('text-dark bg-white').addClass('text-white');
          $(e.target).removeClass('text-white').addClass('text-dark bg-white');
      });
  });
</script>