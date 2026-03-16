<?php
session_start();
require_once('../database.php');

// 1. Load Composer's autoloader
require '../vendor/autoload.php';

// 2. Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 3. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$first_name_val = '';
$last_name_val = '';
$email_val = '';
$role_val = '';
$errors = [];

// Function to generate a random 10-character password
function generateTempPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $specials = '!@#$%^&*()';
    
    // Guarantee at least one of each required type
    $pwd = $chars[rand(0, strlen($chars)-1)] . 
           $numbers[rand(0, strlen($numbers)-1)] . 
           $specials[rand(0, strlen($specials)-1)];
           
    $all = $chars . $numbers . $specials;
    for ($i = 3; $i < $length; $i++) {
        $pwd .= $all[rand(0, strlen($all)-1)];
    }
    return str_shuffle($pwd);
}

if (isset($_POST['register_user'])) {
    $first_name = trim($_POST['first_name']); 
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']); 
    $role       = isset($_POST['role']) ? $_POST['role'] : '';
    $status     = 1; 

    $first_name_val = htmlspecialchars($first_name);
    $last_name_val = htmlspecialchars($last_name);
    $email_val = htmlspecialchars($email);
    $role_val = htmlspecialchars($role);

    if (!preg_match("/^[a-zA-Z\s\-]+$/", $first_name) || !preg_match("/^[a-zA-Z\s\-]+$/", $last_name)) {
        $errors['name'] = true; 
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Name';
        $_SESSION['status_text'] = 'Names must contain letters only.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = true; 
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Email';
        $_SESSION['status_text'] = 'Please provide a valid email address.';
    } else {
        $checkStmt = $mysql->prepare("SELECT email FROM user WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $errors['email'] = true;
            $_SESSION['status_icon'] = 'error';
            $_SESSION['status_title'] = 'Email Exists';
            $_SESSION['status_text'] = 'An account with that email already exists.';
        } else {
            $checkStmt->close();
            
            // Generate and Hash the Temporary Password
            $temp_password = generateTempPassword();
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
            
            // Insert into Database
            $insertStmt = $mysql->prepare("INSERT INTO user (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("sssssi", $first_name, $last_name, $email, $hashed_password, $role, $status);
            
            if ($insertStmt->execute()) {
                
                // === PHPMailer Setup ===
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    
                    // IMPORTANT: You MUST change these two lines to your actual Google App Password details
                    // Otherwise, Google will keep giving you the "SMTP Error: Could not authenticate" error.
                    $mail->Username   = 'samontetn@gmail.com'; 
                    $mail->Password   = 'knekilwtrlbjcfkw';     
                    
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('samontetn@gmail.com', 'WorkForcePro Admin'); // Change this to your Gmail too
                    $mail->addAddress($email, $first_name . ' ' . $last_name);

                    $mail->isHTML(true);
                    $mail->Subject = 'You are registered in WorkForcePro';
                    $mail->Body    = "
                        <h3>Welcome to WorkForcePro, {$first_name}!</h3>
                        <p>You have been successfully registered in the WorkForcePro site.</p>
                        <p>Here are your system details:</p>
                        <ul>
                            <li><b>Role:</b> {$role}</li>
                            <li><b>Email (Login ID):</b> {$email}</li>
                            <li><b>Temporary Password:</b> {$temp_password}</li>
                        </ul>
                        <p>Please log in using these credentials. You can change your password in your profile settings after logging in.</p>
                        <br>
                        <p>Best Regards,<br>System Administrator</p>
                    ";

                    $mail->send();

                    $_SESSION['status_icon'] = 'success';
                    $_SESSION['status_title'] = 'Account Created!';
                    $_SESSION['status_text'] = 'The user was registered and their password has been emailed to them.';
                    
                } catch (Exception $e) {
                    $_SESSION['status_icon'] = 'warning';
                    $_SESSION['status_title'] = 'Registered, but Email Failed';
                    $_SESSION['status_text'] = "User created, but the email failed to send. Error: {$mail->ErrorInfo}. Temp Password is: $temp_password";
                }
                
                header("Location: user_management.php");
                exit();
                
            } else {
                $_SESSION['status_icon'] = 'error';
                $_SESSION['status_title'] = 'Database Error';
                $_SESSION['status_text'] = 'Failed to register user: ' . $mysql->error;
            }
        }
    }
}

$title = "Register User | WorkForcePro";
include('../includes/admin_header.php'); 
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Register New User</h1>
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
                      <i class="fas fa-envelope-open-text mr-2"></i> Employee Details
                  </h3>
              </div>
              
              <form method="POST" action="register_user.php">
                <div class="card-body bg-light">
                  
                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">First Name</label>
                        <input type="text" name="first_name" class="form-control shadow-sm <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" placeholder="e.g. John" value="<?php echo $first_name_val; ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Last Name</label>
                        <input type="text" name="last_name" class="form-control shadow-sm <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" placeholder="e.g. Doe" value="<?php echo $last_name_val; ?>" required>
                    </div>
                  </div>

                  <div class="row mt-2">
                    <div class="col-md-12 form-group">
                        <label class="text-dark">Email Address (Login ID)</label>
                        <input type="email" name="email" class="form-control shadow-sm <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" placeholder="name@company.com" value="<?php echo $email_val; ?>" required>
                        <small class="text-muted">A randomly generated password will be emailed to this address.</small>
                    </div>
                  </div>

                  <hr class="mt-4 mb-4 border-secondary" style="opacity: 0.2;">

                  <div class="form-group w-50">
                    <label class="text-dark">Assign System Role</label>
                    <select name="role" class="form-control shadow-sm" required>
                        <option value="" disabled <?php echo empty($role_val) ? 'selected' : ''; ?>>Select a role...</option>
                        <option value="Employee" <?php echo ($role_val === 'Employee') ? 'selected' : ''; ?>>Employee (Standard Access)</option>
                        <option value="HR Staff" <?php echo ($role_val === 'HR Staff') ? 'selected' : ''; ?>>HR Staff (Leave/Payroll Access)</option>
                        <option value="Admin" <?php echo ($role_val === 'Admin') ? 'selected' : ''; ?>>System Admin (Full Access)</option>
                    </select>
                  </div>
                  
                </div>
                
                <div class="card-footer bg-white text-right border-top-0 py-3">
                  <a href="user_management.php" class="btn btn-outline-secondary shadow-sm mr-2 px-4" style="border-radius: 6px;">Cancel</a>
                  <button type="submit" name="register_user" class="btn btn-dark shadow-sm px-4" style="border-radius: 6px;">
                      <i class="fas fa-paper-plane mr-1"></i> Register & Send Email
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