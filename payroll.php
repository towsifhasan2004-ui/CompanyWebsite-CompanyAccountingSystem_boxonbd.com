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

/*=========================================
DEFAULT MONTH
=========================================*/

$salaryMonth = isset($_GET['month'])
    ? $_GET['month']
    : date('Y-m');

/*=========================================
LOAD CASH / BANK ACCOUNTS
=========================================*/

$cashAccounts = mysqli_query($conn,"
SELECT

a.id,
a.account_code,
a.account_name

FROM accounts a

INNER JOIN account_groups g

ON g.id=a.group_id

WHERE

g.account_type='Asset'

AND
(

LOWER(a.account_name) LIKE '%cash%'

OR

LOWER(a.account_name) LIKE '%bank%'

)

ORDER BY a.account_name
");

/*=========================================
LOAD PENDING SALARIES
=========================================*/

$stmt = mysqli_prepare($conn,"
SELECT

ss.id,
ss.employee_id,
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

ON e.id=ss.employee_id

WHERE

DATE_FORMAT(ss.salary_month,'%Y-%m')=?

AND

ss.status='Pending'

ORDER BY e.employee_name
");

mysqli_stmt_bind_param(
$stmt,
"s",
$salaryMonth
);

mysqli_stmt_execute($stmt);

$salaryList = mysqli_stmt_get_result($stmt);

/*==================================================
PAY SALARY
==================================================*/

if(isset($_POST['pay_salary']))
{

    $salarySheetId = (int)$_POST['salary_sheet_id'];

    $cashAccount   = (int)$_POST['cash_account'];

    $paymentDate   = $_POST['payment_date'];

    $remarks       = trim($_POST['remarks']);

    if($cashAccount<=0)
    {
        $error="Please select Cash/Bank Account.";
    }
    else
    {

        mysqli_begin_transaction($conn);

        try{

            /*=========================================
            LOAD SALARY SHEET
            =========================================*/

            $stmt=mysqli_prepare($conn,"
            SELECT *
            FROM salary_sheets
            WHERE id=?
            LIMIT 1
            ");

            mysqli_stmt_bind_param($stmt,"i",$salarySheetId);

            mysqli_stmt_execute($stmt);

            $salary=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if(!$salary)
            {
                throw new Exception("Salary sheet not found.");
            }

            if($salary['status']=="Paid")
            {
                throw new Exception("Salary already paid.");
            }

            $employeeId = $salary['employee_id'];

            $basicSalary = $salary['basic_salary'];

            $advanceDeduction = $salary['advance_deduction'];

            $otherDeduction = $salary['other_deduction'];

            $netSalary = $salary['net_salary'];

            /*=========================================
            SALARY EXPENSE ACCOUNT
            =========================================*/

            $salaryExpense = mysqli_query($conn,"
            SELECT id
            FROM accounts
            WHERE account_code='5001'
            LIMIT 1
            ");

            if(mysqli_num_rows($salaryExpense)==0)
            {
                throw new Exception("Salary Expense account (5001) not found.");
            }

            $salaryExpenseId =
            mysqli_fetch_assoc($salaryExpense)['id'];

            /*=========================================
            EMPLOYEE ADVANCE ACCOUNT
            =========================================*/

            $advanceAccount = mysqli_query($conn,"
            SELECT id
            FROM accounts
            WHERE account_code='1010'
            LIMIT 1
            ");

            if(mysqli_num_rows($advanceAccount)==0)
            {
                throw new Exception("Employee Advance account (1010) not found.");
            }

            $advanceAccountId =
            mysqli_fetch_assoc($advanceAccount)['id'];

            /*=========================================
            CREATE VOUCHER
            =========================================*/

            $voucherNo = "PV-".date("YmdHis");

            $voucherType = "Payment";

            $narration =
            "Salary Payment";

            $createdBy =
            $_SESSION['user_id'];

            $stmt=mysqli_prepare($conn,"
            INSERT INTO vouchers
            (
                voucher_no,
                voucher_date,
                voucher_type,
                narration,
                status,
                created_by,
                remarks
            )
            VALUES
            (
                ?,?,
                ?,
                ?,
                'Approved',
                ?,
                ?
            )
            ");

            mysqli_stmt_bind_param(

                $stmt,

                "ssssis",

                $voucherNo,
                $paymentDate,
                $voucherType,
                $narration,
                $createdBy,
                $remarks

            );

            mysqli_stmt_execute($stmt);

            $voucherId =
            mysqli_insert_id($conn);            /*=========================================
            DEBIT : SALARY EXPENSE
            =========================================*/

            $stmt = mysqli_prepare($conn,"
            INSERT INTO voucher_details
            (
                voucher_id,
                account_id,
                debit,
                credit
            )
            VALUES
            (?,?,?,?)
            ");

            $debit  = $basicSalary;
            $credit = 0;

            mysqli_stmt_bind_param(
                $stmt,
                "iidd",
                $voucherId,
                $salaryExpenseId,
                $debit,
                $credit
            );

            mysqli_stmt_execute($stmt);

            /*=========================================
            CREDIT : EMPLOYEE ADVANCE
            =========================================*/

            if($advanceDeduction>0)
            {

                $stmt = mysqli_prepare($conn,"
                INSERT INTO voucher_details
                (
                    voucher_id,
                    account_id,
                    debit,
                    credit
                )
                VALUES
                (?,?,?,?)
                ");

                $debit  = 0;
                $credit = $advanceDeduction;

                mysqli_stmt_bind_param(
                    $stmt,
                    "iidd",
                    $voucherId,
                    $advanceAccountId,
                    $debit,
                    $credit
                );

                mysqli_stmt_execute($stmt);

            }

            /*=========================================
            CREDIT : CASH / BANK
            =========================================*/

            $stmt = mysqli_prepare($conn,"
            INSERT INTO voucher_details
            (
                voucher_id,
                account_id,
                debit,
                credit
            )
            VALUES
            (?,?,?,?)
            ");

            $debit  = 0;
            $credit = $netSalary;

            mysqli_stmt_bind_param(
                $stmt,
                "iidd",
                $voucherId,
                $cashAccount,
                $debit,
                $credit
            );

            mysqli_stmt_execute($stmt);

            /*=========================================
            INSERT SALARY PAYMENT
            =========================================*/

            $stmt = mysqli_prepare($conn,"
            INSERT INTO salary_payments
            (
                salary_month,
                payment_date,
                employee_id,
                cash_account_id,
                gross_salary,
                deductions,
                advance_deduction,
                net_salary,
                voucher_id,
                remarks,
                status,
                created_by
            )
            VALUES
            (
                ?,?,?,?,?,?,?,?,?,?,
                'Paid',
                ?
            )
            ");

            mysqli_stmt_bind_param(

                $stmt,

                "ssidddddisi",

                $salary['salary_month'],
                $paymentDate,
                $employeeId,
                $cashAccount,
                $basicSalary,
                $otherDeduction,
                $advanceDeduction,
                $netSalary,
                $voucherId,
                $remarks,
                $createdBy

            );

            mysqli_stmt_execute($stmt);

            /*=========================================
            UPDATE SALARY SHEET
            =========================================*/

            $stmt = mysqli_prepare($conn,"
            UPDATE salary_sheets
            SET
                status='Paid',
                voucher_id=?
            WHERE id=?
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $voucherId,
                $salarySheetId
            );

            mysqli_stmt_execute($stmt);

            /*=========================================
            UPDATE STAFF ADVANCE
            =========================================*/

            if($advanceDeduction>0)
            {

                mysqli_query($conn,"
                UPDATE staff_advances

                SET

                recovered_amount =
                recovered_amount + {$advanceDeduction},

                balance_amount =
                balance_amount - {$advanceDeduction},

                status =
                IF(
                    (balance_amount-{$advanceDeduction})<=0,
                    'Recovered',
                    'Partial'
                )

                WHERE

                employee_id={$employeeId}

                AND

                balance_amount>0

                ORDER BY id

                LIMIT 1
                ");

            }

            mysqli_commit($conn);

            $_SESSION['success']="Salary paid successfully.";

            header("Location: payroll.php?month=".$salaryMonth);

            exit();

        }

        catch(Exception $e)
        {

            mysqli_rollback($conn);

            $error=$e->getMessage();

        }

    }

}?><div class="table-responsive">

<table class="table table-bordered table-hover align-middle" id="payrollTable">

<thead class="table-dark">

<tr>

<th width="60">SL</th>

<th>Employee Code</th>

<th>Employee Name</th>

<th>Department</th>

<th>Designation</th>

<th class="text-end">Basic Salary</th>

<th class="text-end text-danger">Advance</th>

<th class="text-end text-danger">Other Deduction</th>

<th class="text-end text-success">Net Salary</th>

<th width="330">Payment</th>

</tr>

</thead>

<tbody>

<?php

$sl=1;

$totalBasic=0;
$totalAdvance=0;
$totalOther=0;
$totalNet=0;

while($row=mysqli_fetch_assoc($salaryList))
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

<td class="text-end fw-bold text-success">

<?= number_format($row['net_salary'],2) ?>

</td>

<td>

<form method="POST">

<input
type="hidden"
name="salary_sheet_id"
value="<?= $row['id'] ?>">

<div class="row g-1">

<div class="col-md-12">

<select
name="cash_account"
class="form-select form-select-sm"
required>

<option value="">

Select Cash / Bank

</option>

<?php

mysqli_data_seek($cashAccounts,0);

while($acc=mysqli_fetch_assoc($cashAccounts))
{

?>

<option value="<?= $acc['id'] ?>">

<?= htmlspecialchars($acc['account_code']) ?>

-

<?= htmlspecialchars($acc['account_name']) ?>

</option>

<?php } ?>

</select>

</div>

<div class="col-md-5">

<input
type="date"
name="payment_date"
class="form-control form-control-sm"
value="<?= date('Y-m-d') ?>"
required>

</div>

<div class="col-md-4">

<input
type="text"
name="remarks"
class="form-control form-control-sm"
placeholder="Remarks">

</div>

<div class="col-md-3 d-grid">

<button
type="submit"
name="pay_salary"
class="btn btn-success btn-sm">

<i class="fa-solid fa-money-check-dollar"></i>

Pay

</button>

</div>

</div>

</form>

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

<a
href="salary_sheet.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back to Salary Sheet

</a>

</div>

<div class="col-md-6 text-end">

<button
type="button"
class="btn btn-primary"
onclick="printPayroll()">

<i class="fa-solid fa-print"></i>

Print Payroll

</button>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?><script>

$(document).ready(function(){

    $('#payrollTable').DataTable({

        pageLength:25,

        ordering:true,

        responsive:true,

        searching:true,

        lengthMenu:[
            [25,50,100,-1],
            [25,50,100,"All"]
        ]

    });

});

</script><script>

function printPayroll()
{

    var content=document.querySelector(".card").innerHTML;

    var win=window.open("","","width=1400,height=900");

    win.document.write(`

<html>

<head>

<title>Payroll Register</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

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
form,
input,
select,
.dataTables_wrapper{

display:none !important;

}

</style>

</head>

<body>

<h2 style="text-align:center">

Payroll Register

</h2>

<h5 style="text-align:center">

Salary Month :
<?= date('F Y',strtotime($salaryMonth."-01")) ?>

</h5>

${content}

</body>

</html>

`);

    win.document.close();

    win.focus();

    win.print();

    win.close();

}

</script>