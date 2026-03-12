<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($title) ? $title : "Admin Dashboard"; ?></title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </li>
    </ul>
  </nav>

  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a class="brand-link">
      <span class="brand-text font-weight-light ml-4"><strong>WORK</strong>FORCE</span>
    </a>

    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
          <li class="nav-item">
            <a href="" class="nav-link">
              <i class="bi bi-sort-up"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="   " class="nav-link">
              <i class="bi bi-people-fill"></i>
              <p>Available Tasks</p>
            </a>
          </li>
             <li class="nav-item">
            <a href="" class="nav-link">
              <i class="bi bi-box-arrow-in-left"></i>
              <p>Leave Management</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="" class="nav-link">
              <i class="bi bi-clipboard-check-fill"></i>
              <p>Attendace</p>
            </a>
          </li>
             <li class="nav-item">
            <a href="" class="nav-link">
              <i class="bi bi-wallet"></i>
              <p>Payroll</p>
            </a>
          </li>
           <li class="nav-item">
            <a href="" class="nav-link">
              <i class="bi bi-envelope-arrow-down"></i>
              <p>Requests</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">