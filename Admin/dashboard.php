<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

include('../database.php'); 
include('../includes/admin_header.php');

$admin_id = $_SESSION['user_id'];
$adminQuery = "SELECT * FROM user WHERE user_id = '$admin_id'";
$adminResult = $mysql->query($adminQuery);
$adminProfile = $adminResult->fetch_assoc();

$empCount = $mysql->query("SELECT COUNT(*) as count FROM user WHERE role = 'Employee'")->fetch_assoc()['count'];
$hrCount = $mysql->query("SELECT COUNT(*) as count FROM user WHERE role = 'HR Staff'")->fetch_assoc()['count'];
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Admin Dashboard</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    
    <div class="row">
      <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
          <div class="inner">
            <h3><?php echo $empCount; ?></h3>
            <p>Total Employees</p>
          </div>
          <div class="icon">
            <i class="fas fa-users"></i>
          </div>
          <a href="user_management.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      
      <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
          <div class="inner">
            <h3><?php echo $hrCount; ?></h3>
            <p>HR Staff</p>
          </div>
          <div class="icon">
            <i class="fas fa-user-tie"></i>
          </div>
          <a href="user_management.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>

    <div class="row">
        <div class="col-md-4">
    
            <div class="card card-widget widget-user-2 shadow-sm mb-4">
            <div class="widget-user-header bg-dark">
                <div class="widget-user-image">
                <img class="img-circle elevation-2 bg-white" src="https://ui-avatars.com/api/?name=<?php echo urlencode($adminProfile['first_name'] . ' ' . $adminProfile['last_name']); ?>&background=random" alt="User Avatar">
                </div>
                <h3 class="widget-user-username"><?php echo $adminProfile['first_name'] . ' ' . $adminProfile['last_name']; ?></h3>
                <h5 class="widget-user-desc"><?php echo $adminProfile['role']; ?></h5>
            </div>
            <div class="card-footer p-0">
                <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                    Email <span class="float-right badge bg-primary"><?php echo $adminProfile['email']; ?></span>
                    </a>
                </li>
                </ul>
            </div>
            </div>

            <style>
                .calendar-container th { font-size: 0.85rem; color: #6c757d; font-weight: 600; }
                .calendar-container td { width: 14.28%; padding: 7px 5px; cursor: pointer; border-radius: 4px; transition: 0.2s; }
                .calendar-container td:hover { background-color: #f4f5f7; }
                .calendar-container td.current-day { background-color: #007bff; color: white; font-weight: bold; box-shadow: 0 2px 4px rgba(0,123,255,0.3); }
                .calendar-container td.empty-cell:hover { background-color: transparent; cursor: default; }
            </style>

            <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center py-3">
                <h3 class="card-title w-100 mb-1" style="font-size: 1.75rem; font-weight: 700; letter-spacing: 1px;" id="liveClock">00:00:00</h3>
                <p class="mb-0" id="liveDate" style="font-size: 0.9rem; opacity: 0.9;">Loading date...</p>
            </div>
            <div class="card-body p-2">
                <div class="calendar-container p-2">
                    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                        <button class="btn btn-sm btn-light border" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <strong id="monthYear" style="font-size: 1.1rem;">Month Year</strong>
                        <button class="btn btn-sm btn-light border" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <table class="table table-sm table-borderless text-center mb-0">
                        <thead>
                            <tr>
                                <th>Su</th><th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th>
                            </tr>
                        </thead>
                        <tbody id="calendarBody">
                            </tbody>
                    </table>
                </div>
            </div>
            </div>

        </div>

        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Employee Attendance Summary</h3>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="myChart" style="min-height: 500px; height: 500px; max-height: 5000px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Department Distribution</h3>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="secondChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Role Analytics</h3>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="thirdChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

  </div>
</section>

<?php include('../includes/footer.php');?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Bar Chart
  const ctx = document.getElementById('myChart').getContext('2d');
  new Chart(ctx, {
      type: 'bar',
      data: {
          labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
          datasets: [{
              label: 'Attendance',
              data: [12, 19, 3, 5, 2, 3, 10],
              backgroundColor: 'rgba(54, 162, 235, 0.5)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 1
          }]
      },
      options: {
          maintainAspectRatio: false,
          scales: { y: { beginAtZero: true } }
      }
  });
  
  // Doughnut Chart
  const ctx2 = document.getElementById('secondChart').getContext('2d');
  new Chart(ctx2, {
      type: 'doughnut', 
      data: {
          labels: ['HR', 'IT', 'Finance', 'Marketing'],
          datasets: [{
              data: [5, 15, 8, 12],
              backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef'],
          }]
      },
      options: { maintainAspectRatio: false }
  });

  // Line Chart
  const ctx3 = document.getElementById('thirdChart').getContext('2d');
  new Chart(ctx3, {
      type: 'line', 
      data: {
          labels: ['HR', 'Staff', 'Employee'],
          datasets: [{
              label: 'Growth',
              data: [10, 15, 19],
              backgroundColor: 'rgba(60,141,188,0.9)',
              borderColor: 'rgba(60,141,188,0.8)',
              fill: true,
              tension: 0.3
          }]
      },
      options: { maintainAspectRatio: false }
  });

// --- LIVE CLOCK SCRIPT ---
  function updateClock() {
      const now = new Date();
      
      // Format Time (e.g., 04:30:15 PM)
      const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
      document.getElementById('liveClock').innerText = now.toLocaleTimeString('en-US', timeOptions);
      
      // Format Date (e.g., Friday, October 27, 2025)
      const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      document.getElementById('liveDate').innerText = now.toLocaleDateString('en-US', dateOptions);
  }
  setInterval(updateClock, 1000); // Update every 1 second
  updateClock(); // Run immediately on load


  // --- WORKING CALENDAR SCRIPT ---
  let currentMonth = new Date().getMonth();
  let currentYear = new Date().getFullYear();

  function renderCalendar(month, year) {
      const firstDay = new Date(year, month).getDay();
      const daysInMonth = 32 - new Date(year, month, 32).getDate();
      
      const tbl = document.getElementById("calendarBody");
      tbl.innerHTML = ""; // Clear previous cells
      
      // Set the Month and Year header
      document.getElementById("monthYear").innerText = new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      
      let date = 1;
      for (let i = 0; i < 6; i++) {
          let row = document.createElement("tr");
          for (let j = 0; j < 7; j++) {
              let cell = document.createElement("td");
              
              if (i === 0 && j < firstDay) {
                  cell.classList.add("empty-cell");
              } else if (date > daysInMonth) {
                  break;
              } else {
                  cell.innerText = date;
                  
                  // Highlight today's date in blue
                  let today = new Date();
                  if (date === today.getDate() && year === today.getFullYear() && month === today.getMonth()) {
                      cell.classList.add("current-day");
                  }
                  date++;
              }
              row.appendChild(cell);
          }
          tbl.appendChild(row);
      }
  }
  
  // Month Navigation Buttons
  document.getElementById("prevMonth").addEventListener("click", () => {
      currentYear = (currentMonth === 0) ? currentYear - 1 : currentYear;
      currentMonth = (currentMonth === 0) ? 11 : currentMonth - 1;
      renderCalendar(currentMonth, currentYear);
  });
  
  document.getElementById("nextMonth").addEventListener("click", () => {
      currentYear = (currentMonth === 11) ? currentYear + 1 : currentYear;
      currentMonth = (currentMonth + 1) % 12;
      renderCalendar(currentMonth, currentYear);
  });
  
  // Initialize Calendar
  renderCalendar(currentMonth, currentYear);
</script>