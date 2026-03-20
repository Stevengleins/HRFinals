<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Handle Form Submission for Updating Request Status
if (isset($_POST['update_request'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status']; // e.g., 'Reviewed'

    $stmt = $mysql->prepare("UPDATE employee_requests SET status = ? WHERE request_id = ?");
    $stmt->bind_param("si", $status, $request_id);

    if ($stmt->execute()) {
        $_SESSION['status_icon'] = 'success';
        $_SESSION['status_title'] = 'Request Updated!';
        $_SESSION['status_text'] = 'The employee request has been marked as ' . $status . '.';
    } else {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Error';
        $_SESSION['status_text'] = 'Failed to update request status.';
    }
    $stmt->close();
    header("Location: requests.php");
    exit();
}

// Fetch all employee requests and group them by status
$query = "
    SELECT r.*, u.first_name, u.last_name, u.email 
    FROM employee_requests r
    JOIN user u ON r.user_id = u.user_id
    ORDER BY r.date_submitted DESC
";
$result = $mysql->query($query);

$requests = ['Pending' => [], 'Reviewed' => []];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Fallback to Pending if status is somehow empty
        $status = ($row['status'] === 'Reviewed' || $row['status'] === 'Resolved') ? 'Reviewed' : 'Pending';
        $requests[$status][] = $row;
    }
}

