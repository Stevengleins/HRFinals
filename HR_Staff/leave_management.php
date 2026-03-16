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
                <th>Action</th>
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
                    <?php if($row['status'] == 'Pending'): ?>
                        <button onclick="updateLeaveStatus(<?php echo $row['leave_id']; ?>, 'Approved')" class="btn btn-sm btn-success shadow-sm mr-1" title="Approve"><i class="fas fa-check"></i></button>
                        <button onclick="updateLeaveStatus(<?php echo $row['leave_id']; ?>, 'Rejected')" class="btn btn-sm btn-danger shadow-sm" title="Reject"><i class="fas fa-times"></i></button>
                    <?php else: ?>
                        <span class="text-muted text-sm">Actioned</span>
                    <?php endif; ?>
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

<?php include '../includes/footer.php'; ?>

<script>
function updateLeaveStatus(leaveId, newStatus) {
    if (newStatus === 'Rejected') {
        Swal.fire({
            title: 'Reject Leave Request',
            text: 'Please provide a reason for rejecting this leave:',
            input: 'textarea',
            inputPlaceholder: 'Enter your reason here...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Submit Rejection',
            preConfirm: (reason) => {
                if (!reason.trim()) {
                    Swal.showValidationMessage('A reason is required to reject a leave request.');
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Encode the text so it can safely pass through the URL
                let encodedReason = encodeURIComponent(result.value);
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