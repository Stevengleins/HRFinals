<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current admin details
$stmt = $mysql->prepare("SELECT first_name, last_name, email, role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$adminProfile = $result->fetch_assoc();
$stmt->close();

$title = "Admin Profile | WorkForcePro";
include('../includes/admin_header.php'); 
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Administrator Profile</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
              
              <div class="card-header bg-dark text-white py-3 border-bottom-0">
                  <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
                      <i class="fas fa-user-circle mr-2"></i> My Account Details
                  </h3>
              </div>
              
              <div class="card-body bg-light">
                <div class="row mb-3">
                  <div class="col-md-4 text-muted font-weight-bold">First Name:</div>
                  <div class="col-md-8 text-dark"><?php echo htmlspecialchars($adminProfile['first_name']); ?></div>
                </div>
                <hr style="opacity: 0.1;">
                
                <div class="row mb-3">
                  <div class="col-md-4 text-muted font-weight-bold">Last Name:</div>
                  <div class="col-md-8 text-dark"><?php echo htmlspecialchars($adminProfile['last_name']); ?></div>
                </div>
                <hr style="opacity: 0.1;">

                <div class="row mb-3">
                  <div class="col-md-4 text-muted font-weight-bold">Email Address:</div>
                  <div class="col-md-8 text-dark"><?php echo htmlspecialchars($adminProfile['email']); ?></div>
                </div>
                <hr style="opacity: 0.1;">

                <div class="row mb-3">
                  <div class="col-md-4 text-muted font-weight-bold">System Role:</div>
                  <div class="col-md-8">
                    <span class="badge bg-danger px-2 py-1"><i class="fas fa-user-shield mr-1"></i> <?php echo htmlspecialchars($adminProfile['role']); ?></span>
                  </div>
                </div>
              </div>
              
              <div class="card-footer bg-white text-right border-top-0 py-3">
                <a href="dashboard.php" class="btn btn-outline-secondary shadow-sm mr-2 px-4" style="border-radius: 6px;">Back</a>
                <a href="admin_edit_profile.php" class="btn btn-primary shadow-sm px-4" style="border-radius: 6px;">
                    <i class="fas fa-edit mr-1"></i> Edit Profile & Password
                </a>
              </div>
              
            </div>
        </div>
    </div>
  </div>
</section>

<?php include('../includes/footer.php'); ?>

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