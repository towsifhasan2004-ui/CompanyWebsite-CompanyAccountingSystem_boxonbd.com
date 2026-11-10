<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';
require_once 'config/audit.php';

checkRole(['admin']);

$message = "";
$error = "";

/* ==========================================
   LOAD COMPANY INFORMATION
========================================== */

$result = mysqli_query($conn,"SELECT * FROM company WHERE id=1");

if(mysqli_num_rows($result)==0)
{
    mysqli_query($conn,"
        INSERT INTO company(company_name,currency)
        VALUES('My Company','BDT')
    ");

    $result=mysqli_query($conn,"SELECT * FROM company WHERE id=1");
}

$company=mysqli_fetch_assoc($result);

/* ==========================================
   UPDATE COMPANY INFORMATION
========================================== */

if(isset($_POST['update_company']))
{

    $company_name   = trim($_POST['company_name']);
    $address        = trim($_POST['address']);
    $phone          = trim($_POST['phone']);
    $email          = trim($_POST['email']);
    $website        = trim($_POST['website']);
    $tax_number     = trim($_POST['tax_number']);
    $currency       = trim($_POST['currency']);
    $financial_year = trim($_POST['financial_year']);

    $logo = $company['logo'];

    /* ======================================
       COMPANY LOGO UPLOAD
    ====================================== */

    if(isset($_FILES['logo']) && $_FILES['logo']['error']==0)
    {

        $allowed = ['jpg','jpeg','png','gif','webp'];

        $extension = strtolower(
            pathinfo(
                $_FILES['logo']['name'],
                PATHINFO_EXTENSION
            )
        );

        if(!in_array($extension,$allowed))
        {

            $error = "Only JPG, PNG, GIF and WEBP files are allowed.";

        }
        else
        {

            if(!is_dir("assets/uploads"))
            {
                mkdir("assets/uploads",0777,true);
            }

            $newLogo = "company_".time().".".$extension;

            $destination = "assets/uploads/".$newLogo;

            if(move_uploaded_file(
                $_FILES['logo']['tmp_name'],
                $destination
            ))
            {

                if(
                    !empty($logo)
                    &&
                    file_exists("assets/uploads/".$logo)
                )
                {
                    unlink("assets/uploads/".$logo);
                }

                $logo = $newLogo;

            }
            else
            {
                $error = "Logo upload failed.";
            }

        }

    }

    /* ======================================
       UPDATE DATABASE
    ====================================== */

    if($error=="")
    {

        $stmt=mysqli_prepare(

            $conn,

            "UPDATE company SET

            company_name=?,
            address=?,
            phone=?,
            email=?,
            website=?,
            tax_number=?,
            logo=?,
            financial_year=?,
            currency=?

            WHERE id=1"

        );

        mysqli_stmt_bind_param(

            $stmt,

            "sssssssss",

            $company_name,
            $address,
            $phone,
            $email,
            $website,
            $tax_number,
            $logo,
            $financial_year,
            $currency

        );

        if(mysqli_stmt_execute($stmt))
        {

            logActivity(

                $conn,

                "Settings",

                "Update",

                1,

                "Updated Company Settings"

            );

            $message = "Company information updated successfully.";

            $result=mysqli_query($conn,"SELECT * FROM company WHERE id=1");

            $company=mysqli_fetch_assoc($result);

        }
        else
        {
            $error="Unable to update company information.";
        }

    }

}

/* ==========================================
   PAGE LAYOUT
========================================== */

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-dark text-white">

<h4 class="mb-0">

<i class="fa-solid fa-gears"></i>

System Settings

</h4>

</div>

<div class="card-body">

<?php if($message!=""){ ?>

<div class="alert alert-success">

<?= $message ?>

</div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<ul class="nav nav-tabs mb-4">

<li class="nav-item">

<a class="nav-link active"
data-bs-toggle="tab"
href="#company">

<i class="fa-solid fa-building"></i>

Company

</a>

</li>

<li class="nav-item">

<a class="nav-link"
data-bs-toggle="tab"
href="#password">

<i class="fa-solid fa-key"></i>

Change Password

</a>

</li>

<li class="nav-item">

<a class="nav-link"
data-bs-toggle="tab"
href="#backup">

<i class="fa-solid fa-database"></i>

Database Backup

</a>

</li>

<li class="nav-item">

<a class="nav-link"
data-bs-toggle="tab"
href="#system">

<i class="fa-solid fa-server"></i>

System Info

</a>

</li>

</ul>

<div class="tab-content">

<div class="tab-pane fade show active"
id="company">

<form
method="post"
enctype="multipart/form-data">

<div class="row"><div class="col-md-6 mb-3">

<label class="form-label">

Company Name

</label>

<input
type="text"
name="company_name"
class="form-control"
value="<?= htmlspecialchars($company['company_name']) ?>"
required>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Phone

</label>

<input
type="text"
name="phone"
class="form-control"
value="<?= htmlspecialchars($company['phone']) ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Email

</label>

<input
type="email"
name="email"
class="form-control"
value="<?= htmlspecialchars($company['email']) ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Website

</label>

<input
type="text"
name="website"
class="form-control"
value="<?= htmlspecialchars($company['website']) ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Tax Number

</label>

<input
type="text"
name="tax_number"
class="form-control"
value="<?= htmlspecialchars($company['tax_number']) ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Currency

</label>

<select
name="currency"
class="form-select">

<option value="BDT" <?= ($company['currency']=="BDT")?"selected":"" ?>>

Bangladeshi Taka (BDT)

</option>

<option value="USD" <?= ($company['currency']=="USD")?"selected":"" ?>>

US Dollar (USD)

</option>

<option value="EUR" <?= ($company['currency']=="EUR")?"selected":"" ?>>

Euro (EUR)

</option>

<option value="INR" <?= ($company['currency']=="INR")?"selected":"" ?>>

Indian Rupee (INR)

</option>

</select>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Financial Year

</label>

<input
type="text"
name="financial_year"
class="form-control"
placeholder="2026-2027"
value="<?= htmlspecialchars($company['financial_year']) ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Company Logo

</label>

<input
type="file"
name="logo"
id="logo"
class="form-control"
accept=".jpg,.jpeg,.png,.gif,.webp">

<small class="text-muted">

Allowed: JPG, PNG, GIF, WEBP (Maximum 2MB)

</small>

</div>

<div class="col-12 mb-3">

<label class="form-label">

Company Address

</label>

<textarea
name="address"
rows="4"
class="form-control"><?= htmlspecialchars($company['address']) ?></textarea>

</div>

<div class="col-12 mb-4 text-center">

<?php
if(
!empty($company['logo']) &&
file_exists("assets/uploads/".$company['logo'])
){
?>

<img
id="logoPreview"
src="assets/uploads/<?= htmlspecialchars($company['logo']) ?>"
class="img-thumbnail shadow"
style="max-height:150px;">

<?php
}
else
{
?>

<img
id="logoPreview"
src=""
class="img-thumbnail shadow"
style="display:none;max-height:150px;">

<?php } ?>

</div>

<div class="col-12">

<button
type="submit"
name="update_company"
class="btn btn-primary">

<i class="fa-solid fa-floppy-disk"></i>

Save Company Information

</button>

<button
type="reset"
class="btn btn-secondary">

<i class="fa-solid fa-rotate-left"></i>

Reset

</button>

</div>

</div>

</form>

</div>

<!-- END COMPANY TAB --></div>

<!-- ================================
     CHANGE PASSWORD TAB
================================ -->

<div class="tab-pane fade" id="password">

<?php

if(isset($_POST['change_password']))
{

    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $admin_id = $_SESSION['user_id'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT password
         FROM users
         WHERE id=?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $admin_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $user = mysqli_fetch_assoc($result);

    if(!password_verify($current_password,$user['password']))
    {

        echo '<div class="alert alert-danger">
        Current password is incorrect.
        </div>';

    }
    elseif($new_password != $confirm_password)
    {

        echo '<div class="alert alert-danger">
        New password and Confirm password do not match.
        </div>';

    }
    elseif(strlen($new_password) < 6)
    {

        echo '<div class="alert alert-warning">
        Password must be at least 6 characters.
        </div>';

    }
    else
    {

        $hash = password_hash(
            $new_password,
            PASSWORD_DEFAULT
        );

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET password=?
             WHERE id=?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $hash,
            $admin_id
        );

        if(mysqli_stmt_execute($stmt))
        {

            logActivity(
                $conn,
                "Settings",
                "Password Change",
                $admin_id,
                "Administrator changed own password."
            );

            echo '<div class="alert alert-success">
            Password changed successfully.
            </div>';

        }
        else
        {

            echo '<div class="alert alert-danger">
            Failed to change password.
            </div>';

        }

    }

}

?>

<form method="post">

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">

Current Password

</label>

<input
type="password"
name="current_password"
class="form-control"
required>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

New Password

</label>

<input
type="password"
name="new_password"
class="form-control"
required>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Confirm Password

</label>

<input
type="password"
name="confirm_password"
class="form-control"
required>

</div>

<div class="col-12">

<button
type="submit"
name="change_password"
class="btn btn-success">

<i class="fa-solid fa-key"></i>

Change Password

</button>

</div>

</div>

</form>

</div>

<!-- END PASSWORD TAB --><!-- ======================================
     DATABASE BACKUP TAB
====================================== -->

<div class="tab-pane fade" id="backup">

<div class="card border-0">

<div class="card-body">

<h5 class="mb-3">

<i class="fa-solid fa-database"></i>

Database Backup

</h5>

<p class="text-muted">

Download a backup of your Office Accounting database.

</p>

<a
href="backup_database.php"
class="btn btn-primary">

<i class="fa-solid fa-download"></i>

Download Database Backup

</a>

<hr>

<div class="alert alert-info mb-0">

<strong>Note:</strong>

Keep database backups in a safe location.

</div>

</div>

</div>

</div>

<!-- ======================================
     SYSTEM INFORMATION TAB
====================================== -->

<div class="tab-pane fade" id="system">

<div class="card border-0">

<div class="card-body">

<h5 class="mb-4">

<i class="fa-solid fa-server"></i>

System Information

</h5>

<div class="row">

<div class="col-md-6">

<table class="table table-bordered">

<tr>

<th width="45%">

Software Name

</th>

<td>

Office Accounting System

</td>

</tr>

<tr>

<th>

Version

</th>

<td>

1.0

</td>

</tr>

<tr>

<th>

PHP Version

</th>

<td>

<?= phpversion(); ?>

</td>

</tr>

<tr>

<th>

MySQL Version

</th>

<td>

<?= mysqli_get_server_info($conn); ?>

</td>

</tr>

<tr>

<th>

Server

</th>

<td>

<?= $_SERVER['SERVER_SOFTWARE']; ?>

</td>

</tr>

<tr>

<th>

Operating System

</th>

<td>

<?= PHP_OS; ?>

</td>

</tr>

</table>

</div>

<div class="col-md-6">

<table class="table table-bordered">

<tr>

<th width="45%">

Company

</th>

<td>

<?= htmlspecialchars($company['company_name']); ?>

</td>

</tr>

<tr>

<th>

Financial Year

</th>

<td>

<?= htmlspecialchars($company['financial_year']); ?>

</td>

</tr>

<tr>

<th>

Currency

</th>

<td>

<?= htmlspecialchars($company['currency']); ?>

</td>

</tr>

<tr>

<th>

Current User

</th>

<td>

<?= htmlspecialchars($_SESSION['name']); ?>

</td>

</tr>

<tr>

<th>

Role

</th>

<td>

<?= ucfirst($_SESSION['role']); ?>

</td>

</tr>

<tr>

<th>

Current Date

</th>

<td>

<?= date('d-M-Y h:i A'); ?>

</td>

</tr>

</table>

</div>

</div>

</div>

</div>

</div>

<!-- END TAB CONTENT -->

</div>

</div>

</div><?php include 'footer.php'; ?>

<script>

document.addEventListener("DOMContentLoaded",function(){

    /* ==========================
       Logo Preview
    ========================== */

    const logoInput=document.getElementById("logo");

    const preview=document.getElementById("logoPreview");

    if(logoInput)
    {

        logoInput.addEventListener("change",function(e){

            const file=e.target.files[0];

            if(!file)
            {
                return;
            }

            const allowed=[
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/webp"
            ];

            if(!allowed.includes(file.type))
            {
                alert("Only JPG, PNG, GIF and WEBP images are allowed.");

                logoInput.value="";

                return;
            }

            if(file.size > (2*1024*1024))
            {
                alert("Maximum logo size is 2 MB.");

                logoInput.value="";

                return;
            }

            const reader=new FileReader();

            reader.onload=function(e){

                preview.src=e.target.result;

                preview.style.display="block";

            };

            reader.readAsDataURL(file);

        });

    }

    /* ==========================
       Password Validation
    ========================== */

    const passForm=document.querySelector('form');

    if(passForm)
    {

        passForm.addEventListener("submit",function(){

            const newPass=document.querySelector("input[name='new_password']");

            const confirmPass=document.querySelector("input[name='confirm_password']");

            if(newPass && confirmPass)
            {

                if(newPass.value!=confirmPass.value)
                {
                    alert("New Password and Confirm Password do not match.");

                    event.preventDefault();

                    return false;
                }

            }

        });

    }

});

</script>

</body>

</html>