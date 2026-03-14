<?php
session_start();
require_once('../database.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// 1. Get the ID
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($mysql, $_GET['id']);
} else if (isset($_POST['user_id'])) { 
    $id = mysqli_real_escape_string($mysql, $_POST['user_id']);
} else {
    die("No User ID provided.");
}

// 2. Handle Update Logic
if (isset($_POST['update_user'])) {
    $fname = mysqli_real_escape_string($mysql, $_POST['first_name']);
    $lname = mysqli_real_escape_string($mysql, $_POST['last_name']);
    $email = mysqli_real_escape_string($mysql, $_POST['email']);
    $role  = mysqli_real_escape_string($mysql, $_POST['role']);
    $status = (int)$_POST['status']; 
    $is_verified = (int)$_POST['is_verified']; 
    
    // FIX 1: Safely check if password exists to prevent errors
    $new_password = isset($_POST['password']) ? $_POST['password'] : '';

    // Construct Query
    $updateQuery = "UPDATE user SET 
                    first_name='$fname', 
                    last_name='$lname', 
                    email='$email', 
                    role='$role', 
                    status='$status', 
                    is_verified='$is_verified'";

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updateQuery .= ", password='$hashed_password'";
    }

    $updateQuery .= " WHERE user_id='$id'";
    
    // Execute and Check
    if ($mysql->query($updateQuery)) {
        // FIX 2: Send Success Alert to the next page using Sessions
        $_SESSION['status_icon'] = 'success';
        $_SESSION['status_title'] = 'Success!';
        $_SESSION['status_text'] = 'User updated successfully.';
        header("Location: user_management.php");
        exit();
    } else {
        die("Update Failed: " . $mysql->error);
    }
}

// 3. Fetch current data
$fetchQuery = "SELECT * FROM user WHERE user_id = '$id'";
$res = $mysql->query($fetchQuery);
$user = $res->fetch_assoc();

include('../includes/header.php');
?>

<section class="content">
  <div class="container-fluid">
    <div class="card card-warning mt-4">
      <div class="card-header"><h3 class="card-title">Edit User</h3></div>
      <form method="POST">
        <input type="hidden" name="user_id" value="<?php echo $id; ?>">
        
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
                <label>First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
            </div>
            <div class="col-md-6">
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-md-6">
                <label>Account Status</label>
                <select name="status" class="form-control">
                  <option value="1" <?php echo ($user['status'] == 1) ? 'selected' : ''; ?>>Active</option>
                  <option value="0" <?php echo ($user['status'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-6">
                <label>Verification</label>
                <select name="is_verified" class="form-control">
                  <option value="1" <?php echo ($user['is_verified'] == 1) ? 'selected' : ''; ?>>Verified</option>
                  <option value="0" <?php echo ($user['is_verified'] == 0) ? 'selected' : ''; ?>>Unverified</option>
                </select>
            </div>
          </div>

          <div class="form-group mt-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
          </div>
          
          <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control">
                <option value="Employee" <?php echo ($user['role'] == 'Employee') ? 'selected' : ''; ?>>Employee</option>
                <option value="HR Staff" <?php echo ($user['role'] == 'HR Staff') ? 'selected' : ''; ?>>HR Staff</option>
                <option value="Admin" <?php echo ($user['role'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" name="update_user" class="btn btn-warning">Save Changes</button>
          <a href="user_management.php" class="btn btn-default">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</section>
<?php include('../includes/footer.php'); ?>