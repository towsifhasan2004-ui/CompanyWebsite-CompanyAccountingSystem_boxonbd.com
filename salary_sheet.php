<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager','accountant']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = "";
$success = "";

/*=========================================
DEFAULT SALARY MONTH
=========================================*/

$salaryMonth = isset($_POST['salary_month'])
    ? $_POST['salary_month']
    : date('Y-m');

/*=========================================
LOAD ACTIVE EMPLOYEES
=========================================*/

$employees = mysqli_query($conn,"
SELECT

id,
employee_code,
employee_name,
department,
designation,
basic_salary

FROM employees

WHERE status='Active'

ORDER BY employee_name
");

/*=========================================
CHECK EXISTING SALARY SHEET
=========================================*/

$existingSalary = [];

$check = mysqli_prepare($conn,"
SELECT employee_id
FROM salary_sheets
WHERE DATE_FORMAT(salary_month,'%Y-%m')=?
");

mysqli_stmt_bind_param($check,"s",$salaryMonth);

mysqli_stmt_execute($check);

$result = mysqli_stmt_get_result($check);

while($row = mysqli_fetch_assoc($result))
{
    $existingSalary[$row['employee_id']] = true;
}
/*=========================================
GENERATE SALARY SHEET
=========================================*/

if(isset($_POST['generate']))
{

    mysqli_begin_transaction($conn);

    try
    {

        $salaryMonth = $_POST['salary_month'];

        $createdBy = $_SESSION['user_id'];

        /*=========================================
        LOAD ACTIVE EMPLOYEES
        =========================================*/

        $empQuery = mysqli_query($conn,"
        SELECT

            id,
            basic_salary

        FROM employees

        WHERE status='Active'

        ORDER BY employee_name
        ");

        while($emp = mysqli_fetch_assoc($empQuery))
        {

            $employeeId = (int)$emp['id'];

            $basicSalary = (float)$emp['basic_salary'];

            /*=========================================
            CHECK DUPLICATE
            =========================================*/

            $check = mysqli_prepare($conn,"
            SELECT id
            FROM salary_sheets
            WHERE
            employee_id=?
            AND DATE_FORMAT(salary_month,'%Y-%m')=?
            LIMIT 1
            ");

            mysqli_stmt_bind_param(
                $check,
                "is",
                $employeeId,
                $salaryMonth
            );

            mysqli_stmt_execute($check);

            mysqli_stmt_store_result($check);

            if(mysqli_stmt_num_rows($check)>0)
            {
                continue;
            }

            /*=========================================
            OUTSTANDING ADVANCE
            =========================================*/

            $advanceDeduction = 0;

            $adv = mysqli_prepare($conn,"
            SELECT
            IFNULL(SUM(balance_amount),0)
            FROM staff_advances
            WHERE
            employee_id=?
            AND balance_amount>0
            ");

            mysqli_stmt_bind_param(
                $adv,
                "i",
                $employeeId
            );

            mysqli_stmt_execute($adv);

            mysqli_stmt_bind_result(
                $adv,
                $advanceDeduction
            );

            mysqli_stmt_fetch($adv);

            mysqli_stmt_close($adv);

            /*=========================================
            ERP RULE
            ==========================================
            Maximum 30% of salary
            =========================================*/

            $maximumRecovery = $basicSalary * 0.30;

            if($advanceDeduction > $maximumRecovery)
            {
                $advanceDeduction = $maximumRecovery;
            }

            /*=========================================
            OTHER DEDUCTION
            =========================================*/

            $otherDeduction = 0;

            /*=========================================
            NET SALARY
            =========================================*/

            $netSalary =

                $basicSalary
                -
                $advanceDeduction
                -
                $otherDeduction;

            if($netSalary < 0)
            {
                $netSalary = 0;
            }

            /*=========================================
            SAVE SALARY SHEET
            =========================================*/

            $stmt = mysqli_prepare($conn,"
            INSERT INTO salary_sheets
            (
                salary_month,
                employee_id,
                basic_salary,
                advance_deduction,
                other_deduction,
                net_salary,
                created_by
            )
            VALUES
            (
                ?,?,?,?,?,?,?
            )
            ");

            mysqli_stmt_bind_param(

                $stmt,

                "siddddi",

                $salaryMonth,
                $employeeId,
                $basicSalary,
                $advanceDeduction,
                $otherDeduction,
                $netSalary,
                $createdBy

            );

            mysqli_stmt_execute($stmt);

        }

        mysqli_commit($conn);

        $success = "Salary Sheet generated successfully.";

    }
    catch(Exception $e)
    {

        mysqli_rollback($conn);

        $error = $e->getMessage();

    }

}?><div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow border-0">

<div class="card-header bg-primary text-white">

<div class="d-flex justify-content-between align-items-center">

<div>

<h4 class="mb-0">

<i class="fa-solid fa-money-check-dollar"></i>

Salary Sheet

</h4>

<small>

Generate Monthly Salary Sheet

</small>

</div>

<div>

<a href="dashboard.php" class="btn btn-light">

<i class="fa-solid fa-arrow-left"></i>

Dashboard

</a>

</div>

</div>

</div>

<div class="card-body">

<?php if($error!=""){ ?>

<div class="alert alert-danger alert-dismissible fade show">

<?= $error ?>

<button
type="button"
class="btn-close"
data-bs-dismiss="alert">
</button>

</div>

<?php } ?>

<?php if($success!=""){ ?>

<div class="alert alert-success alert-dismissible fade show">

<?= $success ?>

<button
type="button"
class="btn-close"
data-bs-dismiss="alert">
</button>

</div>

<?php } ?>

<form method="POST">

<div class="row">

<div class="col-md-4">

<label class="form-label fw-bold">

Salary Month

</label>

<input
type="month"
name="salary_month"
class="form-control"
value="<?= $salaryMonth ?>"
required>

</div>

<div class="col-md-4">

<label class="form-label">

&nbsp;

</label>

<div>

<button
type="submit"
name="generate"
class="btn btn-success">

<i class="fa-solid fa-gears"></i>

Generate Salary Sheet

</button>

<a
href="salary_sheet.php"
class="btn btn-secondary">

<i class="fa-solid fa-rotate"></i>

Refresh

</a>

</div>

</div>

<div class="col-md-4">

<div class="alert alert-info mb-0">

<strong>ERP Note</strong>

<br>

Salary will be generated for all Active Employees.

Outstanding Staff Advance will automatically be deducted (Maximum 30%).

</div>

</div>

</div>

</form>

<hr><?php

/*=========================================
LOAD GENERATED SALARY SHEET
=========================================*/

$stmt = mysqli_prepare($conn,"
SELECT

ss.id,
ss.salary_month,
ss.basic_salary,
ss.advance_deduction,
ss.other_deduction,
ss.net_salary,
ss.status,

e.employee_code,
e.employee_name,
e.department,
e.designation

FROM salary_sheets ss

INNER JOIN employees e
ON e.id = ss.employee_id

WHERE DATE_FORMAT(ss.salary_month,'%Y-%m')=?

ORDER BY e.employee_name
");

mysqli_stmt_bind_param($stmt,"s",$salaryMonth);

mysqli_stmt_execute($stmt);

$salaryResult = mysqli_stmt_get_result($stmt);

$totalBasic = 0;
$totalAdvance = 0;
$totalOther = 0;
$totalNet = 0;

?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle" id="salaryTable">

<thead class="table-dark">

<tr>

<th width="60">SL</th>

<th>Employee Code</th>

<th>Employee Name</th>

<th>Department</th>

<th>Designation</th>

<th class="text-end">Basic Salary</th>

<th class="text-end">Advance</th>

<th class="text-end">Other Deduction</th>

<th class="text-end">Net Salary</th>

<th class="text-center">Status</th>

</tr>

</thead>

<tbody>

<?php

$sl = 1;

while($row = mysqli_fetch_assoc($salaryResult))
{

$totalBasic += $row['basic_salary'];
$totalAdvance += $row['advance_deduction'];
$totalOther += $row['other_deduction'];
$totalNet += $row['net_salary'];

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

<?= number_format($row['basic_salary'],2) ?>

</td>

<td class="text-end text-danger">

<?= number_format($row['advance_deduction'],2) ?>

</td>

<td class="text-end text-danger">

<?= number_format($row['other_deduction'],2) ?>

</td>

<td class="text-end text-success fw-bold">

<?= number_format($row['net_salary'],2) ?>

</td>

<td class="text-center">

<?php

if($row['status']=="Paid")
{

echo '<span class="badge bg-success">Paid</span>';

}
else
{

echo '<span class="badge bg-warning text-dark">Pending</span>';

}

?>

</td>

</tr>

<?php } ?>

</tbody>

<tfoot class="table-secondary">

<tr>

<th colspan="5" class="text-end">

TOTAL

</th>

<th class="text-end">

<?= number_format($totalBasic,2) ?>

</th>

<th class="text-end text-danger">

<?= number_format($totalAdvance,2) ?>

</th>

<th class="text-end text-danger">

<?= number_format($totalOther,2) ?>

</th>

<th class="text-end text-success">

<?= number_format($totalNet,2) ?>

</th>

<th></th>

</tr>

</tfoot>

</table>

</div><hr>

<div class="row">

    <div class="col-md-6">

        <a href="payroll.php?month=<?= $salaryMonth ?>"
        class="btn btn-success">

            <i class="fa-solid fa-money-check-dollar"></i>

            Process Payroll

        </a>

    </div>

    <div class="col-md-6 text-end">

        <button
        type="button"
        class="btn btn-primary"
        onclick="printSalarySheet()">

            <i class="fa-solid fa-print"></i>

            Print Salary Sheet

        </button>

    </div>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?><script>

$(document).ready(function(){

    $('#salaryTable').DataTable({

        pageLength:25,

        ordering:true,

        searching:true,

        responsive:true,

        lengthMenu:[
            [25,50,100,-1],
            [25,50,100,"All"]
        ]

    });

});

</script><script>

function printSalarySheet()
{

    var contents=document.querySelector(".card").innerHTML;

    var printWindow=window.open("","","width=1200,height=900");

    printWindow.document.write(`

<html>

<head>

<title>Salary Sheet</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>

@page{

    size:A4 landscape;

    margin:10mm;

}

body{

    font-family:Arial;

    font-size:12px;

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

.table-dark{

    background:#343a40 !important;

    color:#fff !important;

}

.btn,
.dataTables_wrapper,
.paginate_button,
.dataTables_filter,
.dataTables_length{

    display:none !important;

}

</style>

</head>

<body>

<h3 class="text-center">

Salary Sheet

</h3>

<p class="text-center">

Salary Month :
<strong><?= date('F Y',strtotime($salaryMonth.'-01')) ?></strong>

</p>

${contents}

</body>

</html>

`);

    printWindow.document.close();

    printWindow.focus();

    printWindow.print();

    printWindow.close();

}

</script>