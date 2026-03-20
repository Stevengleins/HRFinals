<?php
session_start();

// 1. Strict Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}

// 2. Connect to Database 
require '../database.php'; 

// 3. Include the Header 
$title = "Employee Dashboard | WorkForcePro";
require '../includes/employee_header.php'; 

// 4. Fetch User Data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE user_id = '$user_id'";
$result = $mysql->query($query);
$employee = $result->fetch_assoc();

// 5. CONNECTION: Fetch Leave Stats
$vacationQuery = $mysql->query("SELECT COUNT(*) as total FROM leave_requests WHERE user_id = '$user_id' AND leave_type = 'Vacation Leave'");
$vacationCount = $vacationQuery->fetch_assoc()['total'] ?? 0;

$sickQuery = $mysql->query("SELECT COUNT(*) as total FROM leave_requests WHERE user_id = '$user_id' AND leave_type = 'Sick Leave'");
$sickCount = $sickQuery->fetch_assoc()['total'] ?? 0;

$pendingQuery = $mysql->query("SELECT COUNT(*) as total FROM leave_requests WHERE user_id = '$user_id' AND status = 'Pending'");
$pendingTasks = $pendingQuery->fetch_assoc()['total'] ?? 0;

// 6. CONNECTION: Check Today's Attendance Status
date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
$checkAttendance = $mysql->query("SELECT * FROM attendance WHERE user_id = '$user_id' AND date = '$today'");
$attendance = $checkAttendance->fetch_assoc();
$hasClockedIn = !empty($attendance['time_in']);
$hasClockedOut = !empty($attendance['time_out']);
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-end">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Dashboard</h1>
        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($employee['first_name']); ?>!</p>
      </div>
      <div class="col-sm-6 text-right d-none d-sm-block">
        <div class="d-inline-block bg-white border px-3 py-2 shadow-sm" style="border-radius: 8px;">
            <i class="far fa-clock mr-2 text-dark"></i>
            <span class="font-weight-bold" id="header-clock"><?php echo date('h:i A'); ?></span>
            <span class="text-muted ml-2 small uppercase font-weight-bold">| <?php echo date('M d, Y'); ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="row">
      <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-hourglass-half"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Pending Requests</span>
            <span class="info-box-number text-lg"><?php echo $pendingTasks; ?></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-teal elevation-1 text-white"><i class="fas fa-plane"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Vacation Leaves</span>
            <span class="info-box-number text-lg"><?php echo $vacationCount; ?></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-maroon elevation-1"><i class="fas fa-briefcase-medical"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Sick Leaves</span>
            <span class="info-box-number text-lg"><?php echo $sickCount; ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-5">
            <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
                <div class="card-header bg-dark text-white py-3">
                    <h3 class="card-title font-weight-bold" style="font-size: 1.1rem;">
                        <i class="fas fa-fingerprint mr-2"></i> Attendance Control
                    </h3>
                </div>
                <div class="card-body text-center py-5">
                    <button class="btn btn-success btn-lg btn-block mb-3 shadow-sm font-weight-bold py-3" 
                            style="border-radius: 10px; letter-spacing: 1px;" 
                            onclick="timePunch('Time In')" <?php echo $hasClockedIn ? 'disabled' : ''; ?>>
                        <i class="fas fa-sign-in-alt mr-2"></i> <?php echo $hasClockedIn ? 'ALREADY CLOCKED IN' : 'CLOCK IN'; ?>
                    </button>
                    
                    <button class="btn btn-danger btn-lg btn-block shadow-sm font-weight-bold py-3" 
                            style="border-radius: 10px; letter-spacing: 1px;" 
                            onclick="timePunch('Time Out')" <?php echo ($hasClockedOut || !$hasClockedIn) ? 'disabled' : ''; ?>>
                        <i class="fas fa-sign-out-alt mr-2"></i> <?php echo $hasClockedOut ? 'ALREADY CLOCKED OUT' : 'CLOCK OUT'; ?>
                    </button>

                    <?php if($hasClockedIn): ?>
                        <div class="mt-4 p-2 bg-light rounded border">
                            <span class="text-muted small uppercase font-weight-bold">Shift Start:</span><br>
                            <span class="h5 font-weight-bold text-dark"><?php echo date('h:i A', strtotime($attendance['time_in'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 mt-3" style="border-radius: 8px;">
                <div class="card-body p-3">
                    <a href="../Employee/leave_requests.php" class="btn btn-outline-dark btn-block font-weight-bold" style="border-radius: 6px;">
                        <i class="fas fa-calendar-plus mr-2"></i> File New Leave Request
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <style>
                .calendar-container th { font-size: 0.85rem; color: #6c757d; font-weight: 700; text-transform: uppercase; }
                .calendar-container td { width: 14.28%; padding: 15px 5px; cursor: default; border-radius: 8px; transition: 0.2s; font-size: 1.1rem; } 
                .calendar-container td.current-day { background-color: #001f3f; color: white; font-weight: bold; }
                .calendar-container td:not(.current-day):hover { background-color: #f8f9fa; }
            </style>

            <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
              <div class="card-header bg-white border-bottom py-3">
                 <div class="d-flex justify-content-between align-items-center px-2">
                    <h3 class="card-title font-weight-bold text-dark m-0" id="monthYear">Month Year</h3>
                    <div>
                        <button class="btn btn-xs btn-light border mr-1" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <button class="btn btn-xs btn-light border" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
              </div>
              <div class="card-body p-3">
                <div class="calendar-container">
                    <table class="table table-sm table-borderless text-center mb-0 w-100">
                        <thead><tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr></thead>
                        <tbody id="calendarBody"></tbody>
                    </table>
                </div>
              </div>
            </div>
        </div>
    </div>
  </div>
</section>

<script>
  // Sync header clock
  function updateHeaderClock() {
      const now = new Date();
      const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
      document.getElementById('header-clock').innerText = timeStr;
  }
  setInterval(updateHeaderClock, 1000);

  function timePunch(action) {
      let color = action === 'Time In' ? '#28a745' : '#dc3545';
      
      Swal.fire({
          title: `Confirm ${action}`,
          text: `Are you sure you want to log your ${action.toLowerCase()}?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: color,
          cancelButtonColor: '#6c757d',
          confirmButtonText: `Yes, ${action}!`
      }).then((result) => {
          if (result.isConfirmed) {
              let formData = new FormData();
              formData.append('action', action);

              fetch('process_attendance.php', {
                  method: 'POST',
                  body: formData
              })
              .then(response => response.json())
              .then(data => {
                  if (data.status === 'success') {
                      Swal.fire({
                          icon: 'success',
                          title: 'Success!',
                          text: data.message,
                          confirmButtonColor: '#212529'
                      }).then(() => { location.reload(); });
                  } else {
                      Swal.fire('Warning', data.message, 'warning');
                  }
              })
              .catch(() => {
                  Swal.fire('Error', 'Connection failed.', 'error');
              });
          }
      });
  }

  // Calendar Engine
  let cMonth = new Date().getMonth(), cYear = new Date().getFullYear();
  function renderCal(m, y) {
      const first = new Date(y, m).getDay(), days = 32 - new Date(y, m, 32).getDate();
      const tbl = document.getElementById("calendarBody"); tbl.innerHTML = "";
      document.getElementById("monthYear").innerText = new Date(y, m).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      let d = 1;
      for (let i = 0; i < 6; i++) {
          let row = document.createElement("tr");
          for (let j = 0; j < 7; j++) {
              let cell = document.createElement("td");
              if (i === 0 && j < first) { cell.innerText = ""; } 
              else if (d > days) { cell.innerText = ""; }
              else {
                  cell.innerText = d;
                  let t = new Date();
                  if (d === t.getDate() && y === t.getFullYear() && m === t.getMonth()) cell.classList.add("current-day");
                  d++;
              }
              row.appendChild(cell);
          }
          tbl.appendChild(row);
      }
  }
  document.getElementById("prevMonth").onclick = () => { cYear = cMonth===0?cYear-1:cYear; cMonth = cMonth===0?11:cMonth-1; renderCal(cMonth, cYear); };
  document.getElementById("nextMonth").onclick = () => { cYear = cMonth===11?cYear+1:cYear; cMonth = (cMonth+1)%12; renderCal(cMonth, cYear); };
  renderCal(cMonth, cYear);
</script>

<?php include '../includes/footer.php'; ?>