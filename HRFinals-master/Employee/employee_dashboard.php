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
require '../includes/employee_header.php'; 

// 4. Fetch User Data
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE user_id = '$user_id'";
$result = $mysql->query($query);
$employee = $result->fetch_assoc();
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0" style="color: #333; font-weight: 600;">Welcome back, <?php echo htmlspecialchars($employee['first_name']); ?>!</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="row">
      <div class="col-lg-4 col-6">
        <div class="small-box bg-primary shadow-sm" style="border-radius: 8px;">
          <div class="inner">
            <h3>3</h3>
            <p>Pending Tasks</p>
          </div>
          <div class="icon"><i class="fas fa-clipboard-list"></i></div>
        </div>
      </div>
      <div class="col-lg-4 col-6">
        <div class="small-box bg-teal shadow-sm" style="border-radius: 8px;">
          <div class="inner">
            <h3>12</h3>
            <p>Vacation Leaves</p>
          </div>
          <div class="icon"><i class="fas fa-plane"></i></div>
        </div>
      </div>
      <div class="col-lg-4 col-12">
        <div class="small-box bg-maroon shadow-sm" style="border-radius: 8px;">
          <div class="inner">
            <h3>5</h3>
            <p>Sick Leaves</p>
          </div>
          <div class="icon"><i class="fas fa-briefcase-medical"></i></div>
        </div>
      </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            
            <div class="card card-widget widget-user-2 shadow-sm mb-4" style="border-radius: 8px; overflow: hidden;">
              <div class="widget-user-header bg-navy">
                <div class="widget-user-image">
                  <img class="img-circle elevation-2 bg-white" src="https://ui-avatars.com/api/?name=<?php echo urlencode($employee['first_name'] . ' ' . $employee['last_name']); ?>&background=random" alt="User Avatar">
                </div>
                <h3 class="widget-user-username font-weight-bold"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h3>
                <h5 class="widget-user-desc" style="opacity: 0.8;"><?php echo htmlspecialchars($employee['role']); ?></h5>
              </div>
              <div class="card-footer p-0">
                <ul class="nav flex-column">
                  <li class="nav-item">
                    <a href="#" class="nav-link text-dark">
                      <i class="fas fa-envelope mr-2 text-muted"></i> Email 
                      <span class="float-right badge bg-primary"><?php echo htmlspecialchars($employee['email']); ?></span>
                    </a>
                  </li>
                </ul>
              </div>
            </div>

            <div class="card card-outline card-primary shadow-sm" style="border-radius: 8px;">
                <div class="card-header border-bottom-0">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-fingerprint mr-2 text-primary"></i> Daily Time Record</h3>
                </div>
                <div class="card-body text-center py-4">
                    <button class="btn btn-success btn-lg btn-block mb-3 shadow-sm font-weight-bold" style="border-radius: 6px; letter-spacing: 1px;" onclick="timePunch('Time In')">
                        <i class="fas fa-sign-in-alt mr-2"></i> CLOCK IN
                    </button>
                    <button class="btn btn-danger btn-lg btn-block shadow-sm font-weight-bold" style="border-radius: 6px; letter-spacing: 1px;" onclick="timePunch('Time Out')">
                        <i class="fas fa-sign-out-alt mr-2"></i> CLOCK OUT
                    </button>
                </div>
            </div>

        </div>

        <div class="col-md-7">
            <style>
                .calendar-container th { font-size: 0.9rem; color: #6c757d; font-weight: 600; padding-bottom: 10px; }
                .calendar-container td { width: 14.28%; padding: 18px 5px; cursor: pointer; border-radius: 6px; transition: 0.2s; font-size: 1.1rem; } 
                .calendar-container td:hover { background-color: #f4f5f7; }
                .calendar-container td.current-day { background-color: #001f3f; color: white; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1); } /* Uses Navy for current day */
            </style>

            <div class="card shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
              <div class="card-header bg-navy text-white text-center py-4 border-bottom-0">
                <h3 class="card-title w-100 mb-1" style="font-size: 2.2rem; font-weight: 700; letter-spacing: 1px;" id="liveClock">00:00:00</h3>
                <p class="mb-0" id="liveDate" style="font-size: 1.1rem; opacity: 0.85;">Loading date...</p>
              </div>
              <div class="card-body p-3 d-flex flex-column justify-content-center">
                <div class="calendar-container">
                    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                        <button class="btn btn-sm btn-light border shadow-sm" style="border-radius: 6px;" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <strong id="monthYear" style="font-size: 1.25rem; color: #333;">Month Year</strong>
                        <button class="btn btn-sm btn-light border shadow-sm" style="border-radius: 6px;" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <table class="table table-sm table-borderless text-center mb-0 w-100">
                        <thead><tr><th>Su</th><th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th></tr></thead>
                        <tbody id="calendarBody"></tbody>
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

<script>
  // SweetAlert Time Punch Logic
  function timePunch(action) {
      let color = action === 'Time In' ? '#28a745' : '#dc3545';
      
      Swal.fire({
          title: `Confirm ${action}`,
          text: `Log your ${action.toLowerCase()} for today?`,
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
                      Swal.fire('Recorded!', data.message, 'success');
                  } else {
                      Swal.fire('Oops!', data.message, 'warning');
                  }
              })
              .catch(error => {
                  Swal.fire('Error', 'Could not connect to the server.', 'error');
              });
          }
      });
  }

  function updateClock() {
      const now = new Date();
      document.getElementById('liveClock').innerText = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
      document.getElementById('liveDate').innerText = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
  }
  setInterval(updateClock, 1000); updateClock();

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
              if (i === 0 && j < first) { /* empty */ } 
              else if (d > days) break;
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
  document.getElementById("prevMonth").addEventListener("click", () => { cYear = cMonth===0?cYear-1:cYear; cMonth = cMonth===0?11:cMonth-1; renderCal(cMonth, cYear); });
  document.getElementById("nextMonth").addEventListener("click", () => { cYear = cMonth===11?cYear+1:cYear; cMonth = (cMonth+1)%12; renderCal(cMonth, cYear); });
  renderCal(cMonth, cYear);
</script>
</body>
</html> 