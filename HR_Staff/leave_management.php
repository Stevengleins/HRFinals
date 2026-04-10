<?php
session_start();

// Changed to check for HR Staff role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

require '../database.php';

// Now includes the new HR specific header
include '../includes/hr_header.php'; 

// Fetch employee leave requests
$query = "SELECT lr.*, u.first_name, u.last_name 
          FROM leave_requests lr 
          JOIN user u ON lr.user_id = u.user_id 
          ORDER BY lr.date_applied DESC";
$result = $mysql->query($query);
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Leave Management</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;"><i class="fas fa-calendar-check mr-2"></i> Employee Leave Requests</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped m-0 text-center align-middle">
            <thead class="bg-light">
              <tr>
                <th>Employee Name</th>
                <th>Leave Type</th>
                <th>Dates</th>
                <th>Reason</th>
                <th>Date Applied</th>
                <th>Status</th>
                <th>View Summary</th>
              </tr>
            </thead>
            <tbody>
              <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="font-weight-bold"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                <td>
                    <?php echo date('M d, Y', strtotime($row['start_date'])); ?> - <br>
                    <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                </td>
                <td style="max-width: 200px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?php echo htmlspecialchars($row['reason']); ?>">
                    <?php echo htmlspecialchars($row['reason']); ?>
                </td>
                <td><?php echo date('M d, Y g:i A', strtotime($row['date_applied'])); ?></td>
                <td>
                    <?php 
                        if($row['status'] == 'Approved') echo '<span class="badge badge-success px-2 py-1">Approved</span>';
                        elseif($row['status'] == 'Rejected') echo '<span class="badge badge-danger px-2 py-1">Rejected</span>';
                        else echo '<span class="badge badge-warning px-2 py-1 text-dark">Pending</span>';
                    ?>
                </td>
                <td>
                    <button onclick="viewLeaveRequest(this)" class="btn btn-sm btn-primary shadow-sm" title="View" 
                            data-id="<?php echo $row['leave_id']; ?>" 
                            data-name="<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>" 
                            data-type="<?php echo htmlspecialchars($row['leave_type']); ?>" 
                            data-start="<?php echo date('M d, Y', strtotime($row['start_date'])); ?>" 
                            data-end="<?php echo date('M d, Y', strtotime($row['end_date'])); ?>" 
                            data-reason="<?php echo htmlspecialchars($row['reason']); ?>" 
                            data-applied="<?php echo date('M d, Y g:i A', strtotime($row['date_applied'])); ?>" 
                            data-status="<?php echo $row['status']; ?>">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr>
                  <td colspan="7" class="text-center py-4 text-muted">No leave requests found.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Leave Request Modal -->
<div class="modal fade" id="leaveModal" tabindex="-1" role="dialog" aria-labelledby="leaveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="leaveModalLabel">Leave Request Summary</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <p><strong>Employee Name:</strong> <span id="modalEmployeeName"></span></p>
            <p><strong>Leave Type:</strong> <span id="modalLeaveType"></span></p>
            <p><strong>Start Date:</strong> <span id="modalStartDate"></span></p>
            <p><strong>End Date:</strong> <span id="modalEndDate"></span></p>
          </div>
          <div class="col-md-6">
            <p><strong>Reason:</strong> <span id="modalReason"></span></p>
            <p><strong>Date Applied:</strong> <span id="modalDateApplied"></span></p>
            <p><strong>Status:</strong> <span id="modalStatus"></span></p>
          </div>
        </div>
      </div>
      <div class="modal-footer" id="modalActions">
        <!-- Buttons will be added here by JavaScript -->
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function viewLeaveRequest(button) {
    // Populate modal with data
    document.getElementById('modalEmployeeName').textContent = button.getAttribute('data-name');
    document.getElementById('modalLeaveType').textContent = button.getAttribute('data-type');
    document.getElementById('modalStartDate').textContent = button.getAttribute('data-start');
    document.getElementById('modalEndDate').textContent = button.getAttribute('data-end');
    document.getElementById('modalReason').textContent = button.getAttribute('data-reason');
    document.getElementById('modalDateApplied').textContent = button.getAttribute('data-applied');
    const status = button.getAttribute('data-status');
    document.getElementById('modalStatus').textContent = status;
    
    // Clear previous actions
    const modalActions = document.getElementById('modalActions');
    modalActions.innerHTML = '';
    
    // Add buttons if pending
    if (status === 'Pending') {
        modalActions.innerHTML = `
            <button onclick="updateLeaveStatus(${button.getAttribute('data-id')}, 'Approved')" class="btn btn-success mr-2" title="Approve">
                <i class="fas fa-check"></i> Approve
            </button>
            <button onclick="updateLeaveStatus(${button.getAttribute('data-id')}, 'Rejected')" class="btn btn-danger" title="Reject">
                <i class="fas fa-times"></i> Reject
            </button>
        `;
    } else {
        modalActions.innerHTML = '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
    }
    
    // Show modal
    $('#leaveModal').modal('show');
}

function updateLeaveStatus(leaveId, newStatus) {
    // Close the modal first
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
                // Encode the text so it can safely pass through the URL
                let encodedReason = encodeURIComponent(result.value || '');
                window.location.href = `process_leave.php?id=${leaveId}&status=${newStatus}&reason=${encodedReason}`;
            }
        });
    } else {
        // Standard approval confirmation
        Swal.fire({
            title: `Are you sure?`,
            text: `You are about to approve this leave request.`,
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
