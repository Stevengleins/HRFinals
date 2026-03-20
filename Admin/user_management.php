<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

include('../database.php'); 

// FIXED QUERY: Explicitly selecting columns instead of using e.* to prevent user_id collision
$query = "
    SELECT 
        u.user_id, 
        u.email as u_email, 
        u.role as u_role, 
        u.first_name as u_first, 
        u.last_name as u_last,
        e.position, e.gender, e.birth_date, e.mobile_number, e.address, e.join_date, e.profile_image,
        (SELECT COUNT(*) FROM leave_requests lr WHERE lr.user_id = u.user_id AND lr.status = 'Pending') AS pending_requests
    FROM user u 
    LEFT JOIN employee_details e ON u.user_id = e.user_id
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
              <th>Position</th>
              <th>Role</th>
              <th>Email</th>
              <th>Pending Requests</th>       
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
                // Smart Fallbacks
                $display_first = !empty($row['first_name']) ? $row['first_name'] : $row['u_first'];
                $display_last  = !empty($row['last_name']) ? $row['last_name'] : $row['u_last'];
                $display_email = !empty($row['email']) ? $row['email'] : $row['u_email'];
                $display_role  = !empty($row['role']) ? $row['role'] : $row['u_role'];
                $full_name = htmlspecialchars($display_first . ' ' . $display_last);
                
                // Get Image or Avatar
                $avatar = !empty($row['profile_image']) ? '../' . htmlspecialchars($row['profile_image']) : "https://ui-avatars.com/api/?name=" . urlencode($full_name) . "&background=343a40&color=ffffff";
            ?>
            <tr>
              <td class="font-weight-bold text-muted"><?php echo htmlspecialchars($row['user_id']); ?></td>
              <td class="text-left font-weight-bold">
                  <img src="<?php echo $avatar; ?>" class="img-circle mr-2" style="width: 30px; height: 30px; object-fit: cover;">
                  <?php echo $full_name; ?>
              </td>
              <td><span class="text-muted"><?php echo htmlspecialchars($row['position'] ?? 'Not Assigned'); ?></span></td>
              <td>
                  <?php if($display_role == 'HR Staff'): ?>
                      <span class="badge bg-success px-2 py-1"><i class="fas fa-user-tie mr-1"></i> HR Staff</span>
                  <?php else: ?>
                      <span class="badge bg-info px-2 py-1"><i class="fas fa-user mr-1"></i> Employee</span>
                  <?php endif; ?>
              </td>
              <td class="text-left"><?php echo htmlspecialchars($display_email); ?></td>
              
              <td>
                <?php if($row['pending_requests'] > 0): ?>
                    <span class="badge badge-warning" style="font-size: 0.9rem;"><?php echo $row['pending_requests']; ?> Pending</span>
                <?php else: ?>
                    <span class="text-muted">0</span>
                <?php endif; ?>
              </td>
              <td>
                <button type="button" class="btn btn-sm btn-info shadow-sm mr-1 view-btn" 
                    data-id="<?php echo htmlspecialchars($row['user_id']); ?>"
                    data-name="<?php echo $full_name; ?>"
                    data-email="<?php echo htmlspecialchars($display_email); ?>"
                    data-role="<?php echo htmlspecialchars($display_role); ?>"
                    data-position="<?php echo htmlspecialchars($row['position'] ?? 'N/A'); ?>"
                    data-gender="<?php echo htmlspecialchars($row['gender'] ?? 'N/A'); ?>"
                    data-mobile="<?php echo htmlspecialchars($row['mobile_number'] ?? 'N/A'); ?>"
                    data-address="<?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?>"
                    data-birth="<?php echo !empty($row['birth_date']) ? date('M d, Y', strtotime($row['birth_date'])) : 'N/A'; ?>"
                    data-join="<?php echo !empty($row['join_date']) ? date('M d, Y', strtotime($row['join_date'])) : 'N/A'; ?>"
                    data-avatar="<?php echo $avatar; ?>"
                    title="View Details">
                  <i class="fas fa-eye"></i>
                </button>

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

<div class="modal fade" id="viewProfileModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 10px; overflow: hidden;">
      <div class="modal-header bg-dark text-white border-0 py-3">
        <h5 class="modal-title font-weight-bold"><i class="fas fa-address-card mr-2"></i> Employee Profile</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0">
        
        <div class="text-center p-4 bg-light border-bottom">
            <img id="modalAvatar" src="" class="img-circle elevation-2 mb-3" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid white;">
            <h4 id="modalName" class="font-weight-bold text-dark m-0">Name</h4>
            <p id="modalPosition" class="text-muted m-0">Position</p>
            <span id="modalRole" class="badge badge-primary mt-2 px-3 py-1">Role</span>
        </div>

        <div class="p-4">
            <div class="row mb-2">
                <div class="col-5 text-muted font-weight-bold"><i class="fas fa-envelope mr-2"></i> Email:</div>
                <div class="col-7 text-dark" id="modalEmail">...</div>
            </div>
            <div class="row mb-2">
                <div class="col-5 text-muted font-weight-bold"><i class="fas fa-phone mr-2"></i> Mobile:</div>
                <div class="col-7 text-dark" id="modalMobile">...</div>
            </div>
            <div class="row mb-2">
                <div class="col-5 text-muted font-weight-bold"><i class="fas fa-venus-mars mr-2"></i> Gender:</div>
                <div class="col-7 text-dark" id="modalGender">...</div>
            </div>
            <div class="row mb-2">
                <div class="col-5 text-muted font-weight-bold"><i class="fas fa-birthday-cake mr-2"></i> Birth Date:</div>
                <div class="col-7 text-dark" id="modalBirth">...</div>
            </div>
            <div class="row mb-2">
                <div class="col-5 text-muted font-weight-bold"><i class="fas fa-calendar-check mr-2"></i> Join Date:</div>
                <div class="col-7 text-dark" id="modalJoin">...</div>
            </div>
            <div class="row">
                <div class="col-5 text-muted font-weight-bold"><i class="fas fa-map-marker-alt mr-2"></i> Address:</div>
                <div class="col-7 text-dark" id="modalAddress">...</div>
            </div>
        </div>

      </div>
      <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-secondary px-4 shadow-sm" data-dismiss="modal" style="border-radius: 6px;">Close</button>
      </div>
    </div>
  </div>
</div>

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
        table.column(3).search(this.value).draw();
    });

    // Populate and show the View Profile Modal
    $('.view-btn').on('click', function() {
        $('#modalAvatar').attr('src', $(this).data('avatar'));
        $('#modalName').text($(this).data('name'));
        $('#modalPosition').text($(this).data('position'));
        
        let role = $(this).data('role');
        $('#modalRole').text(role);
        $('#modalRole').removeClass().addClass('badge px-3 py-1 mt-2 ' + (role === 'HR Staff' ? 'badge-success' : 'badge-info'));

        $('#modalEmail').text($(this).data('email'));
        $('#modalMobile').text($(this).data('mobile'));
        $('#modalGender').text($(this).data('gender'));
        $('#modalBirth').text($(this).data('birth'));
        $('#modalJoin').text($(this).data('join'));
        $('#modalAddress').text($(this).data('address'));

        $('#viewProfileModal').modal('show');
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