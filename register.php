<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

require 'database.php';

$success = false;
$error_message = ""; // basta para sa swal

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']); 
    $last_name  = trim($_POST['last_name']);
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']); 
    $role       = $_POST['role'];
    $password   = $_POST['password'];

    // --- START VALIDATIONS ---

    // 1. NAMES: Letters only (allows spaces for middle names/hyphens)
    if (!preg_match("/^[a-zA-Z\s\-]+$/", $first_name) || !preg_match("/^[a-zA-Z\s\-]+$/", $last_name)) {
        $error_message = "Names must contain letters only.";
    } 
    // 2. EMAIL: Must include @gmail.com
    elseif (!str_ends_with($email, "@gmail.com")) {
        $error_message = "Only @gmail.com addresses are allowed.";
    }
    // 3. USERNAME: No special characters (Alphanumeric only)
    elseif (!preg_match("/^[a-zA-Z0-9]+$/", $username)) {
        $error_message = "Username cannot contain special characters.";
    }
    // 4. PASSWORD: Min 8 chars, must have a number and a special character
    elseif (strlen($password) < 8 || 
            !preg_match("/[0-9]/", $password) || 
            !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        $error_message = "Password must be at least 8 characters and include a number and a special character.";
    }
    // --- END VALIDATIONS ---

    else {
        // If all validations pass, proceed to check if user exists and then INSERT
        $check = $mysql->prepare("SELECT user_id FROM user WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error_message = "Account already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysql->prepare("INSERT INTO user (first_name, last_name, username, email, role, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $first_name, $last_name, $username, $email, $role, $hash);

            if ($stmt->execute()) {
                $success = true;
            } else {
                $error_message = "Registration failed: " . $mysql->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>WorkForcePro | Registration</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="hold-transition register-page">
<div class="register-box" style="width: 500px;">
  <div class="card card-outline card-primary">
    <div class="card-body">
      <p class="login-box-msg">Register Account</p>
      <form action="" method="post">
        <div class="form-group">
          <label>Assign Role / Job Title</label>
          <select name="role" class="form-control" required>
            <option value="" disabled selected>Select your role...</option>
            <option value="Employee" selected>Employee</option>
            <option value="HR Staff">HR Staff</option>
            <option value="Admin">Admin</option>
          </select>
        </div>
        <div class="row">
          <div class="col-6"><input type="text" name="first_name" class="form-control" placeholder="First Name" required></div>
          <div class="col-6"><input type="text" name="last_name" class="form-control" placeholder="Last Name" required></div>
        </div>
        <div class="form-group mt-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
        <div class="form-group"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
        <div class="form-group"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
        <button type="submit" name="register" class="btn btn-primary btn-block">Register Account</button>
        <p class="mb-1 mt-3 text-center">
            <a href="index.php">Already have an account?</a>
          </p>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($error_message != ""): ?>
        Swal.fire({ 
            icon: 'error', 
            title: 'Registration Failed', 
            text: '<?php echo $error_message; ?>' 
        });
    <?php endif; ?>

    <?php if ($success): ?>
        Swal.fire({
            icon: 'success', 
            title: 'Success!', 
            text: 'Account registered successfully.',
            confirmButtonText: 'Go to Login'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = 'index.php'; }
        });
    <?php endif; ?>
});
</script>