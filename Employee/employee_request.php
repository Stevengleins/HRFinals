<?php
session_start();
require '../database.php';

// Employee only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = false;
$error_message = "";

$title = "Request Box | WorkForcePro";
include '../includes/employee_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_type = trim($_POST['request_type']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($request_type) || empty($subject) || empty($message)) {
        $error_message = "All fields are required.";
    } else {
        $stmt = $mysql->prepare("INSERT INTO employee_requests (user_id, request_type, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $request_type, $subject, $message);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $error_message = "Failed to submit request.";
        }
    }
}
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Request / Suggestion Box</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-envelope-open-text mr-2"></i> Submit a Request
        </h3>
      </div>

      <div class="card-body">
        <form method="POST">
          <div class="form-group">
            <label class="font-weight-bold">Request Type</label>
            <select name="request_type" class="form-control" required>
              <option value="" disabled selected>Select request type</option>
              <option value="Concern">Concern</option>
              <option value="Suggestion">Suggestion</option>
              <option value="Schedule Change">Schedule Change</option>
              <option value="Attendance Issue">Attendance Issue</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Subject</label>
            <input type="text" name="subject" class="form-control" placeholder="Enter subject" required>
          </div>

          <div class="form-group">
            <label class="font-weight-bold">Message</label>
            <textarea name="message" rows="5" class="form-control" placeholder="Write your request here..." required></textarea>
          </div>

          <button type="submit" class="btn btn-dark px-4">
            <i class="fas fa-paper-plane mr-1"></i> Submit Request
          </button>
        </form>
      </div>
    </div>

  </div>
</section>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if (!empty($error_message)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Submission Failed',
            text: '<?php echo addslashes($error_message); ?>',
            confirmButtonColor: '#212529'
        });
    <?php endif; ?>

    <?php if ($success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Submitted!',
            text: 'Your request has been submitted successfully.',
            confirmButtonColor: '#212529'
        }).then(() => {
            window.location.href = 'employee_request.php';
        });
    <?php endif; ?>
});
</script>