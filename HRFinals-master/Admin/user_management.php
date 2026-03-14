<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

include('../database.php'); 

// Fetch ACTIVE users (Added back u.status = 1)
$query = "
    SELECT 
        u.user_id, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.role, 
        (SELECT COUNT(*) FROM leave_requests lr WHERE lr.user_id = u.user_id AND lr.status = 'Pending') AS pending_requests
    FROM user u 
    WHERE u.role != 'Admin' AND u.status = 1
";
$result = $mysql->query($query); 

if (!$result) {
    die("Query Failed: " . $mysql->error);
}

include('../includes/admin_header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold">User Management</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="archived_users.php" class="btn btn-outline-secondary btn-sm px-3 shadow-none mr-2" style="border-radius: 4px;">
          <i class="fas fa-archive mr-1"></i> Archived Users
        </a>
        <a href="register_user.php" class="btn btn-outline-dark btn-sm px-3 shadow-none" style="border-radius: 4px;">
          <i class="fas fa-plus mr-1"></i> Register User
        </a>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
      
      <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
            <i class="fas fa-users-cog mr-2"></i> Employee & Staff Directory
        </h3>
        <div class="card-tools m-0">
            <select id="roleFilter" class="form-control form-control-sm shadow-none font-weight-bold" style="border-radius: 4px; width: 180px; cursor: pointer;">
                <option value="">View All Roles</option>
                <option value="Employee">Employee Only</option>
                <option value="HR Staff">HR Staff Only</option>
            </select>
        </div>
      </div>
      
      <div class="card-body">
        <table id="userTable" class="table table-bordered table-hover table-striped text-center align-middle w-100">
          <thead class="bg-light">
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Role</th>
              <th>Email</th>
              <th>Pending Requests</th>       
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo $row['user_id']; ?></td>
              <td class="text-left font-weight-bold"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
              <td>
                  <?php if($row['role'] == 'HR Staff'): ?>
                      <span class="badge bg-success px-2 py-1"><i class="fas fa-user-tie mr-1"></i> HR Staff</span>
                  <?php else: ?>
                      <span class="badge bg-info px-2 py-1"><i class="fas fa-user mr-1"></i> Employee</span>
                  <?php endif; ?>
              </td>
              <td class="text-left"><?php echo htmlspecialchars($row['email']); ?></td>
              
              <td>
                <?php if($row['pending_requests'] > 0): ?>
                    <span class="badge badge-warning" style="font-size: 0.9rem;"><?php echo $row['pending_requests']; ?> Pending</span>
                <?php else: ?>
                    <span class="text-muted">0</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-primary shadow-sm mr-1" title="Edit User">
                  <i class="fas fa-edit"></i>
                </a>
                <button type="button" class="btn btn-sm btn-danger shadow-sm" onclick="confirmArchive(<?php echo $row['user_id']; ?>)" title="Archive User">
                  <i class="fas fa-archive"></i>
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
    var table = $("#userTable").DataTable({
      "responsive": true, 
      "lengthChange": true, 
      "autoWidth": false,
      "searching": true,    
      "ordering": true,     
      "info": true,         
      "paging": true,
      "order": [[ 1, "asc" ]] 
    });

    $('#roleFilter').on('change', function() {
        table.column(2).search(this.value).draw();
    });
  });

  function confirmArchive(id) {
    Swal.fire({
        title: 'Archive this account?',
        text: "This will immediately restrict their access and mark them as Inactive, but their records will be saved.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#212529', 
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, archive them!'
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
            confirmButtonColor: '#212529'
        });
    </script>
    ";

    unset($_SESSION['status_icon']);
    unset($_SESSION['status_title']);
    unset($_SESSION['status_text']);
}
?>