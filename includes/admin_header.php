<?php
// Ensure the database connection is available to fetch the email and role
require_once(__DIR__ . '/../database.php');

$sidebarFirstName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Admin';
$sidebarLastName = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
$sidebarFullName = trim($sidebarFirstName . ' ' . $sidebarLastName);
if (empty($sidebarFullName)) $sidebarFullName = 'Admin'; // Fallback if both are empty

$sidebarEmail = 'admin@workforce.com'; // Default fallback
$sidebarRole = 'Admin';
$sidebarProfileImage = null;

// Fetch the current user's details from the database if they are logged in
if (isset($_SESSION['user_id'])) {
    $headerStmt = $mysql->prepare("SELECT u.email, u.role, e.profile_image FROM user u LEFT JOIN employee_details e ON u.user_id = e.user_id WHERE u.user_id = ?");
    if ($headerStmt) {
        $headerStmt->bind_param("i", $_SESSION['user_id']);
        $headerStmt->execute();
        $headerResult = $headerStmt->get_result();
        if ($headerResult->num_rows > 0) {
            $headerUser = $headerResult->fetch_assoc();
            $sidebarEmail = $headerUser['email'];
            $sidebarRole = $headerUser['role'];
            $sidebarProfileImage = $headerUser['profile_image'];
        }
        $headerStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($title) ? $title : "Admin Dashboard | WorkForcePro"; ?></title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    function confirmLogout(event) {
        event.preventDefault(); 
        
        Swal.fire({
            title: 'Ready to leave?',
            text: "You will be securely logged out of your Admin session.",
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
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav align-items-center">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4 d-flex flex-column">
    <a href="../Admin/dashboard.php" class="brand-link border-bottom-0">
      <img src="../logo.png" alt="WORKFORCEPRO" class="brand-image" style="opacity: 1; max-height: 35px; border-radius: 4px;" />
      <span class="brand-text font-weight-bold">WORK<span class="font-weight-normal">FORCEPRO</span></span>
    </a>

    <div class="sidebar flex-grow-1 d-flex flex-column">
      
      <div class="user-panel mt-2 pb-3 pt-3 mb-3 d-flex align-items-center justify-content-center shadow-sm" style="background-color: #454d55; border-radius: 8px; margin-left: 4px; margin-right: 4px;">
        <div class="image px-1">
            <a href="../Admin/admin_profile.php">
                <?php if (!empty($sidebarProfileImage)): ?>
                    <img src="../<?php echo htmlspecialchars($sidebarProfileImage); ?>" class="img-circle elevation-2" alt="User Image" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #ffffff;">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($sidebarFullName); ?>&background=343a40&color=ffffff" class="img-circle elevation-2" alt="User Image" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #ffffff;">
                <?php endif; ?>
            </a>
        </div>
        <div class="info d-flex flex-column align-items-start pl-1" style="overflow: hidden; max-width: 160px;">
            <a href="../Admin/admin_profile.php" class="d-block text-white font-weight-bold mb-0 text-truncate" style="font-size: 1rem; line-height: 1.2; text-decoration: none; width: 100%;">
                <?php echo htmlspecialchars($sidebarFullName); ?>
            </a>
            
            <small class="text-light d-block text-truncate" style="width: 100%; font-size: 0.8rem; line-height: 1.2; margin-top: 2px; opacity: 0.8;">
                <?php echo htmlspecialchars($sidebarEmail); ?>
            </small>
            
            <span class="badge bg-danger mt-1" style="font-size: 0.7rem; font-weight: normal; padding: 3px 6px;">
                <i class="fas fa-user-shield mr-1" style="font-size: 0.6rem;"></i> <?php echo htmlspecialchars($sidebarRole); ?>
            </span>
        </div>
      </div>

      <nav class="mt-2 flex-grow-1">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="../Admin/dashboard.php" class="nav-link">
              <i class="nav-icon bi bi-sort-up"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="../Admin/user_management.php" class="nav-link">
              <i class="nav-icon bi bi-people-fill"></i>
              <p>Manage Users</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="../Admin/attendance.php" class="nav-link">
              <i class="nav-icon bi bi-clipboard-check-fill"></i>
              <p>Attendance Reports</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="../Admin/leave_management.php" class="nav-link">
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>Leave Reports</p>
            </a>
          </li>
         <li class="nav-item">
            <a href="../Admin/payroll_admin.php" class="nav-link">
              <i class="nav-icon fas fa-wallet"></i>
              <p>Payroll Reports</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="../Admin/requests.php" class="nav-link">
              <i class="nav-icon fas fa-inbox"></i> 
              <p>Requests</p>
            </a>
          </li>
        </ul>
      </nav>

      <div class="mt-auto pb-3">
        <ul class="nav nav-pills nav-sidebar flex-column">
          <li class="nav-item">
            <a href="#" onclick="confirmLogout(event)" class="nav-link text-danger">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Logout</p>
            </a>
          </li>
        </ul>
      </div>

    </div>
  </aside>

  <div class="content-wrapper">