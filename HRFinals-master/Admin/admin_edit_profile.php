<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];

// Fetch current admin details to populate the form
$stmt = $mysql->prepare("SELECT first_name, last_name, email, role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$adminProfile = $result->fetch_assoc();
$stmt->close();

if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!preg_match("/^[a-zA-Z\s\-]+$/", $first_name) || !preg_match("/^[a-zA-Z\s\-]+$/", $last_name)) {
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Name';
        $_SESSION['status_text'] = 'Names must contain letters only.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
                    $_SESSION['status_icon'] = 'error';
                    $_SESSION['status_title'] = 'Weak Password';
                    $_SESSION['status_text'] = 'Password must be at least 8 characters and include a number and a special character.';
                } elseif ($new_password !== $confirm_password) {
                    $_SESSION['status_icon'] = 'error';
                    $_SESSION['status_title'] = 'Password Mismatch';
                    $_SESSION['status_text'] = 'The new passwords do not match. Please try again.';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $updateStmt = $mysql->prepare("UPDATE user SET first_name = ?, last_name = ?, email = ?, password = ? WHERE user_id = ?");
                    $updateStmt->bind_param("ssssi", $first_name, $last_name, $email, $hashed_password, $user_id);
                    
                    if ($updateStmt->execute()) {
                        $_SESSION['first_name'] = $first_name; 
                        $_SESSION['status_icon'] = 'success';
                        $_SESSION['status_title'] = 'Profile Updated!';
                        $_SESSION['status_text'] = 'Your details and password have been updated successfully.';
                        header("Location: admin_profile.php");
                        exit();
                    }
                }
            } else {
                $updateStmt = $mysql->prepare("UPDATE user SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?");
                $updateStmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
                
                if ($updateStmt->execute()) {
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['status_icon'] = 'success';
                    $_SESSION['status_title'] = 'Profile Updated!';
                    $_SESSION['status_text'] = 'Your profile details have been updated successfully.';
                    header("Location: admin_profile.php");
                    exit();
                }
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
              
              <form method="POST" action="admin_edit_profile.php">
                <div class="card-body bg-light">

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">First Name</label>
                        <input type="text" name="first_name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($adminProfile['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Last Name</label>
                        <input type="text" name="last_name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($adminProfile['last_name']); ?>" required>
                    </div>
                  </div>

                  <div class="row mt-2">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Email Address (Login ID)</label>
                        <input type="email" name="email" class="form-control shadow-sm" value="<?php echo htmlspecialchars($adminProfile['email']); ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">System Role</label>
                        <input type="text" class="form-control shadow-sm" value="<?php echo htmlspecialchars($adminProfile['role']); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
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