<?php
session_start();
require_once('../database.php');

// Strict Security Check: ONLY HR Staff can access this
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
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

$title = "Leave Management | WorkForcePro";
include('../includes/hr_header.php');
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
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Leave Management</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="card shadow-sm border-0 mb-5" style="border-radius: 8px; overflow: hidden;">
        
        <div class="card-header bg-dark text-white pt-3 pb-0 px-4 border-bottom-0">
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
                                <th class="text-center">Action</th>
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
                                <td class="text-muted" style="max-width: 200px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?php echo htmlspecialchars($row['reason']); ?>">
                                    <?php echo htmlspecialchars($row['reason']); ?>
                                </td>
                                <td class="font-weight-bold text-dark"><?php echo date('M d, Y', strtotime($row['date_applied'])); ?></td>
                                <td class="text-center">
                                    <button onclick="viewLeaveRequest(this)" class="btn btn-sm btn-dark shadow-sm font-weight-bold px-3" title="Review & Process" 
                                            data-id="<?php echo $row['leave_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>" 
                                            data-type="<?php echo htmlspecialchars($row['leave_type']); ?>" 
                                            data-start="<?php echo date('M d, Y', strtotime($row['start_date'])); ?>" 
                                            data-end="<?php echo date('M d, Y', strtotime($row['end_date'])); ?>" 
                                            data-reason="<?php echo htmlspecialchars($row['reason']); ?>" 
                                            data-applied="<?php echo date('M d, Y g:i A', strtotime($row['date_applied'])); ?>" 
                                            data-status="<?php echo $row['status']; ?>">
                                        <i class="fas fa-search mr-1"></i> Review
                                    </button>
                                </td>
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

<div class="modal fade" id="leaveModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 8px;">
      <div class="modal-header bg-dark text-white border-0 py-3">
        <h5 class="modal-title font-weight-bold"><i class="fas fa-file-signature mr-2"></i> Leave Request Summary</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body bg-light p-4">
        
        <div class="p-3" style="background-color: #ffffff; border-radius: 8px; border: 1px solid #e9ecef;">
            <div class="row">
              <div class="col-md-6 border-right">
                <div class="mb-3"><span class="text-muted font-weight-bold d-block text-xs text-uppercase">Employee Name</span><span class="text-dark font-weight-bold" id="modalEmployeeName"></span></div>
                <div class="mb-3"><span class="text-muted font-weight-bold d-block text-xs text-uppercase">Leave Type</span><span class="badge badge-info px-2 py-1 mt-1" id="modalLeaveType"></span></div>
                <div class="mb-3"><span class="text-muted font-weight-bold d-block text-xs text-uppercase">Status</span><span class="badge badge-warning px-2 py-1 mt-1 text-dark" id="modalStatus"></span></div>
              </div>
              <div class="col-md-6 pl-md-4">
                <div class="mb-3"><span class="text-muted font-weight-bold d-block text-xs text-uppercase">Date Applied</span><span class="text-dark" id="modalDateApplied"></span></div>
                <div class="mb-3"><span class="text-muted font-weight-bold d-block text-xs text-uppercase">Requested Duration</span><span class="text-dark font-weight-bold text-primary"><span id="modalStartDate"></span> <i class="fas fa-arrow-right mx-1 text-xs text-muted"></i> <span id="modalEndDate"></span></span></div>
              </div>
            </div>
            
            <hr style="opacity: 0.2;">
            
            <div class="row">
              <div class="col-12">
                  <span class="text-muted font-weight-bold d-block text-xs text-uppercase mb-1">Employee Reason</span>
                  <p class="text-dark p-3 bg-light rounded border" id="modalReason"></p>
              </div>
            </div>
        </div>

      </div>
      <div class="modal-footer border-top-0 bg-white" id="modalActions">
        </div>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

<script>
  $(document).ready(function () {
      // Initialize Premium DataTables
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

  // HR Specific Modal Logic
  function viewLeaveRequest(button) {
      document.getElementById('modalEmployeeName').textContent = button.getAttribute('data-name');
      document.getElementById('modalLeaveType').textContent = button.getAttribute('data-type');
      document.getElementById('modalStartDate').textContent = button.getAttribute('data-start');
      document.getElementById('modalEndDate').textContent = button.getAttribute('data-end');
      document.getElementById('modalReason').textContent = button.getAttribute('data-reason');
      document.getElementById('modalDateApplied').textContent = button.getAttribute('data-applied');
      
      const status = button.getAttribute('data-status');
      document.getElementById('modalStatus').textContent = status;
      
      const modalActions = document.getElementById('modalActions');
      modalActions.innerHTML = '';
      
      if (status === 'Pending') {
          modalActions.innerHTML = `
              <button type="button" class="btn btn-outline-secondary mr-auto" data-dismiss="modal">Cancel</button>
              <button onclick="updateLeaveStatus(${button.getAttribute('data-id')}, 'Rejected')" class="btn btn-danger shadow-sm px-4" title="Reject">
                  <i class="fas fa-times mr-1"></i> Reject
              </button>
              <button onclick="updateLeaveStatus(${button.getAttribute('data-id')}, 'Approved')" class="btn btn-success shadow-sm px-4 ml-2" title="Approve">
                  <i class="fas fa-check mr-1"></i> Approve
              </button>
          `;
      } else {
          modalActions.innerHTML = '<button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Close</button>';
      }
      
      $('#leaveModal').modal('show');
  }

  function updateLeaveStatus(leaveId, newStatus) {
      $('#leaveModal').modal('hide');
      
      if (newStatus === 'Rejected') {
          Swal.fire({
              title: 'Reject Leave Request',
              text: 'Please provide a reason for rejecting this leave (optional):',
              input: 'textarea',
              inputPlaceholder: 'Enter your reason here...',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#dc3545',
              cancelButtonColor: '#6c757d',
              confirmButtonText: 'Submit Rejection'
          }).then((result) => {
              if (result.isConfirmed) {
                  let encodedReason = encodeURIComponent(result.value || '');
                  window.location.href = `process_leave.php?id=${leaveId}&status=${newStatus}&reason=${encodedReason}`;
              }
          });
      } else {
          Swal.fire({
              title: `Approve Leave Request?`,
              text: `You are about to officially approve this leave request.`,
              icon: 'info',
              showCancelButton: true,
              confirmButtonColor: '#28a745',
              cancelButtonColor: '#6c757d',
              confirmButtonText: `Yes, Approve it!`
          }).then((result) => {
              if (result.isConfirmed) {
                  window.location.href = `process_leave.php?id=${leaveId}&status=${newStatus}`;
              }
          });
      }
  }
</script>

<?php
if (isset($_SESSION['status_icon']) && isset($_SESSION['status_title']) && isset($_SESSION['status_text'])) {
    $icon = $_SESSION['status_icon'];
    $title = $_SESSION['status_title'];
    $text = $_SESSION['status_text'];
    
    echo "
    <script>
        Swal.fire({
            icon: '$icon',
            title: '$title',
            text: '$text',
            confirmButtonColor: '#212529'
        });
    </script>
    ";

    unset($_SESSION['status_icon']);
    unset($_SESSION['status_title']);
    unset($_SESSION['status_text']);
}
?>