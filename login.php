<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/db.php";
require_once "config/audit.php";   // Required for logActivity()

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username=? AND status='active' LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {

        if (password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['name']     = $user['full_name'];

            // Audit Log
            logActivity(
                $conn,
                "Authentication",
                "Login",
                $user['id'],
                $user['full_name'] . " logged into the system."
            );

            header("Location: dashboard.php");
            exit();

        } else {
            $error = "Invalid username or password.";
        }

    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Boxon Account Software</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<style>

body{
    background:#eef2f7;
}

.login-card{
    margin-top:100px;
    border:none;
    border-radius:15px;
    box-shadow:0 8px 25px rgba(0,0,0,.15);
}

.card-header{
    border-radius:15px 15px 0 0 !important;
}

</style>

</head>

<body>

<div class="container">

<div class="row justify-content-center">

<div class="col-md-5">

<div class="card login-card">

<div class="card-header bg-primary text-white text-center">

<h3>
<i class="fa-solid fa-building"></i>
Boxon Account Software
</h3>

</div>

<div class="card-body p-4">

<?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<form method="POST">

<div class="mb-3">

<label>Username</label>

<input
type="text"
name="username"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Password</label>

<input
type="password"
name="password"
class="form-control"
required>

</div>

<button type="submit" class="btn btn-primary w-100">

<i class="fa fa-sign-in-alt"></i>

Login

</button>

</form>

</div>

</div>

</div>

</div>

</div>

</body>

</html>