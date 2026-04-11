<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$password_error = ''; 

// Fetch from BOTH tables using a LEFT JOIN
$stmt = $mysql->prepare("
    SELECT 
        u.first_name as u_first, u.last_name as u_last, u.email as u_email, u.role as u_role, u.password as u_password,
        e.* FROM user u 
    LEFT JOIN employee_details e ON u.user_id = e.user_id 
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employeeProfile = $result->fetch_assoc();
$stmt->close();

// Smart Fallbacks & Full Name Construction
$display_first = !empty($employeeProfile['first_name']) ? $employeeProfile['first_name'] : $employeeProfile['u_first'];
$display_middle = !empty($employeeProfile['middle_name']) ? $employeeProfile['middle_name'] : '';
$display_last  = !empty($employeeProfile['last_name']) ? $employeeProfile['last_name'] : $employeeProfile['u_last'];
$display_suffix = !empty($employeeProfile['suffix']) ? $employeeProfile['suffix'] : '';
$full_display_name = trim(preg_replace('/\s+/', ' ', "$display_first $display_middle $display_last $display_suffix"));

$display_email = !empty($employeeProfile['email']) ? $employeeProfile['email'] : $employeeProfile['u_email'];
$display_role  = !empty($employeeProfile['role']) ? $employeeProfile['role'] : $employeeProfile['u_role'];

// Format the shift for read-only display
$shift_start = !empty($employeeProfile['shift_start']) ? $employeeProfile['shift_start'] : '08:00:00';
$shift_end = !empty($employeeProfile['shift_end']) ? $employeeProfile['shift_end'] : '17:00:00';
$formatted_shift = date('h:i A', strtotime($shift_start)) . ' - ' . date('h:i A', strtotime($shift_end));

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

            $password_updating = false;
            $hashed_password = $employeeProfile['u_password'];

            // Server-side fallback validation just in case they bypass JS
            if (!empty($new_password)) {
                if (strlen($new_password) < 8 || !preg_match("/[0-9]/", $new_password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $new_password)) {
                    $password_error = 'Password must be at least 8 characters and include a number and a special character.';
                } elseif ($new_password !== $confirm_password) {
                    $password_error = 'Passwords do not match. Please try again.';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_updating = true;
                }
            }

            if (empty($password_error)) {
                $mysql->begin_transaction();

                try {
                    $updateUser = $mysql->prepare("UPDATE user SET email = ?, password = ? WHERE user_id = ?");
                    $updateUser->bind_param("ssi", $email, $hashed_password, $user_id);
                    $updateUser->execute();

                    $updateEmp = $mysql->prepare("UPDATE employee_details SET email = ? WHERE user_id = ?");
                    $updateEmp->bind_param("si", $email, $user_id);
                    $updateEmp->execute();

                    $mysql->commit();

                    $_SESSION['status_icon'] = 'success';
                    $_SESSION['status_title'] = 'Settings Updated!';
                    $_SESSION['status_text'] = $password_updating ? 'Your email and password have been updated successfully.' : 'Your email address has been updated successfully.';
                    header("Location: employee_profile.php");
                    exit();

                } catch (Exception $e) {
                    $mysql->rollback();
                    $_SESSION['status_icon'] = 'error';
                    $_SESSION['status_title'] = 'Update Failed';
                    $_SESSION['status_text'] = 'A database error occurred: ' . $e->getMessage();
                }
            }
        }
    }
}

$title = "Edit Profile | WorkForcePro";
include('../includes/employee_header.php'); 
?>

