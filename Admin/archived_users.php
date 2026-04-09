<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

include('../database.php'); 

// Fetch ARCHIVED users (status = 0)
$query = "
    SELECT 
        u.user_id, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.role 
    FROM user u 
    WHERE u.role != 'Admin' AND u.status = 0
";
$result = $mysql->query($query); 

if (!$result) {
    die("Query Failed: " . $mysql->error);
}

$title = "Archived Users | WorkForcePro";
include('../includes/admin_header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">Archived Users</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="user_management.php" class="btn btn-outline-dark btn-sm px-3 shadow-none" style="border-radius: 4px;">
          <i class="fas fa-arrow-left mr-1"></i> Back to Active Users
        </a>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
      
      <div class="card-header bg-secondary text-white py-3">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
            <i class="fas fa-archive mr-2"></i> Inactive Employee Directory
        </h3>
      </div>
      
      <div class="card-body bg-light">
        <table id="archivedTable" class="table table-bordered table-hover table-striped text-center align-middle w-100 bg-white">
          <thead class="bg-dark text-white">
            <tr>
              <th>Full Name</th>
              <th>Role</th>
              <th>Email</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
            <tr>
              <td class="text-left font-weight-bold text-secondary"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
              <td>
                  <span class="badge bg-secondary px-2 py-1"><?php echo htmlspecialchars($row['role']); ?></span>
              </td>
              <td class="text-left text-muted"><?php echo htmlspecialchars($row['email']); ?></td>
              <td>
                <button type="button" class="btn btn-sm btn-success shadow-sm mr-1" onclick="confirmRestore(<?php echo $row['user_id']; ?>)" title="Restore User">
                  <i class="fas fa-trash-restore mr-1"></i> Restore
                </button>
                <button type="button" class="btn btn-sm btn-danger shadow-sm" onclick="confirmDelete(<?php echo $row['user_id']; ?>)" title="Permanently Delete User">
                  <i class="fas fa-trash-alt mr-1"></i> Delete
                </button>
              </td>
            </tr>
            <?php endwhile; endif; ?>
          </tbody>
        </table>

      </div>
    </div>
  </div>
</section>

<?php include('../includes/footer.php');?>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

<script>
  $(document).ready(function () {
    $("#archivedTable").DataTable({
      "responsive": true, 
      "lengthChange": true, 
      "autoWidth": false,
      "searching": true,    
      "ordering": true,     
      "info": true,         
      "paging": true,
      "order": [[ 0, "asc" ]] // Updated to 0 to sort alphabetically by name by default
    });
  });

  // SweetAlert Restore Confirmation
  function confirmRestore(id) {
    Swal.fire({
        title: 'Restore this account?',
        text: "This user will regain access to the system and be moved back to the Active Users list.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745', 
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, restore them!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'restore_user.php?id=' + id;
        }
    })
  }

  // SweetAlert Delete Confirmation
  function confirmDelete(id) {
    Swal.fire({
        title: 'Permanently delete this account?',
        text: "This action cannot be undone. All associated data will be lost.",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545', 
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete permanently!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Adjust this URL to match your actual hard-delete script
            window.location.href = 'delete_user_permanent.php?id=' + id;
        }
    })
  }
</script>

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