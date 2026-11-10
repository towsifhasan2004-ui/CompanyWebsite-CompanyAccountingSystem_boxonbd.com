<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*=========================================
CHECK ID
=========================================*/

if(!isset($_GET['id']))
{
    header("Location:staff_advance_list.php");
    exit();
}

$id = (int)$_GET['id'];

$error = "";
$success = "";

/*=========================================
LOAD ADVANCE
=========================================*/

$stmt = mysqli_prepare($conn,"
SELECT

sa.*,

e.employee_code,
e.employee_name

FROM staff_advances sa

INNER JOIN employees e
ON e.id = sa.employee_id

WHERE sa.id=?

LIMIT 1
");

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0)
{
    die("Advance Record Not Found.");
}

$advance = mysqli_fetch_assoc($result);

/*=========================================
SAVE UPDATE
=========================================*/

if(isset($_POST['update']))
{

    $advance_date = $_POST['advance_date'];

    $amount = (float)$_POST['amount'];

    $remarks = trim($_POST['remarks']);

    /*------------------------------------
    VALIDATION
    ------------------------------------*/

    if($amount <= 0)
    {
        $error = "Advance amount must be greater than zero.";
    }
    elseif($amount < $advance['recovered_amount'])
    {
        $error = "Advance amount cannot be less than recovered amount.";
    }
    else
    {

        $balance = $amount - $advance['recovered_amount'];

        $stmt = mysqli_prepare($conn,"
        UPDATE staff_advances
        SET

        advance_date=?,
        amount=?,
        balance_amount=?,
        remarks=?

        WHERE id=?
        ");

        mysqli_stmt_bind_param(

            $stmt,

            "sddsi",

            $advance_date,

            $amount,

            $balance,

            $remarks,

            $id

        );

        if(mysqli_stmt_execute($stmt))
        {
            header("Location:staff_advance_list.php?updated=1");
            exit();
        }
        else
        {
            $error = mysqli_error($conn);
        }

    }

}

?>

<div class="container-fluid">

<div class="row">

<div class="col-lg-8 mx-auto">

<div class="card shadow">

<div class="card-header bg-warning text-dark">

<h4>

<i class="fa-solid fa-pen"></i>

Edit Staff Advance

</h4>

</div>

<div class="card-body"><?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<form method="POST">

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">

Employee

</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($advance['employee_code']) ?> - <?= htmlspecialchars($advance['employee_name']) ?>"
readonly>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Advance No

</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($advance['advance_no']) ?>"
readonly>

</div>

</div>

<div class="row">

<div class="col-md-4 mb-3">

<label class="form-label">

Advance Date

</label>

<input
type="date"
name="advance_date"
class="form-control"
value="<?= $advance['advance_date'] ?>"
required>

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Advance Amount

</label>

<input
type="number"
step="0.01"
min="<?= $advance['recovered_amount'] ?>"
name="amount"
class="form-control"
value="<?= $advance['amount'] ?>"
required>

<small class="text-muted">

Recovered :
<?= number_format($advance['recovered_amount'],2) ?>

</small>

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Outstanding Balance

</label>

<input
type="text"
class="form-control fw-bold text-danger"
value="<?= number_format($advance['balance_amount'],2) ?>"
readonly>

</div>

</div>

<div class="row">

<div class="col-lg-12 mb-3">

<label class="form-label">

Remarks

</label>

<textarea
name="remarks"
rows="4"
class="form-control"><?= htmlspecialchars($advance['remarks']) ?></textarea>

</div>

</div>

<hr><div class="text-end">

<a
href="staff_advance_list.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

<button
type="submit"
name="update"
class="btn btn-warning">

<i class="fa-solid fa-floppy-disk"></i>

Update Advance

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>