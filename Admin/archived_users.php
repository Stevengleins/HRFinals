<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

include('../database.php'); 

// Fetch ARCHIVED users (status = 0), matching the robust query of user_management
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
        e.position, e.profile_image
    FROM user u 
    LEFT JOIN employee_details e ON u.user_id = e.user_id
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

<style>
    /* Clean Table Styling matching User Management */
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
    .btn-action { 
        width: 32px; 
        height: 32px; 
        padding: 0; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 4px; 
    }
    /* Visual cue that these users are inactive */
    .archived-row { opacity: 0.7; transition: opacity 0.2s; }
    .archived-row:hover { opacity: 1; }
    .archived-img { filter: grayscale(100%); }
</style>

<div class="content-header pb-2">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Archived Users</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="user_management.php" class="btn btn-light border shadow-sm btn-sm px-3 font-weight-bold" style="border-radius: 6px; color: #495057;">
          <i class="fas fa-arrow-left mr-1 text-secondary"></i> Back to Active Users
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
            <i class="fas fa-archive mr-2 text-warning"></i> Inactive Directory
        </h3>
      </div>
      
      <div class="card-body p-0">
        <div class="p-3">
            <table id="archivedTable" class="table table-hover table-custom w-100 bg-white">
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
                ?>
                <tr class="archived-row">
                  <td class="font-weight-bold text-dark">
                      <img src="<?php echo $avatar; ?>" class="img-circle mr-2 shadow-sm archived-img" style="width: 35px; height: 35px; object-fit: cover;">
                      <span class="text-decoration-line-through text-muted"><?php echo $full_name; ?></span>
                  </td>
                  <td class="text-muted"><?php echo htmlspecialchars($row['position'] ?? 'Not Assigned'); ?></td>
                  <td>
                      <span class="badge bg-secondary px-2 py-1" style="font-size: 0.8rem;">
                          <i class="fas fa-lock mr-1"></i> <?php echo htmlspecialchars($display_role); ?>
                      </span>
                  </td>
                  <td class="text-muted"><?php echo htmlspecialchars($display_email); ?></td>
                  
                  <td class="text-center">
                    <button type="button" class="btn btn-light border text-success btn-action mr-1 shadow-sm" onclick="confirmRestore(<?php echo $row['user_id']; ?>)" title="Restore User">
                      <i class="fas fa-trash-restore"></i>
                    </button>
                    <button type="button" class="btn btn-light border text-danger btn-action shadow-sm" onclick="confirmDelete(<?php echo $row['user_id']; ?>)" title="Permanently Delete User">
                      <i class="fas fa-trash-alt"></i>
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
      "pageLength": 10,
      "order": [[ 0, "asc" ]], 
      "language": {
          "search": "_INPUT_",
          "searchPlaceholder": "Search archived users..."
      }
    });
  });

  // SweetAlert Restore Confirmation
  function confirmRestore(id) {
    Swal.fire({
        title: 'Restore this account?',
        text: "This user will regain access to the system and be moved back to the Active Directory.",
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
        text: "This action CANNOT be undone. All associated records, leaves, and history for this user will be lost forever.",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545', 
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete permanently!'
    }).then((result) => {
        if (result.isConfirmed) {
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