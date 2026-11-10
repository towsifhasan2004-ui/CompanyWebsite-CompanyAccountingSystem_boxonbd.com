<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager','accountant']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*=========================================
CHECK ID
=========================================*/

if(!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: payroll.php");
    exit();
}

$paymentId = (int)$_GET['id'];

/*=========================================
LOAD COMPANY
=========================================*/

$company = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT *
FROM company
LIMIT 1
"));

/*=========================================
LOAD PAYSLIP
=========================================*/

$stmt = mysqli_prepare($conn,"
SELECT

sp.*,

e.employee_code,
e.employee_name,
e.department,
e.designation,

v.voucher_no,
v.voucher_date

FROM salary_payments sp

INNER JOIN employees e
ON e.id=sp.employee_id

LEFT JOIN vouchers v
ON v.id=sp.voucher_id

WHERE sp.id=?

LIMIT 1
");

mysqli_stmt_bind_param(
$stmt,
"i",
$paymentId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0)
{
    die("Payslip not found.");
}

$row = mysqli_fetch_assoc($result);

?><div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-9">

<div class="card shadow" id="printArea">

<div class="card-header text-center">

<h3 class="mb-1">

<?= htmlspecialchars($company['company_name']) ?>

</h3>

<p class="mb-1">

<?= htmlspecialchars($company['address']) ?>

</p>

<h4>

SALARY PAYSLIP

</h4>

<hr>

</div>

<div class="card-body"><div class="row mb-4">

<div class="col-md-6">

<table class="table table-bordered">

<tr>

<th width="40%">

Employee Code

</th>

<td>

<?= htmlspecialchars($row['employee_code']) ?>

</td>

</tr>

<tr>

<th>

Employee Name

</th>

<td>

<?= htmlspecialchars($row['employee_name']) ?>

</td>

</tr>

<tr>

<th>

Department

</th>

<td>

<?= htmlspecialchars($row['department']) ?>

</td>

</tr>

<tr>

<th>

Designation

</th>

<td>

<?= htmlspecialchars($row['designation']) ?>

</td>

</tr>

</table>

</div>

<div class="col-md-6">

<table class="table table-bordered">

<tr>

<th width="40%">

Salary Month

</th>

<td>

<?= date('F Y',strtotime($row['salary_month'])) ?>

</td>

</tr>

<tr>

<th>

Payment Date

</th>

<td>

<?= !empty($row['payment_date']) ? date('d M Y',strtotime($row['payment_date'])) : '-' ?>

</td>

</tr>

<tr>

<th>

Voucher No

</th>

<td>

<?= !empty($row['voucher_no']) ? htmlspecialchars($row['voucher_no']) : '-' ?>

</td>

</tr>

<tr>

<th>

Status

</th>

<td>

<span class="badge bg-success">

<?= htmlspecialchars($row['status']) ?>

</span>

</td>

</tr>

</table>

</div>

</div><!-- ==============================
SALARY BREAKDOWN
============================== -->

<h5 class="mb-3">

<i class="fa-solid fa-money-check-dollar"></i>

Salary Details

</h5>

<table class="table table-bordered">

<thead class="table-light">

<tr>

<th>Description</th>

<th width="220" class="text-end">

Amount (৳)

</th>

</tr>

</thead>

<tbody>

<tr>

<td>

Gross Salary

</td>

<td class="text-end">

<?= number_format($row['gross_salary'],2) ?>

</td>

</tr>

<tr>

<td>

Advance Deduction

</td>

<td class="text-end text-danger">

(<?= number_format($row['advance_deduction'],2) ?>)

</td>

</tr>

<tr>

<td>

Other Deduction

</td>

<td class="text-end text-danger">

(<?= number_format($row['deductions'],2) ?>)

</td>

</tr>

<tr class="table-success">

<th>

Net Salary Paid

</th>

<th class="text-end">

৳ <?= number_format($row['net_salary'],2) ?>

</th>

</tr>

</tbody>

</table><hr class="my-5">

<div class="row text-center mt-5">

    <div class="col-md-4">

        ___________________________

        <br>

        Employee Signature

    </div>

    <div class="col-md-4">

        ___________________________

        <br>

        Accountant

    </div>

    <div class="col-md-4">

        ___________________________

        <br>

        Authorized Signatory

    </div>

</div><div class="mt-5 d-flex justify-content-between">

    <a href="payroll.php"
       class="btn btn-secondary">

        <i class="fa-solid fa-arrow-left"></i>

        Back

    </a>

    <button
        type="button"
        class="btn btn-primary"
        onclick="printPayslip()">

        <i class="fa-solid fa-print"></i>

        Print Payslip

    </button>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?><script>

function printPayslip()
{

    var contents =
    document.getElementById("printArea").innerHTML;

    var win =
    window.open('','','width=900,height=700');

    win.document.write(`

<html>

<head>

<title>Salary Payslip</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<style>

@page{

size:A4;

margin:12mm;

}

body{

font-family:Arial,Helvetica,sans-serif;

font-size:13px;

padding:15px;

}

table{

width:100%;

border-collapse:collapse;

}

table th,
table td{

border:1px solid #000;

padding:8px;

}

.table-light{

background:#f8f9fa !important;

}

.table-success{

background:#d1e7dd !important;

}

hr{

border:1px solid #000;

}

</style>

</head>

<body>

${contents}

</body>

</html>

`);

    win.document.close();

    win.focus();

    win.print();

    win.close();

}

</script>