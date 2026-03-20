<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
} else if (isset($_POST['user_id'])) { 
    $id = (int)$_POST['user_id'];
} else {
    $_SESSION['status_icon'] = 'error';
    $_SESSION['status_title'] = 'Error';
    $_SESSION['status_text'] = 'No User ID provided.';
    header("Location: user_management.php");
    exit();
}

// Fetch user details from BOTH tables
$stmt = $mysql->prepare("
    SELECT 
        u.first_name as u_first, u.last_name as u_last, u.email as u_email, u.role as u_role, u.password as u_password,
        e.* FROM user u 
    LEFT JOIN employee_details e ON u.user_id = e.user_id 
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$userProfile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userProfile) {
    die("User not found in the database.");
}

// Smart Fallbacks
$display_first = !empty($userProfile['first_name']) ? $userProfile['first_name'] : $userProfile['u_first'];
$display_last  = !empty($userProfile['last_name']) ? $userProfile['last_name'] : $userProfile['u_last'];
$display_email = !empty($userProfile['email']) ? $userProfile['email'] : $userProfile['u_email'];
$display_role  = !empty($userProfile['role']) ? $userProfile['role'] : $userProfile['u_role'];

$password_error = ''; 

if (isset($_POST['update_user'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'];
    $position = trim($_POST['position']);
    $gender = $_POST['gender'] ?? '';
    $birth_date = $_POST['birth_date'];
    $mobile_number = trim($_POST['mobile_number']);
    $address = trim($_POST['address']);
    
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // SMART FORMATTING: Convert "09..." to "+639..."
    if (preg_match("/^09\d{9}$/", $mobile_number)) {
        $mobile_number = '+63' . substr($mobile_number, 1);
    }

    $has_error = false;

    // Validate Password if provided
    if (!empty($new_password)) {
        if (strlen($new_password) < 8 || !preg_match("/[0-9]/", $new_password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $new_password)) {
            $password_error = 'Password must be at least 8 characters and include a number and a special character.';
            $has_error = true;
        } elseif ($new_password !== $confirm_password) {
            $password_error = 'Passwords do not match. Please try again.';
            $has_error = true;
        }
    }

    // Check Email Uniqueness
    $checkStmt = $mysql->prepare("SELECT user_id FROM user WHERE email = ? AND user_id != ?");
    $checkStmt->bind_param("si", $email, $id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Email Taken';
        $_SESSION['status_text'] = 'That email is already in use by another account.';
        $has_error = true;
    }
    $checkStmt->close();

    if (!$has_error) {
        
        // Handle Profile Image Upload
        $profile_image_path = $userProfile['profile_image']; 
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_dir = '../uploads/profile_images/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
            $file_name = uniqid() . '_' . basename($_FILES['profile_image']['name']);
            $target_file = $upload_dir . $file_name;
            if (getimagesize($_FILES['profile_image']['tmp_name']) !== false) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $profile_image_path = 'uploads/profile_images/' . $file_name;
                }
            }
        }

        $hashed_password = !empty($new_password) ? password_hash($new_password, PASSWORD_DEFAULT) : $userProfile['u_password'];

        // Transaction for Both Tables
        $mysql->begin_transaction();

        try {
            // Update User Table
            $updateUser = $mysql->prepare("UPDATE user SET first_name=?, last_name=?, email=?, role=?, password=? WHERE user_id=?");
            $updateUser->bind_param("sssssi", $first_name, $last_name, $email, $role, $hashed_password, $id);
            $updateUser->execute();

            // Update Employee Details Table
            $checkEmp = $mysql->prepare("SELECT id FROM employee_details WHERE user_id = ?");
            $checkEmp->bind_param("i", $id);
            $checkEmp->execute();
            if ($checkEmp->get_result()->num_rows > 0) {
                $updateEmp = $mysql->prepare("UPDATE employee_details SET first_name=?, middle_name=?, last_name=?, email=?, gender=?, birth_date=?, mobile_number=?, address=?, position=?, role=?, profile_image=? WHERE user_id=?");
                $updateEmp->bind_param("sssssssssssi", $first_name, $middle_name, $last_name, $email, $gender, $birth_date, $mobile_number, $address, $position, $role, $profile_image_path, $id);
                $updateEmp->execute();
            } else {
                // If it's an old user without details, insert them
                $insertEmp = $mysql->prepare("INSERT INTO employee_details (user_id, first_name, middle_name, last_name, email, gender, birth_date, mobile_number, address, position, role, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insertEmp->bind_param("isssssssssss", $id, $first_name, $middle_name, $last_name, $email, $gender, $birth_date, $mobile_number, $address, $position, $role, $profile_image_path);
                $insertEmp->execute();
            }

            $mysql->commit();

            $_SESSION['status_icon'] = 'success';
            $_SESSION['status_title'] = 'Success!';
            $_SESSION['status_text'] = 'User profile updated successfully.';
            header("Location: user_management.php");
            exit();

        } catch (Exception $e) {
            $mysql->rollback();
            $_SESSION['status_icon'] = 'error';
            $_SESSION['status_title'] = 'Error';
            $_SESSION['status_text'] = 'Database update failed: ' . $e->getMessage();
        }
    }
}

