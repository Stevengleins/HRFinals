<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}

require '../database.php'; 
require '../includes/employee_header.php'; 


$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE user_id = '$user_id'";
$result = $mysql->query($query);
$employee = $result->fetch_assoc();


$historyQuery = "SELECT * FROM leave_requests WHERE user_id = '$user_id' ORDER BY date_applied DESC";
$historyResult = $mysql->query($historyQuery);

$usedLeaves = [];
$balanceQuery = "SELECT leave_type, SUM(DATEDIFF(end_date, start_date) + 1) as used_days 
                 FROM leave_requests 
                 WHERE user_id = '$user_id' AND status = 'Approved' 
                 GROUP BY leave_type";
$balanceResult = $mysql->query($balanceQuery);

if($balanceResult && $balanceResult->num_rows > 0) {
    while($row = $balanceResult->fetch_assoc()) {
        $usedLeaves[$row['leave_type']] = $row['used_days'];
    }
}


$vl_left = max(0, 12 - ($usedLeaves['Vacation Leave'] ?? 0));
$sl_left = max(0, 5 - ($usedLeaves['Sick Leave'] ?? 0));
$pm_left = max(0, 7 - ($usedLeaves['Paternity/Maternity Leave'] ?? 0));
$el_left = max(0, 3 - ($usedLeaves['Emergency Leave'] ?? 0));
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Leave Management</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="row">
      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
          <span class="info-box-icon bg-success elevation-1"><i class="fas fa-plane"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Vacation Leave</span>
            <span class="info-box-number"><?php echo $vl_left; ?> <small>Days Left</small></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
          <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-briefcase-medical text-white"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Sick Leave</span>
            <span class="info-box-number"><?php echo $sl_left; ?> <small>Days Left</small></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
          <span class="info-box-icon bg-info elevation-1"><i class="fas fa-baby"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Paternity/Maternity</span>
            <span class="info-box-number"><?php echo $pm_left; ?> <small>Days Left</small></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
          <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-exclamation-triangle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Emergency Leave</span>
            <span class="info-box-number"><?php echo $el_left; ?> <small>Days Left</small></span>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-4">
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-2"></i> File a New Leave</h3>
                </div>
                <form id="leaveForm" onsubmit="submitLeave(event)">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Leave Type</label>
                            <select class="form-control" id="leave_type" required>
                                <option value="" disabled selected>Select leave type...</option>
                                <option value="Vacation Leave">Vacation Leave (VL)</option>
                                <option value="Sick Leave">Sick Leave (SL)</option>
                                <option value="Paternity/Maternity Leave">Paternity/Maternity Leave</option>
                                <option value="Emergency Leave">Emergency Leave (EL)</option>
                                <option value="Unpaid Leave">Unpaid Leave</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Start Date</label>
                                <input type="date" class="form-control" id="start_date" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>End Date</label>
                                <input type="date" class="form-control" id="end_date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Reason for Leave</label>
                            <textarea class="form-control" id="reason" rows="3" placeholder="Briefly explain your reason..." required></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane mr-2"></i> Submit Request</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
              <div class="card-header bg-dark text-white">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i> My Leave History</h3>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover table-striped m-0 text-center">
                    <thead>
                    <tr>
                      <th>Leave Type</th>
                      <th>Duration</th>
                      <th>Total Days</th>
                      <th>Date Applied</th>
                      <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php if($historyResult && $historyResult->num_rows > 0): while($row = $historyResult->fetch_assoc()): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                          <td>
                              <?php echo date('M d, Y', strtotime($row['start_date'])); ?> - <br>
                              <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                          </td>
                          <td>
                              <?php 
                                $start = new DateTime($row['start_date']);
                                $end = new DateTime($row['end_date']);
                                $days = $start->diff($end)->days + 1;
                                echo $days;
                              ?>
                          </td>
                          <td><?php echo date('M d, Y', strtotime($row['date_applied'])); ?></td>
                          <td>
                              <?php 
                                  if($row['status'] == 'Approved') echo '<span class="badge bg-success px-2 py-1">Approved</span>';
                                  elseif($row['status'] == 'Rejected') echo '<span class="badge bg-danger px-2 py-1">Rejected</span>';
                                  else echo '<span class="badge bg-warning px-2 py-1 text-dark">Pending</span>';
                              ?>
                          </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">You have no leave history.</td>
                        </tr>
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

</div> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  function submitLeave(event) {
      event.preventDefault(); 

      let leaveType = document.getElementById('leave_type').value;
      let startDate = document.getElementById('start_date').value;
      let endDate = document.getElementById('end_date').value;
      let reason = document.getElementById('reason').value; 

      if(new Date(endDate) < new Date(startDate)) {
          Swal.fire({
              icon: 'error',
              title: 'Invalid Dates',
              text: 'End date cannot be earlier than the start date!',
              confirmButtonColor: '#3085d6'
          });
          return;
      }

      Swal.fire({
          title: 'Submit Leave Request?',
          html: `You are applying for <b>${leaveType}</b> from <b>${startDate}</b> to <b>${endDate}</b>.`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#007bff',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, submit it!'
      }).then((result) => {
          if (result.isConfirmed) {
              let formData = new FormData();
              formData.append('leave_type', leaveType);
              formData.append('start_date', startDate);
              formData.append('end_date', endDate);
              formData.append('reason', reason);

            
              fetch('process_leave.php', {
                  method: 'POST',
                  body: formData
              })
              .then(response => response.json())
              .then(data => {
                  if(data.status === 'success') {
                      Swal.fire(
                          'Submitted!',
                          'Your leave request has been forwarded to HR for approval.',
                          'success'
                      ).then(() => {
                          window.location.reload(); 
                      });
                  } else {
                      Swal.fire('Error', data.message || 'Could not save the request.', 'error');
                  }
              })
              .catch(error => {
                  Swal.fire('Error', 'An unexpected error occurred.', 'error');
              });
          }
      });
  }
</script>
</body>
</html> 