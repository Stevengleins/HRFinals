<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current admin details from BOTH tables
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
$adminProfile = $result->fetch_assoc();
$stmt->close();

// Smart Fallbacks for the HTML form
$display_first = !empty($adminProfile['first_name']) ? $adminProfile['first_name'] : $adminProfile['u_first'];
$display_middle = !empty($adminProfile['middle_name']) ? $adminProfile['middle_name'] : '';
$display_last  = !empty($adminProfile['last_name']) ? $adminProfile['last_name'] : $adminProfile['u_last'];
$display_suffix = !empty($adminProfile['suffix']) ? $adminProfile['suffix'] : '';
$display_email = !empty($adminProfile['email']) ? $adminProfile['email'] : $adminProfile['u_email'];
$display_role  = !empty($adminProfile['role']) ? $adminProfile['role'] : $adminProfile['u_role'];

// Format the existing shift so the dropdown can pre-select it
$current_shift_start = !empty($adminProfile['shift_start']) ? $adminProfile['shift_start'] : '08:00:00';
$current_shift_end = !empty($adminProfile['shift_end']) ? $adminProfile['shift_end'] : '17:00:00';
$current_shift_val = $current_shift_start . '|' . $current_shift_end;

if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = !empty(trim($_POST['middle_name'])) ? trim($_POST['middle_name']) : null;
    $last_name = trim($_POST['last_name']);
    $suffix = !empty(trim($_POST['suffix'])) ? trim($_POST['suffix']) : null;
    
    $email = trim($_POST['email']);
    $position = trim($_POST['position']);
    $gender = $_POST['gender'] ?? '';
    $birth_date = $_POST['birth_date'];
    $mobile_number = trim($_POST['mobile_number']);
    $address = trim($_POST['address']);
    
    // Process the predefined shift selection
    $shift_schedule = $_POST['shift_schedule'] ?? '08:00:00|17:00:00';
    $shift_parts = explode('|', $shift_schedule);
    $shift_start = $shift_parts[0];
    $shift_end = isset($shift_parts[1]) ? $shift_parts[1] : '17:00:00';
    
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // SMART FORMATTING: Automatically convert "09..." to "+639..."
    if (preg_match("/^09\d{9}$/", $mobile_number)) {
        $mobile_number = '+63' . substr($mobile_number, 1);
    }

    if (!preg_match("/^[a-zA-Z\s\-]+$/", $first_name) || !preg_match("/^[a-zA-Z\s\-]+$/", $last_name)) {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Name';
        $_SESSION['status_text'] = 'Names must contain letters only.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Email';
        $_SESSION['status_text'] = 'Please provide a valid email address.';
    } elseif (!preg_match("/^\+639\d{9}$/", $mobile_number) && !empty($mobile_number)) {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Mobile Number';
        $_SESSION['status_text'] = 'Please enter a valid PH mobile number (e.g., 09123456789).';
    } else {
        $checkStmt = $mysql->prepare("SELECT user_id FROM user WHERE email = ? AND user_id != ?");
        $checkStmt->bind_param("si", $email, $user_id);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            $_SESSION['status_icon'] = 'error';
            $_SESSION['status_title'] = 'Email Taken';
            $_SESSION['status_text'] = 'That email is already in use by another account.';
            $checkStmt->close();
        } else {
            $checkStmt->close();

            // Handle Profile Image Upload
            $profile_image_path = $adminProfile['profile_image']; 
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

            // Determine if updating password
            $hashed_password = $adminProfile['u_password']; 
            $password_updating = false;
            
            if (!empty($new_password)) {
                if (strlen($new_password) < 8 || !preg_match("/[0-9]/", $new_password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $new_password)) {
                    $_SESSION['status_icon'] = 'error';
                    $_SESSION['status_title'] = 'Weak Password';
                    $_SESSION['status_text'] = 'Password must be at least 8 characters and include a number and a special character.';
                    header("Location: admin_edit_profile.php"); exit();
                } elseif ($new_password !== $confirm_password) {
                    $_SESSION['status_icon'] = 'error';
                    $_SESSION['status_title'] = 'Password Mismatch';
                    $_SESSION['status_text'] = 'The new passwords do not match. Please try again.';
                    header("Location: admin_edit_profile.php"); exit();
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_updating = true;
                }
            }

            // ==========================================
            // BEGIN TRANSACTION: Update TWO tables safely
            // ==========================================
            $mysql->begin_transaction();

            try {
                // 1. Update the Main User Table
                $updateUser = $mysql->prepare("UPDATE user SET first_name = ?, last_name = ?, email = ?, password = ? WHERE user_id = ?");
                $updateUser->bind_param("ssssi", $first_name, $last_name, $email, $hashed_password, $user_id);
                $updateUser->execute();

                // 2. Check if Employee Details exist for this user yet
                $checkEmp = $mysql->prepare("SELECT id FROM employee_details WHERE user_id = ?");
                $checkEmp->bind_param("i", $user_id);
                $checkEmp->execute();
                $empResult = $checkEmp->get_result();

                if ($empResult->num_rows > 0) {
                    // Update existing details 
                    $updateEmp = $mysql->prepare("UPDATE employee_details SET first_name=?, middle_name=?, last_name=?, suffix=?, email=?, gender=?, birth_date=?, mobile_number=?, address=?, position=?, profile_image=?, shift_start=?, shift_end=? WHERE user_id=?");
                    $updateEmp->bind_param("sssssssssssssi", $first_name, $middle_name, $last_name, $suffix, $email, $gender, $birth_date, $mobile_number, $address, $position, $profile_image_path, $shift_start, $shift_end, $user_id);
                    $updateEmp->execute();
                } else {
                    // Insert new details (Perfect for old admin accounts)
                    $insertEmp = $mysql->prepare("INSERT INTO employee_details (user_id, first_name, middle_name, last_name, suffix, email, gender, birth_date, mobile_number, address, position, role, profile_image, shift_start, shift_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insertEmp->bind_param("issssssssssssss", $user_id, $first_name, $middle_name, $last_name, $suffix, $email, $gender, $birth_date, $mobile_number, $address, $position, $display_role, $profile_image_path, $shift_start, $shift_end);
                    $insertEmp->execute();
                }

                $mysql->commit();

                $_SESSION['first_name'] = $first_name; 
                $_SESSION['status_icon'] = 'success';
                $_SESSION['status_title'] = 'Profile Updated!';
                $_SESSION['status_text'] = $password_updating ? 'Your details and password have been updated successfully.' : 'Your profile details have been updated successfully.';
                header("Location: admin_profile.php");
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

$title = "Edit Profile | WorkForcePro";
include('../includes/admin_header.php'); 
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Edit Administrator Profile</h1>
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
                      <i class="fas fa-user-edit mr-2"></i> Update Details
                  </h3>
              </div>
              
              <form method="POST" action="admin_edit_profile.php" enctype="multipart/form-data">
                <div class="card-body bg-light">

                  <div class="row">
                    <div class="col-md-3 form-group">
                        <label class="text-dark">First Name</label>
                        <input type="text" name="first_name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($display_first); ?>" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="text-dark">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($display_middle); ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Last Name</label>
                        <input type="text" name="last_name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($display_last); ?>" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="text-dark">Suffix</label>
                        <input type="text" name="suffix" class="form-control shadow-sm" value="<?php echo htmlspecialchars($display_suffix); ?>" placeholder="Jr, Sr">
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Email Address (Login ID)</label>
                        <input type="email" name="email" class="form-control shadow-sm" value="<?php echo htmlspecialchars($display_email); ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Position</label>
                        <input type="text" name="position" class="form-control shadow-sm" value="<?php echo htmlspecialchars($adminProfile['position'] ?? ''); ?>">
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Gender</label>
                        <select name="gender" class="form-control shadow-sm">
                            <option value="" disabled <?php echo empty($adminProfile['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                            <option value="Male" <?php echo (($adminProfile['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (($adminProfile['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (($adminProfile['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Birth Date</label>
                        <input type="date" name="birth_date" class="form-control shadow-sm" value="<?php echo htmlspecialchars($adminProfile['birth_date'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Mobile Number</label>
                        <input type="text" name="mobile_number" class="form-control shadow-sm" placeholder="09123456789" maxlength="13" value="<?php echo htmlspecialchars($adminProfile['mobile_number'] ?? ''); ?>">
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-12 form-group">
                        <label class="text-dark">Address</label>
                        <textarea name="address" class="form-control shadow-sm" rows="2" placeholder="Full address"><?php echo htmlspecialchars($adminProfile['address'] ?? ''); ?></textarea>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark"><i class="fas fa-clock mr-1 text-primary"></i> Assigned Shift Schedule</label>
                        <select name="shift_schedule" class="form-control shadow-sm" required>
                            <optgroup label="First Shift (Day Shift)">
                                <option value="07:00:00|15:00:00" <?php echo ($current_shift_val === '07:00:00|15:00:00') ? 'selected' : ''; ?>>07:00 AM - 03:00 PM</option>
                                <option value="08:00:00|17:00:00" <?php echo ($current_shift_val === '08:00:00|17:00:00') ? 'selected' : ''; ?>>08:00 AM - 05:00 PM (Standard)</option>
                                <option value="09:00:00|18:00:00" <?php echo ($current_shift_val === '09:00:00|18:00:00') ? 'selected' : ''; ?>>09:00 AM - 06:00 PM</option>
                            </optgroup>
                            <optgroup label="Second Shift (Afternoon/Evening)">
                                <option value="15:00:00|23:00:00" <?php echo ($current_shift_val === '15:00:00|23:00:00') ? 'selected' : ''; ?>>03:00 PM - 11:00 PM</option>
                                <option value="16:00:00|00:00:00" <?php echo ($current_shift_val === '16:00:00|00:00:00') ? 'selected' : ''; ?>>04:00 PM - 12:00 AM</option>
                            </optgroup>
                            <optgroup label="Third Shift (Night Shift)">
                                <option value="23:00:00|07:00:00" <?php echo ($current_shift_val === '23:00:00|07:00:00') ? 'selected' : ''; ?>>11:00 PM - 07:00 AM</option>
                                <option value="00:00:00|08:00:00" <?php echo ($current_shift_val === '00:00:00|08:00:00') ? 'selected' : ''; ?>>12:00 AM - 08:00 AM</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="col-md-3 form-group">
                        <label class="text-dark">System Role</label>
                        <input type="text" class="form-control shadow-sm" value="<?php echo htmlspecialchars($display_role); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="text-dark">Profile Image</label>
                        <input type="file" name="profile_image" class="form-control shadow-sm" accept="image/*" style="padding-bottom: 35px;">
                    </div>
                  </div>

                  <hr class="mt-4 mb-4 border-secondary" style="opacity: 0.2;">

                  <h5 class="font-weight-bold mb-3"><i class="fas fa-lock mr-2"></i> Security (Change Password)</h5>
                  <p class="text-muted small mb-3">Leave these fields blank if you do not want to change your current password.</p>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">New Password</label>
                        <input type="password" name="new_password" class="form-control shadow-sm" placeholder="Minimum 8 characters">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control shadow-sm" placeholder="Re-type new password">
                    </div>
                  </div>
                  
                </div>
                
                <div class="card-footer bg-white text-right border-top-0 py-3">
                  <a href="admin_profile.php" class="btn btn-outline-secondary shadow-sm mr-2 px-4" style="border-radius: 6px;">Cancel</a>
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

<?php
if (isset($_SESSION['status_icon'])) {
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