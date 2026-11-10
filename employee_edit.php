<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error   = "";
$success = "";

/*=========================================
CHECK ID
=========================================*/

if(!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: employee_list.php");
    exit();
}

$id = (int)$_GET['id'];

/*=========================================
LOAD EMPLOYEE
=========================================*/

$stmt = mysqli_prepare($conn,"
SELECT *
FROM employees
WHERE id=?
LIMIT 1
");

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0)
{
    die("Employee not found.");
}

$employee = mysqli_fetch_assoc($result);/*=========================================
UPDATE EMPLOYEE
=========================================*/

if(isset($_POST['update']))
{

    $employee_code     = trim($_POST['employee_code']);
    $employee_name     = trim($_POST['employee_name']);
    $father_name       = trim($_POST['father_name']);
    $mother_name       = trim($_POST['mother_name']);
    $gender            = $_POST['gender'];
    $date_of_birth     = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : NULL;
    $mobile            = trim($_POST['mobile']);
    $email             = trim($_POST['email']);
    $nid               = trim($_POST['nid']);
    $present_address   = trim($_POST['present_address']);
    $permanent_address = trim($_POST['permanent_address']);
    $department        = trim($_POST['department']);
    $designation       = trim($_POST['designation']);
    $joining_date      = !empty($_POST['joining_date']) ? $_POST['joining_date'] : NULL;
    $basic_salary      = (float)$_POST['basic_salary'];
    $status            = $_POST['status'];

    if($employee_code=="")
    {
        $error="Employee Code is required.";
    }
    elseif($employee_name=="")
    {
        $error="Employee Name is required.";
    }
    else
    {

        /*=====================================
        CHECK DUPLICATE EMPLOYEE CODE
        =====================================*/

        $check = mysqli_prepare($conn,"
        SELECT id
        FROM employees
        WHERE employee_code=?
        AND id<>?
        ");

        mysqli_stmt_bind_param(
            $check,
            "si",
            $employee_code,
            $id
        );

        mysqli_stmt_execute($check);

        mysqli_stmt_store_result($check);

        if(mysqli_stmt_num_rows($check)>0)
        {

            $error="Employee Code already exists.";

        }
        else
        {

            $stmt = mysqli_prepare($conn,"
            UPDATE employees
            SET

                employee_code=?,
                employee_name=?,
                father_name=?,
                mother_name=?,
                gender=?,
                date_of_birth=?,
                mobile=?,
                email=?,
                nid=?,
                present_address=?,
                permanent_address=?,
                department=?,
                designation=?,
                joining_date=?,
                basic_salary=?,
                status=?

            WHERE id=?

            ");

            mysqli_stmt_bind_param(

                $stmt,

                "ssssssssssssssdsi",

                $employee_code,
                $employee_name,
                $father_name,
                $mother_name,
                $gender,
                $date_of_birth,
                $mobile,
                $email,
                $nid,
                $present_address,
                $permanent_address,
                $department,
                $designation,
                $joining_date,
                $basic_salary,
                $status,
                $id

            );

            if(mysqli_stmt_execute($stmt))
            {

                $success="Employee updated successfully.";

                /* Reload updated data */

                $stmt = mysqli_prepare($conn,"
                SELECT *
                FROM employees
                WHERE id=?
                ");

                mysqli_stmt_bind_param($stmt,"i",$id);

                mysqli_stmt_execute($stmt);

                $employee = mysqli_fetch_assoc(
                    mysqli_stmt_get_result($stmt)
                );

            }
            else
            {

                $error=mysqli_error($conn);

            }

        }

    }

}?>

<div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-warning text-dark">

<h4 class="mb-0">

<i class="fa-solid fa-user-pen"></i>

Edit Employee

</h4>

</div>

<div class="card-body">

<?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<?php if($success!=""){ ?>

<div class="alert alert-success">

<?= $success ?>

</div>

<?php } ?>

<form method="POST">

<div class="row">

<div class="col-md-3 mb-3">

<label class="form-label">

Employee Code

<span class="text-danger">*</span>

</label>

<input
type="text"
name="employee_code"
class="form-control"
value="<?= htmlspecialchars($employee['employee_code']) ?>"
required>

</div>

<div class="col-md-5 mb-3">

<label class="form-label">

Employee Name

<span class="text-danger">*</span>

</label>

<input
type="text"
name="employee_name"
class="form-control"
value="<?= htmlspecialchars($employee['employee_name']) ?>"
required>

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Gender

</label>

<select
name="gender"
class="form-select">

<option value="Male"
<?=($employee['gender']=='Male')?'selected':''?>>

Male

</option>

<option value="Female"
<?=($employee['gender']=='Female')?'selected':''?>>

Female

</option>

<option value="Other"
<?=($employee['gender']=='Other')?'selected':''?>>

Other

</option>

</select>

</div>

</div>


<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">

Father Name

</label>

<input
type="text"
name="father_name"
class="form-control"
value="<?= htmlspecialchars($employee['father_name']) ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Mother Name

</label>

<input
type="text"
name="mother_name"
class="form-control"
value="<?= htmlspecialchars($employee['mother_name']) ?>">

</div>

</div>


<div class="row">

<div class="col-md-3 mb-3">

<label class="form-label">

Date of Birth

</label>

<input
type="date"
name="date_of_birth"
class="form-control"
value="<?= $employee['date_of_birth'] ?>">

</div>

<div class="col-md-3 mb-3">

<label class="form-label">

Joining Date

</label>

<input
type="date"
name="joining_date"
class="form-control"
value="<?= $employee['joining_date'] ?>">

</div>

<div class="col-md-3 mb-3">

<label class="form-label">

Department

</label>

<input
type="text"
name="department"
class="form-control"
value="<?= htmlspecialchars($employee['department']) ?>">

</div>

<div class="col-md-3 mb-3">

<label class="form-label">

Designation

</label>

<input
type="text"
name="designation"
class="form-control"
value="<?= htmlspecialchars($employee['designation']) ?>">

</div>

</div><div class="row">

<div class="col-md-4 mb-3">

<label class="form-label">

Mobile

</label>

<input
type="text"
name="mobile"
class="form-control"
value="<?= htmlspecialchars($employee['mobile']) ?>">

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Email

</label>

<input
type="email"
name="email"
class="form-control"
value="<?= htmlspecialchars($employee['email']) ?>">

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

National ID

</label>

<input
type="text"
name="nid"
class="form-control"
value="<?= htmlspecialchars($employee['nid']) ?>">

</div>

</div>


<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">

Present Address

</label>

<textarea
name="present_address"
rows="3"
class="form-control"><?= htmlspecialchars($employee['present_address']) ?></textarea>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Permanent Address

</label>

<textarea
name="permanent_address"
rows="3"
class="form-control"><?= htmlspecialchars($employee['permanent_address']) ?></textarea>

</div>

</div>


<div class="row">

<div class="col-md-4 mb-3">

<label class="form-label">

Basic Salary

</label>

<div class="input-group">

<span class="input-group-text">৳</span>

<input
type="number"
step="0.01"
min="0"
name="basic_salary"
class="form-control"
value="<?= number_format((float)$employee['basic_salary'],2,'.','') ?>">

</div>

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Opening Advance

</label>

<div class="input-group">

<span class="input-group-text">৳</span>

<input
type="text"
class="form-control"
value="<?= number_format((float)$employee['opening_advance'],2) ?>"
readonly>

</div>

<small class="text-muted">

Opening Advance is managed from the Staff Advance module.

</small>

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Status

</label>

<select
name="status"
class="form-select">

<option value="Active"
<?=($employee['status']=='Active')?'selected':''?>>

Active

</option>

<option value="Inactive"
<?=($employee['status']=='Inactive')?'selected':''?>>

Inactive

</option>

</select>

</div>

</div>

<hr>

<div class="text-end">

<a
href="employee_list.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

<button
type="submit"
name="update"
class="btn btn-primary">

<i class="fa-solid fa-floppy-disk"></i>

Update Employee

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>