<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

include('../database.php'); 

// NEW: Now selecting e.shift_start and e.shift_end
$query = "
    SELECT 
        u.user_id, 
        u.email as u_email, 
        u.role as u_role, 
        u.first_name as u_first, 
        u.last_name as u_last,
        e.first_name as e_first,
        e.middle_name,
        e.last_name as e_last,
        e.suffix,
        e.position, e.gender, e.birth_date, e.mobile_number, e.address, e.join_date, e.profile_image,
        e.shift_start, e.shift_end
    FROM user u 
    LEFT JOIN employee_details e ON u.user_id = e.user_id
    WHERE u.role != 'Admin' AND u.status = 1
";
$result = $mysql->query($query); 

if (!$result) {
    die("Query Failed: " . $mysql->error);
}

$title = "Manage Users | WorkForcePro";
include('../includes/admin_header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<style>
    /* Clean Table Styling */
    .table-custom thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .table-custom td {
        vertical-align: middle !important;
        border-top: 1px solid #e9ecef;
    }
    .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; }
</style>

<div class="content-header pb-2">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">User Management</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="archived_users.php" class="btn btn-light border shadow-sm btn-sm px-3 mr-2 font-weight-bold" style="border-radius: 6px; color: #495057;">
          <i class="fas fa-archive mr-1 text-secondary"></i> Archived Users
        </a>
        <a href="register_user.php" class="btn btn-dark shadow-sm btn-sm px-3 font-weight-bold" style="border-radius: 6px;">
          <i class="fas fa-user-plus mr-1"></i> Register New User
        </a>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 8px; overflow: hidden;">
      
      <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
        <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.1rem;">
            <i class="fas fa-users-cog mr-2"></i> Active Directory
        </h3>
        <div class="card-tools m-0 d-flex align-items-center">
            
            <div class="btn-group shadow-sm mr-3">
                <a href="export_users_csv.php" class="btn btn-sm btn-light border-0" title="Export Directory to CSV"><i class="fas fa-file-csv text-success"></i></a>
                <a href="export_pdf.php?type=users" target="_blank" class="btn btn-sm btn-light border-0 border-left" title="Export Directory to PDF"><i class="fas fa-file-pdf text-danger"></i></a>
            </div>
            
            <div class="input-group input-group-sm shadow-sm" style="width: 200px;">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-filter text-muted"></i></span>
                </div>
                <select id="roleFilter" class="form-control border-0 text-dark font-weight-bold" style="cursor: pointer;">
                    <option value="">All Roles</option>
                    <option value="Employee">Employee Only</option>
                    <option value="HR Staff">HR Staff Only</option>
                </select>
            </div>
        </div>
      </div>
      
      <div class="card-body p-0">
        <div class="p-3">
            <table id="userTable" class="table table-hover table-custom w-100">
              <thead>
                <tr>
                  <th>Employee Name</th>
                  <th>Position</th>
                  <th>System Role</th>
                  <th>Email Address</th>    
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
                    // Smart Full Name Construction
                    $f_name = !empty($row['e_first']) ? $row['e_first'] : $row['u_first'];
                    $m_name = !empty($row['middle_name']) ? $row['middle_name'] : '';
                    $l_name = !empty($row['e_last']) ? $row['e_last'] : $row['u_last'];
                    $s_name = !empty($row['suffix']) ? $row['suffix'] : '';
                    
                    $name_parts = [];
                    if (!empty($f_name)) $name_parts[] = $f_name;
                    if (!empty($m_name)) $name_parts[] = $m_name;
                    if (!empty($l_name)) $name_parts[] = $l_name;
                    if (!empty($s_name)) $name_parts[] = $s_name;
                    
                    $full_name = htmlspecialchars(implode(' ', $name_parts));
                    
                    // Fallbacks
                    $display_email = !empty($row['email']) ? $row['email'] : $row['u_email'];
                    $display_role  = !empty($row['role']) ? $row['role'] : $row['u_role'];
                    
                    // Avatar logic WITH PHYSICAL FILE CHECK
                    $rawImagePath = trim((string)$row['profile_image']);
                    if (!empty($rawImagePath) && file_exists(__DIR__ . '/../' . $rawImagePath)) {
                        $avatar = '../' . htmlspecialchars($rawImagePath);
                    } else {
                        $avatar = "https://ui-avatars.com/api/?name=" . urlencode($full_name) . "&background=343a40&color=ffffff";
                    }
                    
                    // Formatting the shift for the Data attributes
                    $shift_start = !empty($row['shift_start']) ? $row['shift_start'] : '08:00:00';
                    $shift_end = !empty($row['shift_end']) ? $row['shift_end'] : '17:00:00';
                    $formatted_shift = date('h:i A', strtotime($shift_start)) . ' - ' . date('h:i A', strtotime($shift_end));
                ?>
                <tr>
                  <td class="font-weight-bold text-dark">
                      <img src="<?php echo $avatar; ?>" class="img-circle mr-2 shadow-sm" style="width: 35px; height: 35px; object-fit: cover;">
                      <?php echo $full_name; ?>
                  </td>
                  <td class="text-muted"><?php echo htmlspecialchars($row['position'] ?? 'Not Assigned'); ?></td>
                  <td>
                      <?php if($display_role == 'HR Staff'): ?>
                          <span class="badge bg-success px-2 py-1" style="font-size: 0.8rem;"><i class="fas fa-user-tie mr-1"></i> HR Staff</span>
                      <?php else: ?>
                          <span class="badge bg-info px-2 py-1" style="font-size: 0.8rem;"><i class="fas fa-user mr-1"></i> Employee</span>
                      <?php endif; ?>
                  </td>
                  <td class="text-muted"><?php echo htmlspecialchars($display_email); ?></td>
                  
                  <td class="text-center">
                    <button type="button" class="btn btn-light border text-info btn-action mr-1 view-btn shadow-sm" 
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
                        data-shift="<?php echo htmlspecialchars($formatted_shift); ?>"
                        data-avatar="<?php echo $avatar; ?>"
                        title="View Profile">
                      <i class="fas fa-id-card"></i>
                    </button>
    
                    <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-light border text-primary btn-action mr-1 shadow-sm" title="Edit User">
                      <i class="fas fa-edit"></i>
                    </a>
    
                    <button type="button" class="btn btn-light border text-danger btn-action shadow-sm" onclick="confirmArchive(<?php echo $row['user_id']; ?>)" title="Archive User">
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
  </div>