<style>
    /* Custom styles for validity feedback */
    .input-group .form-control.is-valid { border-color: #28a745; background-image: none; }
    .input-group .form-control.is-invalid { border-color: #dc3545; background-image: none; }
    .input-group .input-group-text.is-valid { border-color: #28a745; }
    .input-group .input-group-text.is-invalid { border-color: #dc3545; }
</style>

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
              
              <form id="editProfileForm" method="POST" action="employee_edit_profile.php" novalidate>
                <div class="card-body bg-light">

                  <div class="alert alert-secondary shadow-sm mb-4" style="border-left: 4px solid #6c757d;">
                      <i class="fas fa-info-circle mr-2"></i> <strong>Note:</strong> Your name, system role, and shift are locked. If you need to modify these, please contact your HR or System Administrator.
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Full Name</label>
                        <input type="text" class="form-control shadow-sm font-weight-bold text-dark" value="<?php echo htmlspecialchars($full_display_name); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark"><i class="fas fa-clock mr-1 text-primary"></i> Assigned Shift</label>
                        <input type="text" class="form-control shadow-sm font-weight-bold text-primary" value="<?php echo $formatted_shift; ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                  </div>

                  <div class="row mt-2">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">System Role</label>
                        <input type="text" class="form-control shadow-sm text-dark" value="<?php echo htmlspecialchars($display_role); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Email Address (Login ID)</label>
                        <input type="email" name="email" class="form-control shadow-sm" value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $display_email); ?>" required>
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
                        <small id="pwd_hint" class="form-text text-muted">Must be 8+ chars with a number and special character.</small>
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
                        <small id="cpwd_hint" class="form-text text-muted">Must perfectly match the password above.</small>
                    </div>
                  </div>
                  
                  <?php if (!empty($password_error)): ?>
                      <div class="text-danger mt-1 small font-weight-bold">
                          <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $password_error; ?>
                      </div>
                  <?php endif; ?>
                  
                </div>
                
                <div class="card-footer bg-white text-right border-top-0 py-3">
                  <a href="employee_profile.php" class="btn btn-outline-secondary shadow-sm mr-2 px-4" style="border-radius: 6px;">Cancel</a>
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

  // Instant Inline Password Validation
  document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('editProfileForm');
      const pwdInput = document.getElementById('new_password');
      const cpwdInput = document.getElementById('confirm_password');
      const pwdHint = document.getElementById('pwd_hint');
      const cpwdHint = document.getElementById('cpwd_hint');
      
      // Select the adjacent span to color the border correctly
      const pwdAddon = pwdInput.nextElementSibling.querySelector('span');
      const cpwdAddon = cpwdInput.nextElementSibling.querySelector('span');

      function validatePassword() {
          const pwd = pwdInput.value;
          if (pwd === '') {
              pwdInput.classList.remove('is-valid', 'is-invalid');
              pwdAddon.classList.remove('is-valid', 'is-invalid');
              pwdHint.className = 'form-text text-muted';
              pwdHint.innerText = 'Must be 8+ chars with a number and special character.';
              return true;
          }
          
          const hasNum = /[0-9]/.test(pwd);
          const hasSpec = /[!@#$%^&*(),.?":{}|<>]/.test(pwd);
          const isLong = pwd.length >= 8;

          if (hasNum && hasSpec && isLong) {
              pwdInput.classList.remove('is-invalid');
              pwdInput.classList.add('is-valid');
              pwdAddon.classList.remove('is-invalid');
              pwdAddon.classList.add('is-valid');
              pwdHint.className = 'form-text text-success font-weight-bold';
              pwdHint.innerText = 'Strong password!';
              return true;
          } else {
              pwdInput.classList.remove('is-valid');
              pwdInput.classList.add('is-invalid');
              pwdAddon.classList.remove('is-valid');
              pwdAddon.classList.add('is-invalid');
              pwdHint.className = 'form-text text-danger font-weight-bold';
              pwdHint.innerText = 'Weak: Need 8+ chars, 1 number, 1 special character.';
              return false;
          }
      }

      function validateConfirm() {
          const pwd = pwdInput.value;
          const cpwd = cpwdInput.value;
          
          if (cpwd === '' && pwd === '') {
              cpwdInput.classList.remove('is-valid', 'is-invalid');
              cpwdAddon.classList.remove('is-valid', 'is-invalid');
              cpwdHint.className = 'form-text text-muted';
              cpwdHint.innerText = 'Must perfectly match the password above.';
              return true;
          }
          
          if (cpwd === pwd && pwd !== '') {
              cpwdInput.classList.remove('is-invalid');
              cpwdInput.classList.add('is-valid');
              cpwdAddon.classList.remove('is-invalid');
              cpwdAddon.classList.add('is-valid');
              cpwdHint.className = 'form-text text-success font-weight-bold';
              cpwdHint.innerText = 'Passwords match!';
              return true;
          } else if (cpwd !== '') {
              cpwdInput.classList.remove('is-valid');
              cpwdInput.classList.add('is-invalid');
              cpwdAddon.classList.remove('is-valid');
              cpwdAddon.classList.add('is-invalid');
              cpwdHint.className = 'form-text text-danger font-weight-bold';
              cpwdHint.innerText = 'Passwords do not match.';
              return false;
          }
          return false;
      }

      pwdInput.addEventListener('input', () => { 
          validatePassword(); 
          if(cpwdInput.value !== '') validateConfirm(); 
      });
      
      cpwdInput.addEventListener('input', validateConfirm);

      form.addEventListener('submit', function(e) {
          if (pwdInput.value !== '') {
              const isPwdValid = validatePassword();
              const isCpwdValid = validateConfirm();
              
              if (!isPwdValid || !isCpwdValid) {
                  e.preventDefault();
                  Swal.fire({
                      icon: 'error',
                      title: 'Invalid Password',
                      text: 'Please ensure your new password meets the security requirements and matches the confirmation box.',
                      confirmButtonColor: '#212529'
                  });
              }
          }
      });
  });
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