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

$password_error = ''; 

if (isset($_POST['update_user'])) {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'];
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $has_error = false;

    if (!empty($new_password)) {
        if (strlen($new_password) < 8 || !preg_match("/[0-9]/", $new_password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $new_password)) {
            $password_error = 'Password must be at least 8 characters and include a number and a special character.';
            $has_error = true;
        } elseif ($new_password !== $confirm_password) {
            $password_error = 'Passwords do not match. Please try again.';
            $has_error = true;
        }
    }

    if (!$has_error) {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $mysql->prepare("UPDATE user SET first_name=?, last_name=?, email=?, role=?, password=? WHERE user_id=?");
            $stmt->bind_param("sssssi", $fname, $lname, $email, $role, $hashed_password, $id);
        } else {
            $stmt = $mysql->prepare("UPDATE user SET first_name=?, last_name=?, email=?, role=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $fname, $lname, $email, $role, $id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['status_icon'] = 'success';
            $_SESSION['status_title'] = 'Success!';
            $_SESSION['status_text'] = 'User profile updated successfully.';
            header("Location: user_management.php");
            exit();
        } else {
            die("Update Failed: " . $mysql->error);
        }
    }
}

$stmt = $mysql->prepare("SELECT * FROM user WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found in the database.");
}

$title = "Edit User | WorkForcePro";
include('../includes/admin_header.php');
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Edit User Profile</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-dark shadow-sm mt-2">
              <div class="card-header">
                  <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i> Update Information for <?php echo htmlspecialchars($user['first_name']); ?></h3>
              </div>
              
              <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                
                <div class="card-body bg-light">
                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars(isset($_POST['first_name']) ? $_POST['first_name'] : $user['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars(isset($_POST['last_name']) ? $_POST['last_name'] : $user['last_name']); ?>" required>
                    </div>
                  </div>

                  <div class="form-group mt-2">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $user['email']); ?>" required>
                  </div>
                  
                  <hr>

                  <h5 class="font-weight-bold mb-3"><i class="fas fa-lock mr-2"></i> Manual Password Reset</h5>
                  
                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label>New Password <span class="text-muted font-weight-normal" style="font-size: 0.85rem;">(Leave blank to keep)</span></label>
                        <div class="input-group shadow-sm">
                            <input type="password" name="new_password" id="new_password" class="form-control border-right-0 <?php echo !empty($password_error) ? 'is-invalid border-danger' : ''; ?>" placeholder="Enter new password...">
                            <div class="input-group-append">
                                <span class="input-group-text bg-white border-left-0 <?php echo !empty($password_error) ? 'border-danger' : ''; ?>" onclick="togglePassword('new_password', this)" style="cursor: pointer;">
                                    <i class="fas fa-eye text-muted"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Confirm Password</label>
                        <div class="input-group shadow-sm">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control border-right-0 <?php echo !empty($password_error) ? 'is-invalid border-danger' : ''; ?>" placeholder="Re-type new password...">
                            <div class="input-group-append">
                                <span class="input-group-text bg-white border-left-0 <?php echo !empty($password_error) ? 'border-danger' : ''; ?>" onclick="togglePassword('confirm_password', this)" style="cursor: pointer;">
                                    <i class="fas fa-eye text-muted"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                  </div>

                  <?php if (!empty($password_error)): ?>
                      <div class="text-danger mt-1 mb-3 small font-weight-bold">
                          <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $password_error; ?>
                      </div>
                  <?php endif; ?>

                  <hr>

                  <div class="row mt-3">
                    <div class="col-md-12 form-group">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <?php $currentRole = isset($_POST['role']) ? $_POST['role'] : $user['role']; ?>
                            <option value="Employee" <?php echo ($currentRole == 'Employee') ? 'selected' : ''; ?>>Employee</option>
                            <option value="HR Staff" <?php echo ($currentRole == 'HR Staff') ? 'selected' : ''; ?>>HR Staff</option>
                            <option value="Admin" <?php echo ($currentRole == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                  </div>
                  
                </div>
                <div class="card-footer bg-white text-right">
                  <a href="user_management.php" class="btn btn-default shadow-sm mr-2"><i class="fas fa-times mr-1"></i> Cancel</a>
                  <button type="submit" name="update_user" class="btn btn-dark shadow-sm"><i class="fas fa-save mr-1"></i> Save Changes</button>
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