$title = "Employee Requests | WorkForcePro";
include('../includes/admin_header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Employee Requests & Concerns</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="dashboard.php" class="btn btn-outline-dark btn-sm px-3 shadow-none" style="border-radius: 4px;">
          <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
        <div class="card-header bg-dark text-white p-0 border-bottom-0">
            <ul class="nav nav-tabs" id="requestTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active font-weight-bold text-dark bg-white" id="pending-tab" data-toggle="tab" href="#pending" role="tab">
                        <i class="fas fa-exclamation-circle mr-1 text-danger"></i> Pending <span class="badge badge-danger ml-1"><?php echo count($requests['Pending']); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold text-white" id="reviewed-tab" data-toggle="tab" href="#reviewed" role="tab">
                        <i class="fas fa-check-double mr-1 text-success"></i> Reviewed <span class="badge badge-success ml-1"><?php echo count($requests['Reviewed']); ?></span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="card-body bg-light">
            <div class="tab-content" id="requestTabsContent">
                
                <div class="tab-pane fade show active" id="pending" role="tabpanel">
                    <table class="table table-bordered table-hover bg-white text-center align-middle datatable w-100">
                        <thead class="bg-light">
                            <tr>
                                <th>Employee</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Date Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($requests['Pending'] as $row): ?>
                            <tr>
                                <td class="font-weight-bold text-left">
                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                </td>
                                <td class="text-left font-weight-bold"><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td><span class="badge badge-info px-2 py-1"><?php echo htmlspecialchars($row['request_type']); ?></span></td>
                                <td class="text-muted"><?php echo date('M d, Y h:i A', strtotime($row['date_submitted'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary action-btn" 
                                            data-id="<?php echo $row['request_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>"
                                            data-type="<?php echo htmlspecialchars($row['request_type']); ?>"
                                            data-subject="<?php echo htmlspecialchars($row['subject']); ?>"
                                            data-message="<?php echo htmlspecialchars($row['message']); ?>"
                                            data-date="<?php echo date('F d, Y - h:i A', strtotime($row['date_submitted'])); ?>">
                                        <i class="fas fa-eye mr-1"></i> Read & Review
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="reviewed" role="tabpanel">
                    <table class="table table-bordered table-hover bg-white text-center align-middle datatable w-100">
                        <thead class="bg-light">
                            <tr>
                                <th>Employee</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Date Submitted</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($requests['Reviewed'] as $row): ?>
                            <tr>
                                <td class="font-weight-bold text-left">
                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                </td>
                                <td class="text-left font-weight-bold"><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td><span class="badge badge-secondary px-2 py-1"><?php echo htmlspecialchars($row['request_type']); ?></span></td>
                                <td class="text-muted"><?php echo date('M d, Y h:i A', strtotime($row['date_submitted'])); ?></td>
                                <td><span class="badge badge-success px-2 py-1">Reviewed</span></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary view-only-btn" 
                                            data-name="<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>"
                                            data-type="<?php echo htmlspecialchars($row['request_type']); ?>"
                                            data-subject="<?php echo htmlspecialchars($row['subject']); ?>"
                                            data-message="<?php echo htmlspecialchars($row['message']); ?>"
                                            data-date="<?php echo date('F d, Y - h:i A', strtotime($row['date_submitted'])); ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
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

<div class="modal fade" id="actionModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 8px;">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title font-weight-bold"><i class="fas fa-envelope-open-text mr-2"></i> Employee Request Details</h5>
        <button type="button" class="close text-white" data-dismiss="modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="requests.php">
        <div class="modal-body bg-light">
            <input type="hidden" name="request_id" id="modalReqId">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <span class="text-muted d-block font-weight-bold text-uppercase" style="font-size: 0.8rem;">From Employee</span>
                    <span id="modalEmpName" class="font-weight-bold text-dark" style="font-size: 1.1rem;"></span>
                </div>
                <div class="col-md-6 text-right">
                    <span class="text-muted d-block font-weight-bold text-uppercase" style="font-size: 0.8rem;">Date Submitted</span>
                    <span id="modalDate" class="text-dark"></span>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <span class="text-muted d-block font-weight-bold text-uppercase" style="font-size: 0.8rem;">Category / Type</span>
                    <span id="modalReqType" class="badge badge-info px-2 py-1"></span>
                </div>
            </div>

            <div class="card shadow-none border">
                <div class="card-header bg-white">
                    <h5 id="modalSubject" class="m-0 font-weight-bold text-dark"></h5>
                </div>
                <div class="card-body">
                    <p id="modalMessage" class="m-0 text-dark" style="white-space: pre-wrap; font-size: 1.05rem;"></p>
                </div>
            </div>

            <div id="actionSection" class="mt-4">
                <hr>
                <div class="form-group mb-0 text-right">
                    <input type="hidden" name="status" value="Reviewed">
                    <p class="text-muted small mb-2 text-left"><i class="fas fa-info-circle"></i> Once marked as reviewed, this request will be moved to the Reviewed tab.</p>
                    <button type="submit" name="update_request" class="btn btn-success shadow-sm px-4">
                        <i class="fas fa-check-double mr-1"></i> Mark as Reviewed
                    </button>
                </div>
            </div>

        </div>
        <div class="modal-footer bg-white border-top-0 py-2">
            <button type="button" class="btn btn-secondary shadow-sm" data-dismiss="modal">Close</button>
        </div>
      </form>
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
      // Initialize DataTables
      $('.datatable').DataTable({
          "responsive": true, 
          "lengthChange": true, 
          "autoWidth": false,
          "searching": true,
          "ordering": true,
          "order": [[ 3, "desc" ]] // Sort by date submitted by default
      });

      // Custom Tab Styling Logic
      $('.nav-tabs a').on('shown.bs.tab', function (e) {
          $('.nav-tabs a').removeClass('text-dark bg-white').addClass('text-white');
          $(e.target).removeClass('text-white').addClass('text-dark bg-white');
      });

      // Pass data to modal for PENDING requests (Allows marking as reviewed)
      $('.action-btn').on('click', function() {
          $('#modalReqId').val($(this).data('id'));
          $('#modalEmpName').text($(this).data('name'));
          $('#modalReqType').text($(this).data('type'));
          $('#modalSubject').text($(this).data('subject'));
          $('#modalMessage').text($(this).data('message'));
          $('#modalDate').text($(this).data('date'));
          
          $('#actionSection').show(); // Show the submit button
          $('#actionModal').modal('show');
      });

      // Pass data to modal for REVIEWED requests (Read-only)
      $('.view-only-btn').on('click', function() {
          $('#modalEmpName').text($(this).data('name'));
          $('#modalReqType').text($(this).data('type'));
          $('#modalSubject').text($(this).data('subject'));
          $('#modalMessage').text($(this).data('message'));
          $('#modalDate').text($(this).data('date'));
          
          $('#actionSection').hide(); // Hide the submit button
          $('#actionModal').modal('show');
      });
  });
</script>

<?php
if (isset($_SESSION['status_icon'])) {
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

    unset($_SESSION['status_icon'], $_SESSION['status_title'], $_SESSION['status_text']);
}
?>