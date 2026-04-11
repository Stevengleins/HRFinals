<?php
session_start();

// 1. Strict Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}

require '../database.php'; 

$title = "Leave Management | WorkForcePro";
require '../includes/employee_header.php'; 

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM `user` WHERE user_id = '$user_id'";
$result = $mysql->query($query);
$employee = $result->fetch_assoc();

$historyQuery = "SELECT * FROM leave_requests WHERE user_id = '$user_id' ORDER BY date_applied DESC";
$historyResult = $mysql->query($historyQuery);

// 2. CHECK FOR ACTIVE LEAVE LOCKOUT
// Rule: Cannot apply if there is a Pending request OR an Approved request that hasn't finished yet.
date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
$activeQuery = "
    SELECT * FROM leave_requests 
    WHERE user_id = '$user_id' 
    AND (
        status = 'Pending' 
        OR (status = 'Approved' AND end_date >= '$today')
    )
    ORDER BY date_applied DESC LIMIT 1
";
$activeResult = $mysql->query($activeQuery);
$activeLeave = $activeResult->fetch_assoc();
$hasActiveLeave = !empty($activeLeave);

// 3. CALCULATE USED LEAVES
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

// 4. PHILIPPINE LEAVE LAW BALANCES
$vl_left = max(0, 15 - ($usedLeaves['Vacation Leave'] ?? 0)); // Standard Corporate Practice
$sl_left = max(0, 15 - ($usedLeaves['Sick Leave'] ?? 0)); // Standard Corporate Practice
$mat_left = max(0, 105 - ($usedLeaves['Maternity Leave'] ?? 0)); // RA 11210
$pat_left = max(0, 7 - ($usedLeaves['Paternity Leave'] ?? 0)); // RA 8187
$solo_left = max(0, 7 - ($usedLeaves['Solo Parent Leave'] ?? 0)); // RA 8972
$vawc_left = max(0, 10 - ($usedLeaves['VAWC Leave'] ?? 0)); // RA 9262
$el_left = max(0, 5 - ($usedLeaves['Emergency Leave'] ?? 0)); // Standard Practice
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<style>
    /* Premium Dashboard Cards */
    .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
    .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
    .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
    .border-left-danger { border-left: 0.25rem solid #e74a3b !important; }
    .text-gray-300 { color: #dddfeb !important; }
    .text-xs { font-size: .7rem; }
    
    /* Clean Table Styling with Light Categories */
    .table-custom thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        border-top: none;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .table-custom td {
        vertical-align: middle !important;
        border-top: 1px solid #f1f3f5;
        font-size: 0.95rem;
        padding: 1rem 0.75rem;
    }
</style>

<div class="content-header pb-3">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.5rem;">Leave Management</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-primary h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Vacation Leave (VL)</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $vl_left; ?> <small class="text-muted text-sm font-weight-normal">/ 15</small></div>
              </div>
              <div class="col-auto"><i class="fas fa-plane fa-2x text-gray-300"></i></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-success h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sick Leave (SL)</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $sl_left; ?> <small class="text-muted text-sm font-weight-normal">/ 15</small></div>
              </div>
              <div class="col-auto"><i class="fas fa-briefcase-medical fa-2x text-gray-300"></i></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-warning h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Paternity / Maternity</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $pat_left; ?> <span class="text-muted text-sm font-weight-normal">| <?php echo $mat_left; ?></span></div>
              </div>
              <div class="col-auto"><i class="fas fa-baby fa-2x text-gray-300"></i></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm border-left-danger h-100 py-2">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Emergency / Special</div>
                <div class="h4 mb-0 font-weight-bold text-dark"><?php echo $el_left; ?> <small class="text-muted text-sm font-weight-normal">/ 5</small></div>
              </div>
              <div class="col-auto"><i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-3">
        
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 8px; overflow: hidden;">
                <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold" style="font-size: 1.1rem;"><i class="fas fa-edit mr-2"></i> File a New Leave</h6>
                </div>
                
                <form id="leaveForm" onsubmit="submitLeave(event)" class="bg-white">
                    <div class="card-body bg-white p-4">
                        
                        <?php if($hasActiveLeave): ?>
                        <div class="alert alert-warning shadow-sm pb-3 pt-3 mb-4" style="border-left: 4px solid #f6c23e;">
                            <h6 class="font-weight-bold text-dark mb-1"><i class="fas fa-lock mr-2"></i> Form Locked</h6>
                            <p class="mb-0 text-dark small">You have an active leave request (<strong><?php echo $activeLeave['status']; ?></strong>) scheduled until <strong><?php echo date('M d, Y', strtotime($activeLeave['end_date'])); ?></strong>. You cannot file a new request until this leave has finished.</p>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="text-muted text-xs font-weight-bold text-uppercase">Leave Type</label>
                            <select class="form-control shadow-sm" id="leave_type" onchange="updateDateLimits()" required <?php echo $hasActiveLeave ? 'disabled' : ''; ?>>
                                <option value="" disabled selected>Select leave classification...</option>
                                <option value="Vacation Leave">Vacation Leave (VL)</option>
                                <option value="Sick Leave">Sick Leave (SL)</option>
                                <option value="Maternity Leave">Maternity Leave (RA 11210)</option>
                                <option value="Paternity Leave">Paternity Leave (RA 8187)</option>
                                <option value="Solo Parent Leave">Solo Parent Leave (RA 8972)</option>
                                <option value="VAWC Leave">VAWC Leave (RA 9262)</option>
                                <option value="Emergency Leave">Emergency Leave</option>
                                <option value="Unpaid Leave">Unpaid Leave / LWOP</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="text-muted text-xs font-weight-bold text-uppercase">Start Date</label>
                                <input type="date" class="form-control shadow-sm" id="start_date" onchange="updateDateLimits()" required <?php echo $hasActiveLeave ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="text-muted text-xs font-weight-bold text-uppercase">End Date</label>
                                <input type="date" class="form-control shadow-sm" id="end_date" required <?php echo $hasActiveLeave ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="text-muted text-xs font-weight-bold text-uppercase">Reason for Leave</label>
                            <textarea class="form-control shadow-sm" id="reason" rows="4" placeholder="Briefly explain your reason..." required <?php echo $hasActiveLeave ? 'disabled' : ''; ?>></textarea>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light border-top-0 py-3">
                        <button type="submit" class="btn btn-dark btn-block font-weight-bold shadow-sm" style="border-radius: 6px;" <?php echo $hasActiveLeave ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane mr-2"></i> Submit to HR
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 8px; overflow: hidden;">
                <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold" style="font-size: 1.1rem;"><i class="fas fa-history mr-2"></i> My Leave History</h6>
                </div>
              
              <div class="card-body p-0 bg-white">
                <div class="table-responsive p-3">
                  <table id="leaveTable" class="table table-hover table-custom w-100 text-center datatable">
                    <thead>
                    <tr>
                      <th>Leave Type</th>
                      <th>Duration</th>
                      <th>Days</th>
                      <th>Status</th>
                      <th>HR Remarks</th> 
                    </tr>
                    </thead>
                    <tbody>
                        <?php if($historyResult && $historyResult->num_rows > 0): while($row = $historyResult->fetch_assoc()): ?>
                        <tr>
                          <td class="align-middle font-weight-bold text-dark"><?php echo htmlspecialchars($row['leave_type']); ?></td>
                          <td class="align-middle text-muted font-weight-bold">
                              <?php echo date('M d', strtotime($row['start_date'])); ?> <i class="fas fa-arrow-right mx-1 text-xs text-muted"></i> <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                          </td>
                          <td class="align-middle font-weight-bold text-dark">
                              <?php 
                                $start = new DateTime($row['start_date']);
                                $end = new DateTime($row['end_date']);
                                $days = $start->diff($end)->days + 1;
                                echo $days;
                              ?>
                          </td>
                          <td class="align-middle">
                              <?php 
                                  if($row['status'] == 'Approved') echo '<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Approved</span>';
                                  elseif($row['status'] == 'Rejected') echo '<span class="badge badge-danger px-2 py-1"><i class="fas fa-times mr-1"></i> Rejected</span>';
                                  else echo '<span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-clock mr-1"></i> Pending</span>';
                              ?>
                          </td>
                          <td class="align-middle text-left" style="max-width: 200px;">
                              <?php if($row['status'] == 'Rejected' && !empty($row['remarks'])): ?>
                                  <small class="text-danger font-weight-bold font-italic">
                                      <i class="fas fa-info-circle mr-1"></i> <?php echo htmlspecialchars($row['remarks']); ?>
                                  </small>
                              <?php elseif($row['status'] == 'Approved' && !empty($row['remarks'])): ?>
                                  <small class="text-success font-weight-bold font-italic">
                                      <i class="fas fa-comment-dots mr-1"></i> <?php echo htmlspecialchars($row['remarks']); ?>
                                  </small>
                              <?php else: ?>
                                  <span class="text-muted">-</span>
                              <?php endif; ?>
                          </td>
                        </tr>
                        <?php endwhile; endif; ?>
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

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
  $(document).ready(function () {
      $('.datatable').DataTable({ 
          "responsive": true, "lengthChange": false, "pageLength": 10, 
          "order": [[ 1, "desc" ]], 
          "language": { "search": "", "searchPlaceholder": "Search my leaves..." } 
      });
  });

  // Pass PHP balances to JavaScript
  const leaveBalances = {
      'Vacation Leave': <?php echo $vl_left; ?>,
      'Sick Leave': <?php echo $sl_left; ?>,
      'Maternity Leave': <?php echo $mat_left; ?>,
      'Paternity Leave': <?php echo $pat_left; ?>,
      'Solo Parent Leave': <?php echo $solo_left; ?>,
      'VAWC Leave': <?php echo $vawc_left; ?>,
      'Emergency Leave': <?php echo $el_left; ?>,
      'Unpaid Leave': 999 // Unlimited (Leave Without Pay)
  };

  const hasActiveLeave = <?php echo $hasActiveLeave ? 'true' : 'false'; ?>;

  // Dynamically limit the End Date calendar based on available PH Law balances
  function updateDateLimits() {
      const leaveType = document.getElementById('leave_type').value;
      const startDateInput = document.getElementById('start_date');
      const endDateInput = document.getElementById('end_date');

      endDateInput.value = '';
      
      if (leaveType && startDateInput.value) {
          let maxDaysAllowed = leaveBalances[leaveType];

          if (maxDaysAllowed <= 0) {
              Swal.fire({
                  icon: 'warning',
                  title: 'Zero Balance',
                  text: `You have 0 days left for ${leaveType}.`
              });
              startDateInput.value = '';
              return;
          }

          endDateInput.min = startDateInput.value;

          if (maxDaysAllowed !== 999) {
              let startDateObj = new Date(startDateInput.value);
              startDateObj.setDate(startDateObj.getDate() + (maxDaysAllowed - 1));
              let maxDateStr = startDateObj.toISOString().split('T')[0];
              endDateInput.max = maxDateStr;
          } else {
              endDateInput.removeAttribute('max');
          }
      }
  }

  function submitLeave(event) {
      event.preventDefault(); 

      // Active Leave Lockout Mechanism
      if (hasActiveLeave) {
          Swal.fire({
              icon: 'error',
              title: 'Action Blocked',
              text: 'You cannot file a new request until your current active leave has officially finished.',
              confirmButtonColor: '#212529'
          });
          return; 
      }

      let leaveType = document.getElementById('leave_type').value;
      let startDate = document.getElementById('start_date').value;
      let endDate = document.getElementById('end_date').value;
      let reason = document.getElementById('reason').value; 

      if(new Date(endDate) < new Date(startDate)) {
          Swal.fire({
              icon: 'error', title: 'Invalid Dates', text: 'End date cannot be earlier than the start date!', confirmButtonColor: '#212529'
          });
          return;
      }

      Swal.fire({
          title: 'Submit Leave Request?',
          html: `You are officially filing for <b>${leaveType}</b> from <b>${startDate}</b> to <b>${endDate}</b>.`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#212529',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, submit to HR'
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
                      Swal.fire({
                          title: 'Submitted!', text: 'Your leave request has been securely forwarded to HR for approval.', icon: 'success', confirmButtonColor: '#212529'
                      }).then(() => { window.location.reload(); });
                  } else {
                      Swal.fire('Error', data.message || 'Could not save the request.', 'error');
                  }
              })
              .catch(error => {
                  Swal.fire('Error', 'An unexpected connection error occurred.', 'error');
              });
          }
      });
  }
</script>