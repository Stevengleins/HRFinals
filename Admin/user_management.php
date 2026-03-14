<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

include('../database.php'); 

$query = "SELECT user_id, first_name, last_name, email, role, status, is_verified FROM user WHERE role != 'Admin'";
$result = $mysql->query($query); 

if (!$result) {
    die("Query Failed: " . $mysql->error);
}

include('../includes/header.php');
?>

<link rel="stylesheet" href="/Codes/HRFINALS/node_modules/admin-lte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="/Codes/HRFINALS/node_modules/admin-lte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="/Codes/HRFINALS/node_modules/admin-lte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">User Management</h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Employee Data Management</h3>
      </div>
      <div class="card-body">
        <table id="userTable" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Verification</th> 
              <th>Status</th>       
              <th>Role</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo $row['user_id']; ?></td>
              <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
              <td><?php echo $row['email']; ?></td>
              <td>
                <?php if($row['is_verified'] == 1): ?>
                    <span class="badge badge-success">Verified</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Unverified</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($row['status'] == 1): ?>
                    <span class="badge badge-success">Active</span>
                <?php else: ?>
                    <span class="badge badge-danger">Inactive</span>
                <?php endif; ?>
              </td>
              <td><?php echo $row['role']; ?></td>
              <td>
                <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-info">
                  <i class="fas fa-edit"></i>
                </a>
                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['user_id']; ?>)">
                  <i class="fas fa-trash"></i>
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

<script src="/Codes/HRFINALS/node_modules/admin-lte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="/Codes/HRFINALS/node_modules/admin-lte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="/Codes/HRFINALS/node_modules/admin-lte/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="/Codes/HRFINALS/node_modules/admin-lte/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="/Codes/HRFINALS/node_modules/admin-lte/plugins/jszip/jszip.min.js"></script>
<script src="/Codes/HRFINALS/node_modules/admin-lte/plugins/pdfmake/pdfmake.min.js"></script>
<script src="/Codes/HRFINALS/node_modules/admin-lte/plugins/pdfmake/vfs_fonts.js"></script>
<script src="/Codes/HRFINALS/node_modules/admin-lte/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="/Codes/HRFINALS/node_modules/admin-lte/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(function () {
    $("#userTable").DataTable({
      "responsive": true, 
      "lengthChange": true, // This enables the "Show [10] entries" dropdown
      "autoWidth": false,
      "searching": true,    // This enables the Search box
      "ordering": true,     // This enables column sorting
      "info": true,         // This shows "Showing 1 to 10 of X entries"
      "paging": true,       // This enables pagination
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#userTable_wrapper .col-md-6:eq(0)');
  });

  function confirmDelete(id) {
    Swal.fire({
        title: 'Delete user account?',
        text: "This process cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete_user.php?id=' + id;
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
            confirmButtonColor: '#3085d6'
        });
    </script>
    ";

    // Clear the session variables
    unset($_SESSION['status_icon']);
    unset($_SESSION['status_title']);
    unset($_SESSION['status_text']);
}
?>