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
$employee_id_val = '';
$middle_name_val = '';
$gender_val = '';
$birth_date_val = '';
$mobile_number_val = '';
$address_val = '';
$join_date_val = '';
$position_val = '';
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
    $middle_name = trim($_POST['middle_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']); 
    $role       = isset($_POST['role']) ? $_POST['role'] : '';
    $employee_id = trim($_POST['employee_id']);
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $birth_date = $_POST['birth_date'];
    $mobile_number = trim($_POST['mobile_number']);
    $address = trim($_POST['address']);
    $join_date = $_POST['join_date'];
    $position = trim($_POST['position']);
    $status     = 1; 

    $first_name_val = htmlspecialchars($first_name);
    $last_name_val = htmlspecialchars($last_name);
    $email_val = htmlspecialchars($email);
    $role_val = htmlspecialchars($role);
    $employee_id_val = htmlspecialchars($employee_id);
    $middle_name_val = htmlspecialchars($middle_name);
    $gender_val = htmlspecialchars($gender);
    $birth_date_val = htmlspecialchars($birth_date);
    $mobile_number_val = htmlspecialchars($mobile_number);
    $address_val = htmlspecialchars($address);
    $join_date_val = htmlspecialchars($join_date);
    $position_val = htmlspecialchars($position);

    if (!preg_match("/^[a-zA-Z\s\-]+$/", $first_name) || !preg_match("/^[a-zA-Z\s\-]+$/", $last_name)) {
        $errors['name'] = true; 
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Name';
        $_SESSION['status_text'] = 'Names must contain letters only.';
    } elseif (empty($employee_id)) {
        $errors['employee_id'] = true;
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Employee ID Required';
        $_SESSION['status_text'] = 'Please provide an Employee ID.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = true; 
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Email';
        $_SESSION['status_text'] = 'Please provide a valid email address.';
    } elseif (!preg_match("/^\+639\d{9}$/", $mobile_number)) {
        $errors['mobile'] = true;
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Mobile Number';
        $_SESSION['status_text'] = 'Mobile number must start with +639 and be 13 digits long.';
    } elseif (empty($birth_date) || empty($join_date)) {
        $errors['date'] = true;
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Date Required';
        $_SESSION['status_text'] = 'Please provide birth date and join date.';
    } elseif (empty($gender) || empty($position) || empty($address)) {
        $errors['required'] = true;
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Required Fields';
        $_SESSION['status_text'] = 'Please fill in all required fields.';
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
            $checkEmpId = $mysql->prepare("SELECT employee_id FROM user WHERE employee_id = ?");
            $checkEmpId->bind_param("s", $employee_id);
            $checkEmpId->execute();
            if ($checkEmpId->get_result()->num_rows > 0) {
                $errors['employee_id'] = true;
                $_SESSION['status_icon'] = 'error';
                $_SESSION['status_title'] = 'Employee ID Exists';
                $_SESSION['status_text'] = 'An account with that Employee ID already exists.';
            } else {
                $profile_image_path = null;
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $upload_dir = '../uploads/profile_images/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $file_name = uniqid() . '_' . basename($_FILES['profile_image']['name']);
                    $target_file = $upload_dir . $file_name;
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    
                    // Check if image file is a actual image or fake image
                    $check = getimagesize($_FILES['profile_image']['tmp_name']);
                    if ($check !== false) {
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                            $profile_image_path = 'uploads/profile_images/' . $file_name;
                        }
                    }
                }
                
                // Generate and Hash the Temporary Password
                $temp_password = generateTempPassword();
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                
                // Insert into Database
                $insertStmt = $mysql->prepare("INSERT INTO user (first_name, middle_name, last_name, email, password, role, status, employee_id, gender, birth_date, mobile_number, address, join_date, position, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->bind_param("ssssssissssssss", $first_name, $middle_name, $last_name, $email, $hashed_password, $role, $status, $employee_id, $gender, $birth_date, $mobile_number, $address, $join_date, $position, $profile_image_path);
            
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
                        <h3>Welcome to WorkForcePro, " . $first_name . "!</h3>
                        <p>You have been successfully registered in the WorkForcePro site.</p>
                        <p>Here are your system details:</p>
                        <ul>
                            <li><b>Role:</b> " . $role . "</li>
                            <li><b>Email (Login ID):</b> " . $email . "</li>
                            <li><b>Temporary Password:</b> " . $temp_password . "</li>
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
              
              <form method="POST" action="register_user.php" enctype="multipart/form-data">
                <div class="card-body bg-light">
                  
                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control shadow-sm <?php echo isset($errors['employee_id']) ? 'is-invalid' : ''; ?>" placeholder="e.g. EMP001" value="<?php echo $employee_id_val; ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Position</label>
                        <input type="text" name="position" class="form-control shadow-sm <?php echo isset($errors['required']) ? 'is-invalid' : ''; ?>" placeholder="e.g. Software Engineer" value="<?php echo $position_val; ?>" required>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-4 form-group">
                        <label class="text-dark">First Name</label>
                        <input type="text" name="first_name" class="form-control shadow-sm <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" placeholder="e.g. John" value="<?php echo $first_name_val; ?>" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control shadow-sm" placeholder="e.g. Michael" value="<?php echo $middle_name_val; ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Last Name</label>
                        <input type="text" name="last_name" class="form-control shadow-sm <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" placeholder="e.g. Doe" value="<?php echo $last_name_val; ?>" required>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Gender</label>
                        <select name="gender" class="form-control shadow-sm <?php echo isset($errors['required']) ? 'is-invalid' : ''; ?>" required>
                            <option value="" disabled <?php echo empty($gender_val) ? 'selected' : ''; ?>>Select Gender</option>
                            <option value="Male" <?php echo ($gender_val === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($gender_val === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($gender_val === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Birth Date</label>
                        <input type="date" name="birth_date" class="form-control shadow-sm <?php echo isset($errors['date']) ? 'is-invalid' : ''; ?>" value="<?php echo $birth_date_val; ?>" required>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Mobile Number</label>
                        <input type="text" name="mobile_number" class="form-control shadow-sm <?php echo isset($errors['mobile']) ? 'is-invalid' : ''; ?>" placeholder="+639123456789" value="<?php echo $mobile_number_val; ?>" required>
                        <small class="text-muted">Must start with +639</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Join Date</label>
                        <input type="date" name="join_date" class="form-control shadow-sm <?php echo isset($errors['date']) ? 'is-invalid' : ''; ?>" value="<?php echo $join_date_val; ?>" required>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-12 form-group">
                        <label class="text-dark">Address</label>
                        <textarea name="address" class="form-control shadow-sm <?php echo isset($errors['required']) ? 'is-invalid' : ''; ?>" rows="3" placeholder="Full address" required><?php echo $address_val; ?></textarea>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Profile Image</label>
                        <input type="file" name="profile_image" class="form-control shadow-sm" accept="image/*">
                        <small class="text-muted">Optional - Upload profile picture</small>
                    </div>
                    <div class="col-md-6 form-group">
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
