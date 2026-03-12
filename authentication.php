<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

require 'database.php';

if (isset($_POST['login'])) {
    $login_input = trim($_POST['email']); 
    $password = $_POST['password'];

    $stmt = $mysql->prepare("SELECT user_id, first_name, password, role FROM user WHERE BINARY email = ? OR BINARY username = ?");
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['role'] = $user['role'];

            // Role-based Redirection
            switch ($user['role']) {
                case 'Admin':
                    header("Location: Admin/dashboard.php");
                    break;
                case 'HR Staff':
                    header("Location: HR_Staff/hr_dashboard.php");
                    break;
                case 'Employee':
                    header("Location: Employee/employee_dashboard.php");
                    break;
                default:
                    $_SESSION['status_icon'] = 'error';
                    $_SESSION['status_title'] = 'Role Error';
                    $_SESSION['status_text'] = 'User role not recognized.';
                    header("Location: index.php");
                    break;
            }
            exit();
             
        } else {
            $_SESSION['status_icon'] = 'error';
            $_SESSION['status_title'] = 'Login Failed';
            $_SESSION['status_text'] = 'Invalid password.';
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['status_icon'] = 'warning';
        $_SESSION['status_title'] = 'Not Found';
        $_SESSION['status_text'] = 'No account found with that Email or Username.';
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>