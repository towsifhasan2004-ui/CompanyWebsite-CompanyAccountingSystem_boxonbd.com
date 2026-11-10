<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager','accountant']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error   = "";
$success = "";

/*=========================================================
COMPANY INFORMATION
=========================================================*/

$company = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT *
FROM company
LIMIT 1
"));

/*=========================================================
FILTERS
=========================================================*/

$employeeId = isset($_GET['employee_id'])
    ? (int)$_GET['employee_id']
    : 0;

$from = isset($_GET['from'])
    ? $_GET['from']
    : date('Y-01-01');

$to = isset($_GET['to'])
    ? $_GET['to']
    : date('Y-m-d');

/*=========================================================
EMPLOYEE LIST
=========================================================*/

$employees = mysqli_query($conn,"
SELECT

id,
employee_code,
employee_name

FROM employees

ORDER BY employee_name
");

/*=========================================================
SALARY HISTORY
=========================================================*/

$sql = "

SELECT

sp.id,

sp.salary_month,
sp.payment_date,

e.employee_code,
e.employee_name,
e.department,
e.designation,

sp.gross_salary,
sp.overtime,
sp.mobile_allowance,

sp.deductions,
sp.advance_deduction,
sp.net_salary,

sp.status,

v.voucher_no

FROM salary_payments sp

INNER JOIN employees e
ON e.id = sp.employee_id

LEFT JOIN vouchers v
ON v.id = sp.voucher_id

WHERE
sp.payment_date BETWEEN ? AND ?
";

if($employeeId>0)
{
    $sql .= " AND sp.employee_id=? ";
}

$sql .= "

ORDER BY

sp.salary_month DESC,
e.employee_name
";

$stmt = mysqli_prepare($conn,$sql);

if($employeeId>0)
{
    mysqli_stmt_bind_param(
        $stmt,
        "ssi",
        $from,
        $to,
        $employeeId
    );
}
else
{
    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $from,
        $to
    );
}

mysqli_stmt_execute($stmt);

$salaryHistory = mysqli_stmt_get_result($stmt);

/*=========================================================
TOTALS
=========================================================*/

$totalGross      = 0;
$totalOT         = 0;
$totalMobile     = 0;
$totalDeduction  = 0;
$totalAdvance    = 0;
$totalNet        = 0;
$totalEmployee   = 0;
?><div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<div class="d-flex justify-content-between align-items-center">

<div>

<h3 class="mb-1">

Employee Salary History

</h3>

<small>

Payroll Management

</small>

</div>

<a href="payroll.php" class="btn btn-light">

<i class="fa fa-arrow-left"></i>

Back

</a>

</div>

</div>

<div class="card-body">

<form method="GET">

<div class="row">

<div class="col-md-4">

<label class="form-label">

Employee

</label>

<select
name="employee_id"
class="form-select">

<option value="0">

All Employees

</option>

<?php

mysqli_data_seek($employees,0);

while($emp=mysqli_fetch_assoc($employees))
{

?>

<option
value="<?= $emp['id'] ?>"
<?= ($employeeId==$emp['id'])?'selected':'' ?>>

<?= htmlspecialchars($emp['employee_code']) ?>

-

<?= htmlspecialchars($emp['employee_name']) ?>

</option>

<?php } ?>

</select>

</div>

<div class="col-md-3">

<label class="form-label">

From Date

</label>

<input
type="date"
name="from"
class="form-control"
value="<?= $from ?>">

</div>

<div class="col-md-3">

<label class="form-label">

To Date

</label>

<input
type="date"
name="to"
class="form-control"
value="<?= $to ?>">

</div>

<div class="col-md-2">

<label class="form-label">

&nbsp;

</label>

<button
class="btn btn-primary w-100">

<i class="fa fa-search"></i>

Search

</button>

</div>

</div>

</form>

<hr>

<div id="printArea">

<div class="row mb-4">

<div class="col-md-6">

