<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin System | Log in</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="hold-transition login-page m-0" style="background: url('background.png') no-repeat center center / cover; overflow: hidden; font-family: 'Source Sans Pro', sans-serif;">

  <div class="position-absolute w-100 h-100" style="background: rgba(0, 0, 0, 0.4); z-index: 0; top: 0; left: 0;"></div>

  <div class="d-flex align-items-center justify-content-center min-vh-100 w-100 position-relative" style="z-index: 1;">
      
      <div class="container bg-white shadow-lg p-0 rounded overflow-hidden" style="max-width: 1000px; height: 550px;">
        <div class="row m-0 h-100">
          
          <div class="col-md-7 d-flex flex-column justify-content-center p-5">
            
            <div class="d-flex align-items-center mb-5">
              <div class="mr-2" style="width: 18px; height: 18px; background: #e58f8a;"></div>
              <h4 class="m-0 font-weight-bold">WORK<span class="font-weight-normal">FORCEPRO</span></h4>
            </div>

            <h5 class="mb-4 text-dark font-weight-normal">Sign in to start your session</h5>
            
            <form action="authentication.php" method="post" style="max-width: 350px;">
              <div class="form-group mb-3">
                <input name="email" class="form-control bg-light border-secondary rounded-0 py-4" type="email" placeholder="Email Address" required>
              </div>
              
              <div class="input-group mb-4">
                <input name="password" id="login_password" class="form-control bg-light border-secondary border-right-0 rounded-0 py-4" type="password" placeholder="Password" required>
                <div class="input-group-append">
                    <span class="input-group-text bg-light border-secondary border-left-0 rounded-0" onclick="togglePassword('login_password', this)" style="cursor: pointer;">
                        <i class="fas fa-eye text-muted"></i>
                    </span>
                </div>
              </div>

              <button type="submit" name="login" class="btn text-white rounded-0 px-5 py-2" style="background-color: #363434;">Login</button>
            </form>

          </div>

          <div class="col-md-5 p-0 h-100 d-none d-md-block bg-dark">
            <div id="loginCarousel" class="carousel slide h-100 w-100" data-ride="carousel" data-interval="3000">
              
              <ol class="carousel-indicators">
                <li data-target="#loginCarousel" data-slide-to="0" class="active"></li>
                <li data-target="#loginCarousel" data-slide-to="1"></li>
                <li data-target="#loginCarousel" data-slide-to="2"></li>
              </ol>
              
              <div class="carousel-inner h-100 w-100">
                
                <div class="carousel-item active h-100 w-100">
                  <img src="https://picsum.photos/500/600?random=1" class="d-block h-100 w-100" style="object-fit: cover;" alt="Slide 1">
                  <div class="carousel-caption d-none d-md-block" style="background: rgba(0,0,0,0.5); border-radius: 8px;">
                    <h5 class="font-weight-bold">Welcome to WorkForce</h5>
                    <p>Manage your team effectively.</p>
                  </div>
                </div>
                
                <div class="carousel-item h-100 w-100">
                  <img src="https://picsum.photos/500/600?random=2" class="d-block h-100 w-100" style="object-fit: cover;" alt="Slide 2">
                  <div class="carousel-caption d-none d-md-block" style="background: rgba(0,0,0,0.5); border-radius: 8px;">
                    <h5 class="font-weight-bold">Streamlined HR</h5>
                    <p>Everything you need in one place.</p>
                  </div>
                </div>
                
                <div class="carousel-item h-100 w-100">
                  <img src="https://picsum.photos/500/600?random=3" class="d-block h-100 w-100" style="object-fit: cover;" alt="Slide 3">
                  <div class="carousel-caption d-none d-md-block" style="background: rgba(0,0,0,0.5); border-radius: 8px;">
                    <h5 class="font-weight-bold">Secure Platform</h5>
                    <p>Your data is safe with us.</p>
                  </div>
                </div>
                
              </div>
            </div>
          </div>

        </div>
      </div>
      
  </div> <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
  function togglePassword(inputId, element) {
      const input = document.getElementById(inputId);
      const icon = element.querySelector('i');
      
      if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
      } else {
          input.type = 'password';
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
      }
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
            confirmButtonColor: '#363434'
        });
    </script>
    ";

    unset($_SESSION['status_icon']);
    unset($_SESSION['status_title']);
    unset($_SESSION['status_text']);
}
?>
</body>
</html>