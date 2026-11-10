<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/audit.php';

$message = "";
$error = "";

/* ==========================
   LOAD COMPANY
========================== */

$result = mysqli_query($conn,"SELECT * FROM company WHERE id=1");

if(mysqli_num_rows($result)==0)
{
    mysqli_query($conn,"
        INSERT INTO company(company_name)
        VALUES('My Company')
    ");

    $result = mysqli_query($conn,"SELECT * FROM company WHERE id=1");
}

$company = mysqli_fetch_assoc($result);

/* ==========================
   UPDATE COMPANY
========================== */

if(isset($_POST['update_company']))
{

    $company_name   = trim($_POST['company_name']);
    $address        = trim($_POST['address']);
    $phone          = trim($_POST['phone']);
    $email          = trim($_POST['email']);
    $website        = trim($_POST['website']);
    $tax_number     = trim($_POST['tax_number']);
    $financial_year = trim($_POST['financial_year']);
    $currency       = trim($_POST['currency']);

    $logo = $company['logo'];

    /* ==========================
       LOGO UPLOAD
    ========================== */

    if(isset($_FILES['logo']) && $_FILES['logo']['error']==0)
    {

        $allowed = ['jpg','jpeg','png','gif','webp'];

        $ext = strtolower(pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION));

        if(!in_array($ext,$allowed))
        {
            $error = "Only JPG, PNG, GIF and WEBP files are allowed.";
        }
        else
        {

            if(!is_dir("assets/uploads"))
            {
                mkdir("assets/uploads",0777,true);
            }

            $newLogo = "company_".time().".".$ext;

            if(move_uploaded_file(
                $_FILES['logo']['tmp_name'],
                "assets/uploads/".$newLogo
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
                $error = "Unable to upload company logo.";
            }

        }

    }

    /* ==========================
       UPDATE DATABASE
    ========================== */

    if($error=="")
    {

        $stmt = mysqli_prepare(

            $conn,

            "UPDATE company
            SET
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

                "Company",

                "Update",

                1,

                "Updated Company Information"

            );

            $message = "Company information updated successfully.";

            $result = mysqli_query($conn,"SELECT * FROM company WHERE id=1");

            $company = mysqli_fetch_assoc($result);

        }
        else
        {
            $error = "Database update failed.";
        }

    }

}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4>

<i class="fa-solid fa-building"></i>

Company Information

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

<form method="post" enctype="multipart/form-data">

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

<option value="BDT" <?= $company['currency']=="BDT"?"selected":"" ?>>

Bangladeshi Taka (BDT)

</option>

<option value="USD" <?= $company['currency']=="USD"?"selected":"" ?>>

US Dollar (USD)

</option>

<option value="EUR" <?= $company['currency']=="EUR"?"selected":"" ?>>

Euro (EUR)

</option>

<option value="INR" <?= $company['currency']=="INR"?"selected":"" ?>>

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
class="form-control"
accept=".jpg,.jpeg,.png,.gif,.webp">

<small class="text-muted">

Allowed: JPG, PNG, GIF, WEBP

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

<?php
if(
!empty($company['logo']) &&
file_exists("assets/uploads/".$company['logo'])
){
?>

<div class="col-12 text-center mb-4">

<label class="form-label d-block">

Current Logo

</label>

<img
src="assets/uploads/<?= htmlspecialchars($company['logo']) ?>"
class="img-thumbnail shadow"
style="max-height:140px;">

</div>

<?php } ?>

<div class="col-12">

<button
type="submit"
name="update_company"
class="btn btn-primary">

<i class="fa-solid fa-floppy-disk"></i>

Update Company Information

</button>

<a
href="dashboard.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

</div>

</div>

</form>

</div>

</div>

</div><?php include 'footer.php'; ?>

<script>

document.addEventListener("DOMContentLoaded", function(){

    const logoInput = document.querySelector("input[name='logo']");

    if(logoInput)
    {

        logoInput.addEventListener("change", function(e){

            const file = e.target.files[0];

            if(!file)
            {
                return;
            }

            const allowed = [
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/webp"
            ];

            if(!allowed.includes(file.type))
            {
                alert("Only JPG, PNG, GIF and WEBP images are allowed.");

                logoInput.value = "";

                return;
            }

            if(file.size > (2 * 1024 * 1024))
            {
                alert("Maximum logo size is 2 MB.");

                logoInput.value = "";

                return;
            }

            let preview = document.getElementById("logoPreview");

            if(!preview)
            {

                preview = document.createElement("img");

                preview.id = "logoPreview";

                preview.className = "img-thumbnail mt-3 shadow";

                preview.style.maxHeight = "140px";

                logoInput.parentNode.appendChild(preview);

            }

            const reader = new FileReader();

            reader.onload = function(ev){

                preview.src = ev.target.result;

            };

            reader.readAsDataURL(file);

        });

    }

});

</script>

</body>

</html>