<h2 class="fw-bold mb-1">

<?= htmlspecialchars($company['company_name']) ?>

</h2>

<p class="mb-1">

<?= htmlspecialchars($company['address']) ?>

</p>

<?php if(!empty($company['phone'])){ ?>

<p class="mb-0">

Mobile :
<?= htmlspecialchars($company['phone']) ?>

</p>

<?php } ?>

<?php if(!empty($company['email'])){ ?>

<p class="mb-0">

Email :
<?= htmlspecialchars($company['email']) ?>

</p>

<?php } ?>

</div>

<div class="col-md-6 text-end">

<h2 class="text-primary">

Employee Salary History

</h2>

<table class="table table-bordered table-sm w-auto ms-auto">

<tr>

<th>

Report Date

</th>

<td>

<?= date('d-m-Y h:i A') ?>

</td>

</tr>

<tr>

<th>

From

</th>

<td>

<?= date('d-m-Y',strtotime($from)) ?>

</td>

</tr>

<tr>

<th>

To

</th>

<td>

<?= date('d-m-Y',strtotime($to)) ?>

</td>

</tr>

<tr>

<th>

Generated By

</th>

<td>

<?= htmlspecialchars($_SESSION['username']) ?>

</td>

</tr>

</table>

</div>

</div>

<hr>

<div class="row mb-4">

<div class="col-md-3">

<div class="card border-primary">

<div class="card-body text-center">

<h6>Total Employees</h6>

<h3 class="text-primary">

<?= mysqli_num_rows($salaryHistory) ?>

</h3>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card border-success">

<div class="card-body text-center">

<h6>From Date</h6>

<h4 class="text-success">

<?= date('d M Y',strtotime($from)) ?>

</h4>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card border-info">

<div class="card-body text-center">

<h6>To Date</h6>

<h4 class="text-info">

<?= date('d M Y',strtotime($to)) ?>

</h4>

</div>

</div>

</div>

<div class="col-md-3">

<div class="card border-warning">

<div class="card-body text-center">

<h6>Currency</h6>

<h4 class="text-warning">

BDT (৳)

</h4>

</div>

</div>

</div>

</div>

<div class="table-responsive"><table
id="salaryHistoryTable"
class="table table-bordered table-striped table-hover align-middle">

<thead class="table-dark text-center">

<tr>

<th>SL</th>

<th>Employee Code</th>

<th>Employee Name</th>

<th>Department</th>

<th>Designation</th>

<th class="text-end">Gross Salary</th>

<th class="text-end">OT</th>

<th class="text-end">Mobile</th>

<th class="text-end">Deduction</th>

<th class="text-end">Advance</th>

<th class="text-end">Net Salary</th>

<th>Payment Date</th>

<th>Voucher</th>

<th>Status</th>

<th>Payslip</th>

</tr>

</thead>

<tbody>

<?php

$sl=1;

