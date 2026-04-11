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
$middle_name_val = '';
$last_name_val = '';
$suffix_val = '';
$email_val = '';
$role_val = '';
$gender_val = '';
$birth_date_val = '';
$mobile_number_val = '';
$address_val = '';
$position_val = '';
$errors = [];

// Function to generate a random 10-character password
function generateTempPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $specials = '!@#$%^&*()';
    
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
    $middle_name = !empty(trim($_POST['middle_name'])) ? trim($_POST['middle_name']) : null;
    $last_name  = trim($_POST['last_name']);
    $suffix = !empty(trim($_POST['suffix'])) ? trim($_POST['suffix']) : null;
    
    $email = trim($_POST['email']); 
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $position = trim($_POST['position']);
    $address = trim($_POST['address']);
    
    // Process the predefined shift selection
    $shift_schedule = $_POST['shift_schedule'] ?? '08:00:00|17:00:00';
    $shift_parts = explode('|', $shift_schedule);
    $shift_start = $shift_parts[0];
    $shift_end = isset($shift_parts[1]) ? $shift_parts[1] : '17:00:00';

    $status = 1; 

    // Convert MMM/DD/YYYY from Flatpickr back to YYYY-MM-DD for Database
    $raw_date = $_POST['birth_date'];
    $date_obj = DateTime::createFromFormat('M/d/Y', $raw_date);
    $birth_date = $date_obj ? $date_obj->format('Y-m-d') : '';

    // Strip spaces AND DASHES from the masked mobile input 
    $mobile_number = str_replace([' ', '-'], '', trim($_POST['mobile_number']));

    date_default_timezone_set('Asia/Manila');
    $join_date = date('Y-m-d'); 

    // Retain values for form repopulation on error
    $first_name_val = htmlspecialchars($first_name);
    $last_name_val = htmlspecialchars($last_name);
    $middle_name_val = htmlspecialchars($middle_name ?? '');
    $suffix_val = htmlspecialchars($suffix ?? '');
    $email_val = htmlspecialchars($email);
    $role_val = htmlspecialchars($role);
    $gender_val = htmlspecialchars($gender);
    $birth_date_val = htmlspecialchars($raw_date); 
    $mobile_number_val = htmlspecialchars($_POST['mobile_number']); 
    $address_val = htmlspecialchars($address);
    $position_val = htmlspecialchars($position);

    // Backend Validation
    if (!preg_match("/^[a-zA-Z\s\-]+$/", $first_name) || !preg_match("/^[a-zA-Z\s\-]+$/", $last_name)) {
        $errors['name'] = true; 
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Name';
        $_SESSION['status_text'] = 'First and Last names must contain letters only.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = true; 
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Email';
        $_SESSION['status_text'] = 'Please provide a valid email address.';
    } elseif (!preg_match("/^\+63\d{10}$/", $mobile_number)) {
        $errors['mobile'] = true;
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Invalid Mobile Number';
        $_SESSION['status_text'] = 'Please enter a valid 11-digit PH mobile number.';
    } elseif (empty($birth_date)) {
        $errors['date'] = true;
        $_SESSION['status_icon'] = 'error';
        $_SESSION['status_title'] = 'Date Required';
        $_SESSION['status_text'] = 'Please provide a valid birth date.';
    } elseif (empty($gender) || empty($position) || empty($address) || empty($role)) {
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
            $profile_image_path = null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $upload_dir = '../uploads/profile_images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $file_name = uniqid() . '_' . basename($_FILES['profile_image']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (getimagesize($_FILES['profile_image']['tmp_name']) !== false) {
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                        $profile_image_path = 'uploads/profile_images/' . $file_name;
                    }
                }
            }
            
            $temp_password = generateTempPassword();
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
            
            $mysql->begin_transaction();

            try {
                // Insert Login Info
                $insertUser = $mysql->prepare("INSERT INTO user (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
                $insertUser->bind_param("sssssi", $first_name, $last_name, $email, $hashed_password, $role, $status);
                $insertUser->execute();
                
                $new_user_id = $mysql->insert_id;

                // Insert Details including Shift Start and Shift End
                $insertEmp = $mysql->prepare("INSERT INTO employee_details (user_id, first_name, middle_name, last_name, suffix, email, gender, birth_date, mobile_number, address, join_date, position, role, profile_image, shift_start, shift_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insertEmp->bind_param("isssssssssssssss", $new_user_id, $first_name, $middle_name, $last_name, $suffix, $email, $gender, $birth_date, $mobile_number, $address, $join_date, $position, $role, $profile_image_path, $shift_start, $shift_end);
                $insertEmp->execute();

                $mysql->commit();
                
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'samontetn@gmail.com'; 
                    $mail->Password   = 'knekilwtrlbjcfkw';    
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('samontetn@gmail.com', 'WorkForcePro Admin'); 
                    $mail->addAddress($email, $first_name . ' ' . $last_name);

                    $mail->isHTML(true);
                    $mail->Subject = 'Official System Credentials - WorkForcePro';
                    
                    // CRISP PURE WHITE EMAIL TEMPLATE WITH EMBEDDED LOGO
                    $mail->Body = "
                    <div style=\"font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #cccccc; border-radius: 4px; overflow: hidden; background-color: #ffffff;\">
                        <div style=\"background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 2px solid #222222;\">
                            <img src=\"cid:logo\" alt=\"WORKFORCEPRO\" style=\"max-height: 35px; border-radius: 4px; vertical-align: middle; margin-right: 8px;\" />
                            <h2 style=\"margin: 0; font-size: 22px; color: #111111; letter-spacing: 1px; display: inline-block; vertical-align: middle;\">
                                <strong>WORK</strong><span style=\"font-weight: normal;\">FORCEPRO</span>
                            </h2>
                        </div>
                        <div style=\"padding: 30px;\">
                            <h2 style=\"color: #111111; margin-top: 0;\">Welcome, {$first_name}!</h2>
                            <p style=\"color: #333333; line-height: 1.6; font-size: 14px;\">You have been successfully registered in the <strong>WorkForcePro</strong> system by an administrator. Below are your official system credentials:</p>
                            
                            <div style=\"background-color: #ffffff; border: 1px solid #cccccc; border-left: 4px solid #222222; padding: 15px; margin: 25px 0; border-radius: 4px;\">
                                <p style=\"margin: 5px 0; font-size: 14px; color: #111;\"><strong>Assigned Role:</strong> {$role}</p>
                                <p style=\"margin: 5px 0; font-size: 14px; color: #111;\"><strong>Login Email:</strong> {$email}</p>
                                <p style=\"margin: 5px 0; font-size: 14px; color: #111;\"><strong>Temporary Password:</strong> <span style=\"background: #f4f4f4; padding: 3px 8px; border: 1px solid #ddd; border-radius: 3px; font-family: monospace; font-size: 15px;\">{$temp_password}</span></p>
                            </div>
                            
                            <p style=\"color: #333333; line-height: 1.6; font-size: 14px;\">For security reasons, please log in immediately and update your password from your profile settings.</p>
                            <br>
                            <p style=\"color: #555555; font-size: 13px; margin-bottom: 0;\">Best Regards,<br><strong style=\"color:#111;\">System Administrator</strong></p>
                        </div>
                        <div style=\"background-color: #ffffff; border-top: 1px solid #cccccc; padding: 15px; text-align: center;\">
                            <p style=\"color: #888888; font-size: 11px; margin: 0;\">This is an automatically generated electronic statement. Do not reply to this email.</p>
                        </div>
                    </div>";

                    // EMBED THE LOGO SO IT SHOWS UP IN GMAIL
                    $mail->addEmbeddedImage('../logo.png', 'logo');

                    $mail->send();

                    $_SESSION['status_icon'] = 'success';
                    $_SESSION['status_title'] = 'Account Created!';
                    $_SESSION['status_text'] = 'The user was registered and their password has been emailed to them.';
                    
                } catch (Exception $e) {
                    $_SESSION['status_icon'] = 'warning';
                    $_SESSION['status_title'] = 'Registered, but Email Failed';
                    $_SESSION['status_text'] = "User created, but email failed. Error: {$mail->ErrorInfo}. Temp Password: $temp_password";
                }
                
                header("Location: user_management.php");
                exit();
                
            } catch (Exception $e) {
                $mysql->rollback();
                $_SESSION['status_icon'] = 'error';
                $_SESSION['status_title'] = 'Database Error';
                $_SESSION['status_text'] = 'Failed to register user. Error: ' . $e->getMessage();
            }
        }
    }
}