</section>

<div class="modal fade" id="viewProfileModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 8px;">
      
      <div class="modal-header bg-dark text-white border-0 py-3">
        <h5 class="modal-title font-weight-bold"><i class="fas fa-address-card mr-2"></i> Employee Details</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
      <div class="modal-body p-0 bg-white">
        <div class="text-center pt-4 pb-2">
            <img id="modalAvatar" src="" class="img-circle border shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
        </div>
        <div class="text-center px-4 mb-4">
            <h4 id="modalName" class="font-weight-bold text-dark m-0">Name</h4>
            <p id="modalPosition" class="text-muted m-0 mt-1" style="font-size: 0.95rem;">Position</p>
            <span id="modalRole" class="badge badge-primary mt-2 px-3 py-1 shadow-sm">Role</span>
        </div>

        <div class="px-4 pb-4">
            <div class="p-3" style="background-color: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                <div class="row mb-2">
                    <div class="col-5 text-muted font-weight-bold text-sm"><i class="fas fa-envelope mr-2"></i> Email:</div>
                    <div class="col-7 text-dark text-sm font-weight-bold" id="modalEmail">...</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted font-weight-bold text-sm"><i class="fas fa-phone mr-2"></i> Mobile:</div>
                    <div class="col-7 text-dark text-sm font-weight-bold" id="modalMobile">...</div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-5 text-muted font-weight-bold text-sm"><i class="fas fa-clock mr-2"></i> Work Shift:</div>
                    <div class="col-7 text-primary text-sm font-weight-bold" id="modalShift">...</div>
                </div>

                <div class="row mb-2">
                    <div class="col-5 text-muted font-weight-bold text-sm"><i class="fas fa-venus-mars mr-2"></i> Gender:</div>
                    <div class="col-7 text-dark text-sm font-weight-bold" id="modalGender">...</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted font-weight-bold text-sm"><i class="fas fa-birthday-cake mr-2"></i> Birth Date:</div>
                    <div class="col-7 text-dark text-sm font-weight-bold" id="modalBirth">...</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted font-weight-bold text-sm"><i class="fas fa-calendar-check mr-2"></i> Join Date:</div>
                    <div class="col-7 text-dark text-sm font-weight-bold" id="modalJoin">...</div>
                </div>
                <div class="row">
                    <div class="col-5 text-muted font-weight-bold text-sm"><i class="fas fa-map-marker-alt mr-2"></i> Address:</div>
                    <div class="col-7 text-dark text-sm font-weight-bold" id="modalAddress">...</div>
                </div>
            </div>
        </div>
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
      "pageLength": 10,
      "order": [[ 0, "asc" ]], 
      "language": {
          "search": "_INPUT_",
          "searchPlaceholder": "Search directory..."
      }
    });

    $('#roleFilter').on('change', function() {
        table.column(2).search(this.value).draw(); 
    });

    $('.view-btn').on('click', function() {
        $('#modalAvatar').attr('src', $(this).data('avatar'));
        $('#modalName').text($(this).data('name'));
        $('#modalPosition').text($(this).data('position'));
        
        let role = $(this).data('role');
        $('#modalRole').text(role);
        $('#modalRole').removeClass().addClass('badge shadow-sm px-3 py-1 mt-2 ' + (role === 'HR Staff' ? 'badge-success' : 'badge-info'));

        $('#modalEmail').text($(this).data('email'));
        $('#modalMobile').text($(this).data('mobile'));
        $('#modalGender').text($(this).data('gender'));
        $('#modalBirth').text($(this).data('birth'));
        $('#modalJoin').text($(this).data('join'));
        $('#modalAddress').text($(this).data('address'));
        $('#modalShift').text($(this).data('shift'));

        $('#viewProfileModal').modal('show');
    });
  });

  function confirmArchive(id) {
    Swal.fire({
        title: 'Archive this account?',
        text: "This will immediately restrict their access and mark them as Inactive. Their data will be safely preserved.",
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