while($row=mysqli_fetch_assoc($salaryHistory))
{

$totalEmployee++;

$totalGross += (float)$row['gross_salary'];
$totalOT += (float)$row['overtime'];
$totalMobile += (float)$row['mobile_allowance'];
$totalDeduction += (float)$row['deductions'];
$totalAdvance += (float)$row['advance_deduction'];
$totalNet += (float)$row['net_salary'];

?>

<tr>

<td class="text-center">

<?= $sl++ ?>

</td>

<td>

<?= htmlspecialchars($row['employee_code']) ?>

</td>

<td>

<strong>

<?= htmlspecialchars($row['employee_name']) ?>

</strong>

</td>

<td>

<?= htmlspecialchars($row['department']) ?>

</td>

<td>

<?= htmlspecialchars($row['designation']) ?>

</td>

<td class="text-end">

<?= number_format($row['gross_salary'],2) ?>

</td>

<td class="text-end text-primary">

<?= number_format($row['overtime'],2) ?>

</td>

<td class="text-end text-primary">

<?= number_format($row['mobile_allowance'],2) ?>

</td>

<td class="text-end text-danger">

<?= number_format($row['deductions'],2) ?>

</td>

<td class="text-end text-danger">

<?= number_format($row['advance_deduction'],2) ?>

</td>

<td class="text-end fw-bold text-success">

<?= number_format($row['net_salary'],2) ?>

</td>

<td class="text-center">

<?= !empty($row['payment_date']) ? date('d-m-Y',strtotime($row['payment_date'])) : '-' ?>

</td>

<td class="text-center">

<?= !empty($row['voucher_no']) ? htmlspecialchars($row['voucher_no']) : '-' ?>

</td>

<td class="text-center">

<?php if($row['status']=="Paid"){ ?>

<span class="badge bg-success">

Paid

</span>

<?php }else{ ?>

<span class="badge bg-warning text-dark">

Pending

</span>

<?php } ?>

</td>

<td class="text-center">

<a
href="payslip.php?id=<?= $row['id'] ?>"
class="btn btn-primary btn-sm">

<i class="fa fa-print"></i>

Payslip

</a>

</td>

</tr>

<?php } ?>

</tbody>

<tfoot class="table-secondary fw-bold">

<tr>

<td colspan="5" class="text-end">

Grand Total

</td>

<td class="text-end">

<?= number_format($totalGross,2) ?>

</td>

<td class="text-end text-primary">

<?= number_format($totalOT,2) ?>

</td>

<td class="text-end text-primary">

<?= number_format($totalMobile,2) ?>

</td>

<td class="text-end text-danger">

<?= number_format($totalDeduction,2) ?>

</td>

<td class="text-end text-danger">

<?= number_format($totalAdvance,2) ?>

</td>

<td class="text-end text-success">

<?= number_format($totalNet,2) ?>

</td>

<td colspan="4"></td>

</tr>

</tfoot>

</table>

</div>

<div class="mt-3">

<h5>

Total Salary Records :

<span class="badge bg-primary">

<?= $totalEmployee ?>

</span>

</h5>

</div>

</div>

</div>

</div>

</div>

</div><?php include 'footer.php'; ?>

<link rel="stylesheet"
href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<link rel="stylesheet"
href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>

$(document).ready(function(){

$('#salaryHistoryTable').DataTable({

responsive:true,

pageLength:25,

ordering:true,

autoWidth:false,

dom:'Bfrtip',

buttons:[

{
extend:'excelHtml5',
title:'Employee Salary History'
},

{
extend:'csvHtml5',
title:'Employee Salary History'
},

{
extend:'pdfHtml5',
title:'Employee Salary History',
orientation:'landscape',
pageSize:'A4'
},

{
extend:'copyHtml5'
}

]

});

});

</script>

<script>

function printSalaryHistory()
{

var contents=document.getElementById("printArea").innerHTML;

var win=window.open("","","width=1500,height=900");

win.document.write(`

<html>

<head>

<title>Employee Salary History</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<style>

@page{

size:A4 landscape;
margin:10mm;

}

body{

font-family:Calibri,Arial,sans-serif;
font-size:12px;
color:#000;

}

table{

width:100%;
border-collapse:collapse;

}

table th,
table td{

border:1px solid #000;
padding:6px;

}

thead{

display:table-header-group;

}

tfoot{

display:table-footer-group;

}

tr{

page-break-inside:avoid;

}

.table-dark th{

background:#d9d9d9 !important;
color:#000 !important;

}

.table-secondary{

background:#efefef !important;

}

.text-end{

text-align:right;

}

.text-center{

text-align:center;

}

.btn,
button,
input,
select,
textarea,
.dataTables_filter,
.dataTables_length,
.dataTables_info,
.dataTables_paginate,
.dt-buttons{

display:none !important;

}

.card,
.card-header,
.card-body{

border:none !important;
box-shadow:none !important;
background:#fff !important;

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

setTimeout(function(){

win.print();

win.close();

},500);

}

</script>