$title = "Register User | WorkForcePro";
include('../includes/admin_header.php'); 
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
    /* Custom styles for validity feedback */
    .form-control.is-valid { 
        border-color: #28a745; 
        background-image: none; 
    }
    .form-control.is-invalid { 
        border-color: #dc3545; 
        background-image: none; 
    }
</style>

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
        <div class="col-md-9">
            <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
              
              <div class="card-header bg-dark text-white py-3 border-bottom-0">
                  <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
                      <i class="fas fa-envelope-open-text mr-2"></i> Employee Details
                  </h3>
              </div>
              
              <form id="registerForm" method="POST" action="register_user.php" enctype="multipart/form-data" novalidate>
                <div class="card-body bg-light">
                  
                  <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Email Address (Login ID) <span class="text-danger">*</span></label>
                        <input type="email" id="email" name="email" class="form-control shadow-sm <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" placeholder="name@company.com" value="<?php echo $email_val; ?>" required>
                        <div class="invalid-feedback">Please provide a valid email format.</div>
                        <small class="text-muted">A randomly generated password will be emailed here.</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Position <span class="text-danger">*</span></label>
                        <input type="text" id="position" name="position" class="form-control shadow-sm <?php echo isset($errors['required']) ? 'is-invalid' : ''; ?>" placeholder="e.g. Software Engineer" value="<?php echo $position_val; ?>" required>
                        <div class="invalid-feedback">Position is required.</div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-3 form-group">
                        <label class="text-dark">First Name <span class="text-danger">*</span></label>
                        <input type="text" id="first_name" name="first_name" class="form-control shadow-sm <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" placeholder="e.g. John" value="<?php echo $first_name_val; ?>" required pattern="[A-Za-z\s\-]+">
                        <div class="invalid-feedback">Valid first name is required.</div>
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="text-dark">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name" class="form-control shadow-sm" placeholder="Optional" value="<?php echo $middle_name_val; ?>" pattern="[A-Za-z\s\-]*">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Last Name <span class="text-danger">*</span></label>
                        <input type="text" id="last_name" name="last_name" class="form-control shadow-sm <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" placeholder="e.g. Doe" value="<?php echo $last_name_val; ?>" required pattern="[A-Za-z\s\-]+">
                        <div class="invalid-feedback">Valid last name is required.</div>
                    </div>
                    <div class="col-md-2 form-group">
                        <label class="text-dark">Suffix</label>
                        <input type="text" id="suffix" name="suffix" class="form-control shadow-sm" placeholder="Jr, Sr" value="<?php echo $suffix_val; ?>">
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Gender <span class="text-danger">*</span></label>
                        <select id="gender" name="gender" class="form-control shadow-sm <?php echo isset($errors['required']) ? 'is-invalid' : ''; ?>" required>
                            <option value="" disabled <?php echo empty($gender_val) ? 'selected' : ''; ?>>Select Gender</option>
                            <option value="Male" <?php echo ($gender_val === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($gender_val === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($gender_val === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <div class="invalid-feedback">Please select a gender.</div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Birth Date <span class="text-danger">*</span></label>
                        <input type="text" id="birth_date" name="birth_date" class="form-control shadow-sm bg-white <?php echo isset($errors['date']) ? 'is-invalid' : ''; ?>" placeholder="MMM/DD/YYYY" value="<?php echo $birth_date_val; ?>" required>
                        <div class="invalid-feedback">Please select a valid date.</div>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="text-dark">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" id="mobile_number" name="mobile_number" class="form-control shadow-sm <?php echo isset($errors['mobile']) ? 'is-invalid' : ''; ?>" placeholder="+63 000 000 0000" value="<?php echo $mobile_number_val; ?>" required>
                        <div class="invalid-feedback">Must be a valid 10-digit number.</div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-12 form-group">
                        <label class="text-dark">Address <span class="text-danger">*</span></label>
                        <textarea id="address" name="address" class="form-control shadow-sm <?php echo isset($errors['required']) ? 'is-invalid' : ''; ?>" rows="3" placeholder="Full address" required><?php echo $address_val; ?></textarea>
                        <div class="invalid-feedback">Address cannot be empty.</div>
                    </div>
                  </div>

                  <hr class="mt-4 mb-4 border-secondary" style="opacity: 0.2;">

                  <div class="row">
                    <div class="col-md-12">
                        <h5 class="font-weight-bold mb-3 text-dark"><i class="fas fa-clock mr-2 text-primary"></i> Work Schedule & Access</h5>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label class="text-dark">Assigned Shift Schedule <span class="text-danger">*</span></label>
                        <select name="shift_schedule" class="form-control shadow-sm" required>
                            <option value="" disabled selected>Select a predefined shift...</option>
                            <optgroup label="First Shift (Day Shift)">
                                <option value="07:00:00|15:00:00">07:00 AM - 03:00 PM</option>
                                <option value="08:00:00|17:00:00">08:00 AM - 05:00 PM (Standard)</option>
                                <option value="09:00:00|18:00:00">09:00 AM - 06:00 PM</option>
                            </optgroup>
                            <optgroup label="Second Shift (Afternoon/Evening)">
                                <option value="15:00:00|23:00:00">03:00 PM - 11:00 PM</option>
                                <option value="16:00:00|00:00:00">04:00 PM - 12:00 AM</option>
                            </optgroup>
                            <optgroup label="Third Shift (Night Shift)">
                                <option value="23:00:00|07:00:00">11:00 PM - 07:00 AM</option>
                                <option value="00:00:00|08:00:00">12:00 AM - 08:00 AM</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="col-md-3 form-group">
                        <label class="text-dark">System Role <span class="text-danger">*</span></label>
                        <select id="role" name="role" class="form-control shadow-sm <?php echo isset($errors['required']) ? 'is-invalid' : ''; ?>" required>
                            <option value="" disabled <?php echo empty($role_val) ? 'selected' : ''; ?>>Select a role...</option>
                            <option value="Employee" <?php echo ($role_val === 'Employee') ? 'selected' : ''; ?>>Employee</option>
                            <option value="HR Staff" <?php echo ($role_val === 'HR Staff') ? 'selected' : ''; ?>>HR Staff</option>
                            <option value="Admin" <?php echo ($role_val === 'Admin') ? 'selected' : ''; ?>>System Admin</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 form-group">
                        <label class="text-dark">Profile Image</label>
                        <input type="file" name="profile_image" class="form-control shadow-sm" accept="image/*" style="padding-bottom: 35px;">
                    </div>
                  </div>
                  
                </div>
                
                <div class="card-footer bg-white text-right border-top-0 py-3">
                  <a href="user_management.php" class="btn btn-outline-secondary shadow-sm mr-2 px-4" style="border-radius: 6px;">Cancel</a>
                  <button type="submit" name="register_user" id="submitBtn" class="btn btn-dark shadow-sm px-4" style="border-radius: 6px;">
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

<script src="https://unpkg.com/imask"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const form = document.getElementById('registerForm');
    const formFields = form.querySelectorAll('input:not([type="file"]), select, textarea');

    // --- AUTOSAVE FEATURE ---
    formFields.forEach(field => {
        if (!field.id) return;
        
        let savedValue = sessionStorage.getItem('wfp_reg_' + field.id);
        if (savedValue !== null && field.value === '') {
            field.value = savedValue;
        }

        field.addEventListener('input', () => sessionStorage.setItem('wfp_reg_' + field.id, field.value));
        field.addEventListener('change', () => sessionStorage.setItem('wfp_reg_' + field.id, field.value));
    });

    // --- MASKING & DATES ---
    var phoneInput = document.getElementById('mobile_number');
    var phoneMask = IMask(phoneInput, {
        mask: '+63 000 000 0000', 
        lazy: false,  
        placeholderChar: '_'
    });
    phoneMask.updateValue(); 

    flatpickr("#birth_date", {
        dateFormat: "M/d/Y",
        allowInput: true,
        maxDate: "today"
    });

    // --- INLINE VALIDATION ---
    const requiredInputs = form.querySelectorAll('input[required], select[required], textarea[required]');

    function validateField(field) {
        let isValid = true;
        field.classList.remove('is-valid', 'is-invalid');

        if (field.id === 'mobile_number') {
            const unmaskedValue = phoneMask.unmaskedValue;
            if (unmaskedValue.length !== 10) { isValid = false; }
        } 
        else if (!field.checkValidity()) {
            isValid = false;
        }

        if (isValid) {
            field.classList.add('is-valid');
        } else {
            field.classList.add('is-invalid');
        }
        return isValid;
    }

    requiredInputs.forEach(input => {
        input.addEventListener('input', () => {
            if(input.classList.contains('is-invalid')) validateField(input);
        });
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('change', () => validateField(input));
    });

    // --- SUBMISSION LOGIC ---
    form.addEventListener('submit', function (e) {
        let formIsValid = true;
        requiredInputs.forEach(input => {
            if (!validateField(input)) {
                formIsValid = false;
            }
        });

        if (!formIsValid) {
            e.preventDefault();
            e.stopPropagation();
            
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please review the fields in red before submitting.',
                confirmButtonColor: '#212529'
            });
        } else {
            phoneInput.value = '+63' + phoneMask.unmaskedValue;
            
            formFields.forEach(field => {
                if(field.id) sessionStorage.removeItem('wfp_reg_' + field.id);
            });
        }
    }, false);

});
</script>

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