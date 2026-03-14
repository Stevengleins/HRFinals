<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$password_error = ''; 

// Fetch current HR staff details
$stmt = $mysql->prepare("SELECT first_name, last_name, email, role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$hrProfile = $result->fetch_assoc();
$stmt->close();

if (isset($_POST['update_profile'])) {
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Email';
        $_SESSION['status_text'] = 'Please provide a valid email address.';
    } else {
        $checkStmt = $mysql->prepare("SELECT user_id FROM user WHERE email = ? AND user_id != ?");
        $checkStmt->bind_param("si", $email, $user_id);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            $_SESSION['status_icon'] = 'error';
            $_SESSION['status_title'] = 'Email Taken';
            $_SESSION['status_text'] = 'That email is already in use by another account.';
        } else {
            $checkStmt->close();

            if (!empty($new_password)) {
                if (strlen($new_password) < 8 || !preg_match("/[0-9]/", $new_password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $new_password)) {
                    $password_error = 'Password must be at least 8 characters and include a number and a special character.';
                } elseif ($new_password !== $confirm_password) {
                    $password_error = 'Passwords do not match. Please try again.';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $updateStmt = $mysql->prepare("UPDATE user SET email = ?, password = ? WHERE user_id = ?");
                    $updateStmt->bind_param("ssi", $email, $hashed_password, $user_id);
                    
                    if ($updateStmt->execute()) {
                        $_SESSION['status_icon'] = 'success';
                        $_SESSION['status_title'] = 'Settings Updated!';
                        $_SESSION['status_text'] = 'Your email and password have been updated successfully.';
                        header("Location: hr_profile.php");
                        exit();
                    }
                }
            } else {
                $updateStmt = $mysql->prepare("UPDATE user SET email = ? WHERE user_id = ?");
                $updateStmt->bind_param("si", $email, $user_id);
                
                if ($updateStmt->execute()) {
                    $_SESSION['status_icon'] = 'success';
                    $_SESSION['status_title'] = 'Settings Updated!';
                    $_SESSION['status_text'] = 'Your email address has been updated successfully.';
                    header("Location: hr_profile.php");
                    exit();
                }
            }
        }
    }
}

$title = "Edit Profile | WorkForcePro";
include('../includes/hr_header.php'); 
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Edit Profile Settings</h1>
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
                      <i class="fas fa-cog mr-2"></i> Update Preferences
                  </h3>
              </div>
              
              <form method="POST" action="hr_edit_profile.php">
                <div class="card-body bg-light">

                  <div class="alert alert-secondary shadow-sm mb-4" style="border-left: 4px solid #6c757d;">
                      <i class="fas fa-info-circle mr-2"></i> <strong>Note:</strong> Your name and system role are locked. If you need to legally change your name on file, please contact your System Administrator.
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">First Name</label>
                        <input type="text" class="form-control shadow-sm" value="<?php echo htmlspecialchars($hrProfile['first_name']); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Last Name</label>
                        <input type="text" class="form-control shadow-sm" value="<?php echo htmlspecialchars($hrProfile['last_name']); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                  </div>

                  <div class="row mt-2">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Email Address (Login ID)</label>
                        <input type="email" name="email" class="form-control shadow-sm" value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $hrProfile['email']); ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">System Role</label>
                        <input type="text" class="form-control shadow-sm" value="<?php echo htmlspecialchars($hrProfile['role']); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                  </div>

                  <hr class="mt-4 mb-4 border-secondary" style="opacity: 0.2;">

                  <h5 class="font-weight-bold mb-3"><i class="fas fa-lock mr-2"></i> Security (Change Password)</h5>
                  <p class="text-muted small mb-3">Leave these fields blank if you do not want to change your current password.</p>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">New Password</label>
                        <div class="input-group shadow-sm">
                            <input type="password" name="new_password" id="new_password" class="form-control border-right-0 <?php echo !empty($password_error) ? 'is-invalid border-danger' : ''; ?>" placeholder="Minimum 8 characters">
                            <div class="input-group-append">
                                <span class="input-group-text bg-white border-left-0 <?php echo !empty($password_error) ? 'border-danger' : ''; ?>" onclick="togglePassword('new_password', this)" style="cursor: pointer;">
                                    <i class="fas fa-eye text-muted"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Confirm New Password</label>
                        <div class="input-group shadow-sm">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control border-right-0 <?php echo !empty($password_error) ? 'is-invalid border-danger' : ''; ?>" placeholder="Re-type new password">
                            <div class="input-group-append">
                                <span class="input-group-text bg-white border-left-0 <?php echo !empty($password_error) ? 'border-danger' : ''; ?>" onclick="togglePassword('confirm_password', this)" style="cursor: pointer;">
                                    <i class="fas fa-eye text-muted"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                  </div>
                  
                  <?php if (!empty($password_error)): ?>
                      <div class="text-danger mt-1 small font-weight-bold">
                          <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $password_error; ?>
                      </div>
                  <?php endif; ?>
                  
                </div>
                
                <div class="card-footer bg-white text-right border-top-0 py-3">
                  <a href="hr_profile.php" class="btn btn-outline-secondary shadow-sm mr-2 px-4" style="border-radius: 6px;">Cancel</a>
                  <button type="submit" name="update_profile" class="btn btn-dark shadow-sm px-4" style="border-radius: 6px;">
                      <i class="fas fa-save mr-1"></i> Save Changes
                  </button>
                </div>
              </form>
              
            </div>
        </div>
    </div>
  </div>
</section>

<?php include('../includes/footer.php'); ?>

<script>
  function togglePassword(inputId, element) {
      const input = document.getElementById(inputId);
      const icon = element.querySelector('i');
      
      if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
      } else {
          input.type = 'password';
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
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