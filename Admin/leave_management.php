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
        $status = $row['status'] ?? 'Pending';
        if (isset($leaves[$status])) {
            $leaves[$status][] = $row;
        }
    }
}

$title = "Leave Reports | WorkForcePro";
include('../includes/admin_header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<style>
    /* Premium Table Styling */
    .table-custom thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
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
    
    /* Seamless Dark Header Tabs */
    .nav-tabs { border-bottom: none; }
    .nav-tabs .nav-link { 
        color: #adb5bd; 
        border: none; 
        padding: 12px 25px; 
        font-weight: 600; 
        border-radius: 8px 8px 0 0; 
        margin-right: 5px; 
        transition: all 0.2s ease; 
    }
    .nav-tabs .nav-link:hover { color: #ffffff; }
    .nav-tabs .nav-link.active { 
        color: #212529 !important; 
        background-color: #ffffff !important; 
        border: none; 
    }
</style>

<div class="content-header pb-2">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Leave Reports</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="card shadow-sm border-0 mb-5" style="border-radius: 8px; overflow: hidden;">
        
        <div class="card-header bg-dark text-white pt-3 pb-0 px-4 d-flex justify-content-between align-items-end border-bottom-0">
            <ul class="nav nav-tabs" id="leaveTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab">
                        <i class="fas fa-clock mr-1 text-warning"></i> Pending <span class="badge badge-warning ml-1 shadow-sm"><?php echo count($leaves['Pending']); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="approved-tab" data-toggle="tab" href="#approved" role="tab">
                        <i class="fas fa-check-circle mr-1 text-success"></i> Approved <span class="badge badge-success ml-1 shadow-sm"><?php echo count($leaves['Approved']); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="rejected-tab" data-toggle="tab" href="#rejected" role="tab">
                        <i class="fas fa-times-circle mr-1 text-danger"></i> Rejected <span class="badge badge-danger ml-1 shadow-sm"><?php echo count($leaves['Rejected']); ?></span>
                    </a>
                </li>
            </ul>

            <div class="pb-2">
                <div class="btn-group shadow-sm">
                    <a href="export_leaves_csv.php" class="btn btn-sm btn-light border-0" title="Export Leaves to CSV"><i class="fas fa-file-csv text-success mr-1"></i> CSV</a>
                    <a href="export_pdf.php?type=leaves" target="_blank" class="btn btn-sm btn-light border-left border-0" title="Export Leaves to PDF"><i class="fas fa-file-pdf text-danger mr-1"></i> PDF</a>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0 bg-white">
            <div class="tab-content" id="leaveTabsContent">
                
                <div class="tab-pane fade show active p-3" id="pending" role="tabpanel">
                    <table class="table table-hover table-custom w-100 datatable">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Date Filed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($leaves['Pending'] as $row): ?>
                            <tr>
                                <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><span class="badge badge-info px-2 py-1" style="font-size: 0.85rem;"><?php echo htmlspecialchars($row['leave_type']); ?></span></td>
                                <td class="text-muted font-weight-bold">
                                    <?php echo date('M d', strtotime($row['start_date'])); ?> <i class="fas fa-arrow-right mx-1 text-xs text-muted"></i> <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td class="font-weight-bold text-dark"><?php echo date('M d, Y', strtotime($row['date_applied'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade p-3" id="approved" role="tabpanel">
                    <table class="table table-hover table-custom w-100 datatable">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>HR Remarks</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($leaves['Approved'] as $row): ?>
                            <tr>
                                <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td class="text-muted font-weight-bold"><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                <td class="text-muted font-weight-bold">
                                    <?php echo date('M d', strtotime($row['start_date'])); ?> <i class="fas fa-arrow-right mx-1 text-xs text-muted"></i> <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                </td>
                                <td class="text-muted font-italic"><?php echo htmlspecialchars($row['remarks'] ?? 'No remarks provided.'); ?></td>
                                <td><span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Approved</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade p-3" id="rejected" role="tabpanel">
                    <table class="table table-hover table-custom w-100 datatable">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Reason for Rejection</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($leaves['Rejected'] as $row): ?>
                            <tr>
                                <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td class="text-muted font-weight-bold"><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                <td class="text-muted font-weight-bold">
                                    <?php echo date('M d', strtotime($row['start_date'])); ?> <i class="fas fa-arrow-right mx-1 text-xs text-muted"></i> <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                </td>
                                <td class="text-danger font-weight-bold font-italic"><?php echo htmlspecialchars($row['remarks'] ?? 'No reason provided.'); ?></td>
                                <td><span class="badge badge-danger px-2 py-1"><i class="fas fa-times mr-1"></i> Rejected</span></td>
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
          "pageLength": 10,
          "order": [[ 4, "desc" ]], // Sort by date column by default
          "language": {
              "search": "_INPUT_",
              "searchPlaceholder": "Search records..."
          }
      });
  });
</script>