<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch from BOTH tables using a LEFT JOIN
$stmt = $mysql->prepare("
    SELECT 
        u.first_name as u_first, u.last_name as u_last, u.email as u_email, u.role as u_role, 
        e.* FROM user u 
    LEFT JOIN employee_details e ON u.user_id = e.user_id 
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employeeProfile = $result->fetch_assoc();
$stmt->close();

// Smart Fallbacks (In case the Employee doesn't have an employee_details record yet)
$display_first = !empty($employeeProfile['first_name']) ? $employeeProfile['first_name'] : $employeeProfile['u_first'];
$display_last  = !empty($employeeProfile['last_name']) ? $employeeProfile['last_name'] : $employeeProfile['u_last'];
$display_email = !empty($employeeProfile['email']) ? $employeeProfile['email'] : $employeeProfile['u_email'];
$display_role  = !empty($employeeProfile['role']) ? $employeeProfile['role'] : $employeeProfile['u_role'];

// Format the shift for display
$shift_start = !empty($employeeProfile['shift_start']) ? $employeeProfile['shift_start'] : '08:00:00';
$shift_end = !empty($employeeProfile['shift_end']) ? $employeeProfile['shift_end'] : '17:00:00';
$formatted_shift = date('h:i A', strtotime($shift_start)) . ' - ' . date('h:i A', strtotime($shift_end));

// THE ULTIMATE FIX: Check if image physically exists
$rawImagePath = trim((string)$employeeProfile['profile_image']);
$validImage = (!empty($rawImagePath) && file_exists(__DIR__ . '/../' . $rawImagePath)) ? $rawImagePath : '';

$title = "My Profile | WorkForcePro";
include('../includes/employee_header.php'); 
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">My Profile</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
              
              <div class="card-header bg-dark text-white py-3 border-bottom-0 d-flex align-items-center">
                  <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
                      <i class="fas fa-user-circle mr-2"></i> Account Details
                  </h3>
              </div>
              
              <div class="card-body bg-light">
                
                <div class="text-center mb-4">
                    <?php if($validImage !== ''): ?>
                        <img src="../<?php echo htmlspecialchars($validImage); ?>" class="img-circle elevation-2" alt="User Image" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #ffffff;">
                    <?php else: ?>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($display_first . ' ' . $display_last); ?>&background=17a2b8&color=ffffff" class="img-circle elevation-2" alt="User Image" style="width: 120px; height: 120px; border: 3px solid #ffffff;">
                    <?php endif; ?>
                    <h4 class="mt-3 font-weight-bold text-dark"><?php echo htmlspecialchars($display_first . ' ' . $display_last); ?></h4>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($employeeProfile['position'] ?? 'Position Not Set'); ?></p>
                    <span class="badge bg-info px-2 py-1 mt-2"><i class="fas fa-briefcase mr-1"></i> <?php echo htmlspecialchars($display_role); ?></span>
                </div>

                <hr style="opacity: 0.1;">

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <span class="text-muted font-weight-bold d-block"><i class="fas fa-envelope mr-2"></i>Email Address</span>
                            <span class="text-dark ml-4"><?php echo htmlspecialchars($display_email); ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted font-weight-bold d-block"><i class="fas fa-phone mr-2"></i>Mobile Number</span>
                            <span class="text-dark ml-4"><?php echo htmlspecialchars($employeeProfile['mobile_number'] ?? 'Not Provided'); ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted font-weight-bold d-block"><i class="fas fa-map-marker-alt mr-2"></i>Address</span>
                            <span class="text-dark ml-4"><?php echo nl2br(htmlspecialchars($employeeProfile['address'] ?? 'Not Provided')); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <span class="text-muted font-weight-bold d-block"><i class="fas fa-clock mr-2"></i>Assigned Shift</span>
                            <span class="text-primary font-weight-bold ml-4"><?php echo $formatted_shift; ?></span>
                        </div>

                        <div class="mb-3">
                            <span class="text-muted font-weight-bold d-block"><i class="fas fa-venus-mars mr-2"></i>Gender</span>
                            <span class="text-dark ml-4"><?php echo htmlspecialchars($employeeProfile['gender'] ?? 'Not Provided'); ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted font-weight-bold d-block"><i class="fas fa-birthday-cake mr-2"></i>Birth Date</span>
                            <span class="text-dark ml-4"><?php echo !empty($employeeProfile['birth_date']) ? date('F d, Y', strtotime($employeeProfile['birth_date'])) : 'Not Provided'; ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted font-weight-bold d-block"><i class="fas fa-calendar-check mr-2"></i>Join Date</span>
                            <span class="text-dark ml-4"><?php echo !empty($employeeProfile['join_date']) ? date('F d, Y', strtotime($employeeProfile['join_date'])) : 'Not Provided'; ?></span>
                        </div>
                    </div>
                </div>

              </div>
              
              <div class="card-footer bg-white text-right border-top-0 py-3">
                <a href="employee_dashboard.php" class="btn btn-outline-secondary shadow-sm mr-2 px-4" style="border-radius: 6px;">Back</a>
                <a href="employee_edit_profile.php" class="btn btn-primary shadow-sm px-4" style="border-radius: 6px;">
                    <i class="fas fa-edit mr-1"></i> Edit Settings
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