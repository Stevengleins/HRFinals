<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Employee Dashboard | WorkForcePro</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    function confirmLogout(event) {
        event.preventDefault(); 
        
        Swal.fire({
            title: 'Ready to leave?',
            text: "You will be securely logged out of your Employee session.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, log me out!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../logout.php';
            }
        });
    }
  </script>
  <style>
  .sidebar-fixed-logout {
      position: relative;
      height: 100%;
      min-height: calc(100vh - 57px);
  }

  .sidebar-logout {
      position: absolute;
      bottom: 15px;
      left: 15px;
      right: 15px;
  }
</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav align-items-center">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      
      <li class="nav-item d-flex align-items-center ml-2 user-panel mt-0 pb-0 mb-0 border-0">
          <?php 
              // Force the header to ONLY use the session variable so it is identical on every page
              $firstName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Employee';
          ?>
          <a href="employee_profile.php" class="d-flex align-items-center text-decoration-none">
              <div class="image pr-1">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($firstName); ?>&background=17a2b8&color=ffffff" class="img-circle elevation-1" alt="User Image" style="width: 32px; height: 32px;">
              </div>
              <div class="info pl-1">
                <span class="d-block font-weight-bold text-dark" style="font-size: 1.05rem;"><?php echo htmlspecialchars($firstName); ?></span>
              </div>
          </a>
      </li>
    </ul>

    
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="employee_dashboard.php" class="brand-link">
      <span class="brand-text font-weight-light ml-4"><strong>WORK</strong>FORCE</span>
    </a>

    <div class="sidebar">
      <nav class="mt-4">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
          <li class="nav-item">
            <a href="employee_dashboard.php" class="nav-link">
              <i class="nav-icon fas fa-home"></i>
              <p>My Dashboard</p>
            </a>
          </li>
        
          </li>
          <li class="nav-item">
            <a href="leave_requests.php" class="nav-link">
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>Leave Requests</p>
            </a>
          </li>
          </li>
          <li class="nav-item">
            <a href="employee_request.php" class="nav-link">
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>Other Requests</p>
            </a>
          </li>
           <li class="nav-item">
            <a href="employee_payroll.php" class="nav-link">
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>Payroll</p>
            </a>
          </li>
        </ul>
      </nav>
      <div class="sidebar-logout">
  <a href="#" class="btn btn-danger btn-block" onclick="confirmLogout(event)">
    <i class="fas fa-sign-out-alt mr-1"></i> Logout
  </a>
</div>
    </div>
  </aside>

  <div class="content-wrapper">