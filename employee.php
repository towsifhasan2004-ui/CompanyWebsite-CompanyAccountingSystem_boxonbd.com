<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

$error = "";
$success = "";

/*=========================================
AUTO EMPLOYEE CODE
=========================================*/

$employee_code = "EMP-000001";

$result = mysqli_query($conn,"
SELECT employee_code
FROM employees
ORDER BY id DESC
LIMIT 1
");

if(mysqli_num_rows($result)>0)
{
    $row = mysqli_fetch_assoc($result);

    $number = (int)substr($row['employee_code'],4);

    $number++;

    $employee_code = "EMP-".str_pad($number,6,"0",STR_PAD_LEFT);
}

/*=========================================
SAVE EMPLOYEE
=========================================*/

if(isset($_POST['save']))
{

    $employee_code = trim($_POST['employee_code']);
    $employee_name = trim($_POST['employee_name']);
    $father_name = trim($_POST['father_name']);
    $mother_name = trim($_POST['mother_name']);
    $gender = $_POST['gender'];
    $date_of_birth = $_POST['date_of_birth'];
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);
    $nid = trim($_POST['nid']);
    $present_address = trim($_POST['present_address']);
    $permanent_address = trim($_POST['permanent_address']);
    $department = trim($_POST['department']);
    $designation = trim($_POST['designation']);
    $joining_date = $_POST['joining_date'];
    $basic_salary = (float)$_POST['basic_salary'];
    $opening_advance = (float)$_POST['opening_advance'];
    $status = $_POST['status'];

    if($employee_name=="")
    {
        $error = "Employee Name is required.";
    }
    else
    {

        $stmt = mysqli_prepare($conn,"
        INSERT INTO employees
        (
            employee_code,
            employee_name,
            father_name,
            mother_name,
            gender,
            date_of_birth,
            mobile,
            email,
            nid,
            present_address,
            permanent_address,
            department,
            designation,
            joining_date,
            basic_salary,
            opening_advance,
            status
        )
        VALUES
        (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssssssssssdds",
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
            $opening_advance,
            $status
        );

        if(mysqli_stmt_execute($stmt))
        {
            header("Location: employee.php?success=1");
            exit();
        }
        else
        {
            $error = mysqli_error($conn);
        }

    }

}

if(isset($_GET['success']))
{
    $success = "Employee saved successfully.";
}
?><div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">

<i class="fa-solid fa-user-plus"></i>

Employee Master

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

</label>

<input
type="text"
name="employee_code"
class="form-control"
value="<?= $employee_code ?>"
readonly>

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
required>

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Gender

</label>

<select
name="gender"
class="form-select">

<option value="Male">Male</option>

<option value="Female">Female</option>

<option value="Other">Other</option>

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
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Mother Name

</label>

<input
type="text"
name="mother_name"
class="form-control">

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
class="form-control">

</div>

<div class="col-md-3 mb-3">

<label class="form-label">

Joining Date

</label>

<input
type="date"
name="joining_date"
class="form-control"
value="<?= date('Y-m-d') ?>">

</div>

<div class="col-md-3 mb-3">

<label class="form-label">

Department

</label>

<input
type="text"
name="department"
class="form-control">

</div>

<div class="col-md-3 mb-3">

<label class="form-label">

Designation

</label>

<input
type="text"
name="designation"
class="form-control">

</div>

</div>

<div class="row">

<div class="col-md-4 mb-3">

<label class="form-label">

Mobile

</label>

<input
type="text"
name="mobile"
class="form-control">

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Email

</label>

<input
type="email"
name="email"
class="form-control">

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

National ID

</label>

<input
type="text"
name="nid"
class="form-control">

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
class="form-control"></textarea>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Permanent Address

</label>

<textarea
name="permanent_address"
rows="3"
class="form-control"></textarea>

</div>

</div>

<div class="row">

<div class="col-md-4 mb-3">

<label class="form-label">

Basic Salary

</label>

<input
type="number"
step="0.01"
name="basic_salary"
class="form-control"
value="0.00">

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Opening Advance

</label>

<input
type="number"
step="0.01"
name="opening_advance"
class="form-control"
value="0.00">

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Status

</label>

<select
name="status"
class="form-select">

<option value="Active">Active</option>

<option value="Inactive">Inactive</option>

</select>

</div>

</div>

<hr>

<div class="text-end">

<button
type="submit"
name="save"
class="btn btn-success">

<i class="fa-solid fa-floppy-disk"></i>

Save Employee

</button>

<a
href="employee_list.php"
class="btn btn-secondary">

<i class="fa-solid fa-list"></i>

Employee List

</a>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>