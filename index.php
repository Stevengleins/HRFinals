<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
    *{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{
background:#f4f5f7;
height:100vh;
display:flex;
justify-content:center;
align-items:center;
position:relative;
overflow:hidden;
}

/* Background Shapes */

.shape1{
position:absolute;
width:300px;
height:300px;
background: #726969;;
border-radius:50%;
top:-100px;
right:-100px;
}

.shape2{
position:absolute;
width:350px;
height:350px;
background: #726969;;
border-radius:50%;
bottom:-150px;
left:-150px;
}


.container{
width:1100px;
height:600px;
background:white;
display:flex;
box-shadow:0 10px 40px rgba(0,0,0,0.1);
}

.left{
flex:1;
padding:60px;
position:relative;
}

.logo{
display:flex;
align-items:center;
gap:10px;
margin-bottom:50px;
}

.logo-box{
width:18px;
height:18px;
background:#e58f8a;
}

.create{
position:absolute;
top:40px;
right:40px;
border:1px solid #947777;
padding:8px 20px;
font-size:13px;
background:white;
cursor:pointer;
}

.login-title{
font-size:20px;
margin-bottom:25px;
}

.input{
width:350px;
padding:12px;
margin-bottom:15px;
border:1px solid #554242;
background:#f7f7f7;
}

.login-btn{
background: #363434;;
color:white;
border:none;
padding:10px 30px;
cursor:pointer;
}

.forgot{
font-size:12px;
margin-top:10px;
color:#777;
}

.footer{
position:absolute;
bottom:25px;
left:60px;
font-size:11px;
color:#777;
display:flex;
gap:25px;
}


.right{
width:420px;
background: #2a2929;;
color:white;
display:flex;
flex-direction:column;
justify-content:center;
align-items:center;
text-align:center;
padding:40px;
}

.grid{
display:grid;
grid-template-columns:repeat(3,40px);
grid-gap:15px;
margin-bottom:30px;
}

.grid div{
width:40px;
height:40px;
background:#e58f8a;
border-radius:8px;
}

.desc{
font-size:13px;
line-height:1.6;
max-width:250px;
margin-bottom:20px;
}

.author{
font-size:12px;
opacity:0.8;
}
</style>
  <title>Admin System | Log in</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="shape1"></div>
<div class="shape2"></div>

<div class="container">


<div class="left">
<div class="logo">
<div class="logo-box"></div>
<b><b>WORK</b>FORCE</a></b>
</div>

<div class="login-title">Sign in to start your session</div>
<form action="authentication.php" method="post">
  <input name="email" class="input" type="text" placeholder="Username / Email" required>
  <input name="password" class="input" type="password" placeholder="Password" required> <br>
  <button type="submit" name="login" class="login-btn">Login</button>
</form>

</div>


<div class="right">

<div class="grid">
<div></div><div></div><div></div>
<div></div><div></div><div></div>
<div></div><div></div><div></div>
</div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <?php
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
</body>
</html>