$title = "Edit User | WorkForcePro";
include('../includes/admin_header.php');
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Edit Employee Profile</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mt-2" style="border-radius: 8px; overflow: hidden;">
              <div class="card-header bg-dark text-white py-3 border-bottom-0">
                  <h3 class="card-title m-0 font-weight-bold"><i class="fas fa-user-edit mr-2"></i> Update Information for <?php echo htmlspecialchars($display_first); ?></h3>
              </div>
              
              <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                
                <div class="card-body bg-light">
                  
                  <div class="row">
                    <div class="col-md-4 form-group">
                        <label class="text-dark">First Name</label>
                        <input type="text" name="first_name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($display_first); ?>" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($userProfile['middle_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Last Name</label>
                        <input type="text" name="last_name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($display_last); ?>" required>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Email Address (Login ID)</label>
                        <input type="email" name="email" class="form-control shadow-sm" value="<?php echo htmlspecialchars($display_email); ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Position</label>
                        <input type="text" name="position" class="form-control shadow-sm" value="<?php echo htmlspecialchars($userProfile['position'] ?? ''); ?>">
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Gender</label>
                        <select name="gender" class="form-control shadow-sm">
                            <option value="" disabled <?php echo empty($userProfile['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                            <option value="Male" <?php echo (($userProfile['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (($userProfile['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (($userProfile['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Birth Date</label>
                        <input type="date" name="birth_date" class="form-control shadow-sm" value="<?php echo htmlspecialchars($userProfile['birth_date'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Mobile Number</label>
                        <input type="text" name="mobile_number" class="form-control shadow-sm" placeholder="09123456789" maxlength="13" value="<?php echo htmlspecialchars($userProfile['mobile_number'] ?? ''); ?>">
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-12 form-group">
                        <label class="text-dark">Address</label>
                        <textarea name="address" class="form-control shadow-sm" rows="2" placeholder="Full address"><?php echo htmlspecialchars($userProfile['address'] ?? ''); ?></textarea>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">System Role</label>
                        <select name="role" class="form-control shadow-sm" required>
                            <option value="Employee" <?php echo ($display_role == 'Employee') ? 'selected' : ''; ?>>Employee (Standard Access)</option>
                            <option value="HR Staff" <?php echo ($display_role == 'HR Staff') ? 'selected' : ''; ?>>HR Staff (Leave/Payroll Access)</option>
                            <option value="Admin" <?php echo ($display_role == 'Admin') ? 'selected' : ''; ?>>System Admin (Full Access)</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Profile Image</label>
                        <input type="file" name="profile_image" class="form-control shadow-sm" accept="image/*">
                        <small class="text-muted">Upload a new picture to change</small>
                    </div>
                  </div>
                  
                  <hr class="mt-4 mb-4 border-secondary" style="opacity: 0.2;">

                  <h5 class="font-weight-bold mb-3"><i class="fas fa-lock mr-2"></i> Manual Password Reset</h5>
                  <p class="text-muted small mb-3">Leave these fields blank if you do not want to change this user's password.</p>
                  
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
                        <label class="text-dark">Confirm Password</label>
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
                
                <div class="card-footer bg-white text-right py-3 border-top-0">
                  <a href="user_management.php" class="btn btn-outline-secondary shadow-sm mr-2 px-4" style="border-radius: 6px;">Cancel</a>
                  <button type="submit" name="update_user" class="btn btn-dark shadow-sm px-4" style="border-radius: 6px;"><i class="fas fa-save mr-1"></i> Save Changes</button>
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