<?php
session_start();
require '../database.php';

// HR Staff only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR Staff') {
    header("Location: ../index.php");
    exit();
}

$title = "Requests | WorkForcePro";
include '../includes/hr_header.php';

// Fetch all employee requests
$query = "
    SELECT er.*, u.first_name, u.last_name
    FROM employee_requests er
    JOIN user u ON er.user_id = u.user_id
    ORDER BY er.date_submitted DESC
";
$result = $mysql->query($query);

// Summary counts
$totalRequestsQuery = $mysql->query("SELECT COUNT(request_id) AS total FROM employee_requests");
$totalRequests = $totalRequestsQuery->fetch_assoc()['total'] ?? 0;

$pendingRequestsQuery = $mysql->query("SELECT COUNT(request_id) AS total FROM employee_requests WHERE status = 'Pending'");
$pendingRequests = $pendingRequestsQuery->fetch_assoc()['total'] ?? 0;

$reviewedRequestsQuery = $mysql->query("SELECT COUNT(request_id) AS total FROM employee_requests WHERE status = 'Reviewed'");
$reviewedRequests = $reviewedRequestsQuery->fetch_assoc()['total'] ?? 0;
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Requests</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <div class="row">
      <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-inbox"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Total Requests</span>
            <span class="info-box-number text-lg"><?php echo $totalRequests; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock text-white"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Pending</span>
            <span class="info-box-number text-lg"><?php echo $pendingRequests; ?></span>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box shadow-sm" style="border-radius: 8px;">
          <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text font-weight-bold">Reviewed</span>
            <span class="info-box-number text-lg"><?php echo $reviewedRequests; ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mt-3" style="border-radius: 8px; overflow: hidden;">
      <div class="card-header bg-dark text-white py-3 border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-envelope-open-text mr-2"></i> Employee Requests and Suggestions
        </h3>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped m-0 text-center align-middle">
            <thead class="bg-light">
              <tr>
                <th>Employee Name</th>
                <th>Request Type</th>
                <th>Subject</th>
                <th>Date Submitted</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td class="font-weight-bold">
                      <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['request_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                    <td><?php echo date('M d, Y g:i A', strtotime($row['date_submitted'])); ?></td>
                    <td>
                      <?php if ($row['status'] === 'Pending'): ?>
                        <span class="badge badge-warning px-3 py-2 text-dark">Pending</span>
                      <?php else: ?>
                        <span class="badge badge-success px-3 py-2">Reviewed</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-sm btn-outline-dark shadow-sm mr-1" onclick="viewRequest(
                        '<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES); ?>',
                        '<?php echo htmlspecialchars($row['request_type'], ENT_QUOTES); ?>',
                        '<?php echo htmlspecialchars($row['subject'], ENT_QUOTES); ?>',
                        '<?php echo htmlspecialchars($row['message'], ENT_QUOTES); ?>',
                        '<?php echo date('M d, Y g:i A', strtotime($row['date_submitted'])); ?>',
                        '<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>'
                      )">
                        <i class="fas fa-eye mr-1"></i> View
                      </button>

                      <?php if ($row['status'] === 'Pending'): ?>
                        <a href="mark_request_reviewed.php?id=<?php echo $row['request_id']; ?>" class="btn btn-sm btn-success shadow-sm">
                          <i class="fas fa-check mr-1"></i> Mark Reviewed
                        </a>
                      <?php else: ?>
                        <span class="text-muted text-sm">Done</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">No requests found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</section>

<?php include '../includes/footer.php'; ?>

<script>
function viewRequest(employeeName, requestType, subject, message, dateSubmitted, status) {
    Swal.fire({
        title: subject,
        html: `
            <div style="text-align: left;">
                <p><strong>Employee:</strong> ${employeeName}</p>
                <p><strong>Request Type:</strong> ${requestType}</p>
                <p><strong>Date Submitted:</strong> ${dateSubmitted}</p>
                <p><strong>Status:</strong> ${status}</p>
                <hr>
                <p><strong>Message:</strong></p>
                <p>${message}</p>
            </div>
        `,
        icon: 'info',
        confirmButtonColor: '#212529'
    });
}
</script>