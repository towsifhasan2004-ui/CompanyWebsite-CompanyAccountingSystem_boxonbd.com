<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager','accountant']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

$error="";
$success="";

/*=====================================
AUTO ADVANCE NO
======================================*/

$advance_no="ADV-000001";

$q=mysqli_query($conn,"
SELECT advance_no
FROM staff_advances
ORDER BY id DESC
LIMIT 1
");

if(mysqli_num_rows($q)>0)
{
    $r=mysqli_fetch_assoc($q);

    $num=(int)substr($r['advance_no'],4);

    $num++;

    $advance_no="ADV-".str_pad($num,6,"0",STR_PAD_LEFT);
}

/*=====================================
LOAD EMPLOYEES
======================================*/

$employees=mysqli_query($conn,"
SELECT
id,
employee_code,
employee_name
FROM employees
WHERE status='Active'
ORDER BY employee_name
");

/*=====================================
LOAD CASH/BANK ACCOUNTS
======================================*/

$accounts=mysqli_query($conn,"
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
ORDER BY account_name
");
?>

<div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h4 class="mb-0">

<i class="fa-solid fa-money-bill-wave"></i>

Staff Advance Entry

</h4>

</div>

<div class="card-body"><?php if($error!=""){ ?>

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

Advance No

</label>

<input
type="text"
name="advance_no"
class="form-control"
value="<?= $advance_no ?>"
readonly>

</div>

<div class="col-md-3 mb-3">

<label class="form-label">

Advance Date

</label>

<input
type="date"
name="advance_date"
class="form-control"
value="<?= date('Y-m-d') ?>"
required>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

Employee

</label>

<select
name="employee_id"
class="form-select"
required>

<option value="">

Select Employee

</option>

<?php

mysqli_data_seek($employees,0);

while($emp=mysqli_fetch_assoc($employees))
{

?>

<option value="<?= $emp['id'] ?>">

<?= htmlspecialchars($emp['employee_code']) ?>

-

<?= htmlspecialchars($emp['employee_name']) ?>

</option>

<?php } ?>

</select>

</div>

</div>


<div class="row">

<div class="col-md-4 mb-3">

<label class="form-label">

Advance Amount

</label>

<input
type="number"
step="0.01"
name="amount"
class="form-control"
required>

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Paid From

</label>

<select
name="cash_account"
class="form-select"
required>

<option value="">

Select Cash/Bank

</option>

<?php

mysqli_data_seek($accounts,0);

while($acc=mysqli_fetch_assoc($accounts))
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

<div class="col-md-4 mb-3">

<label class="form-label">

Reference No

</label>

<input
type="text"
name="reference_no"
class="form-control">

</div>

</div>


<div class="row">

<div class="col-lg-12 mb-3">

<label class="form-label">

Remarks

</label>

<textarea
name="remarks"
rows="3"
class="form-control"></textarea>

</div>

</div>

<hr>

<div class="text-end">

<button
type="submit"
name="save"
class="btn btn-success">

<i class="fa-solid fa-floppy-disk"></i>

Save Advance

</button>

<a
href="staff_advance_list.php"
class="btn btn-secondary">

<i class="fa-solid fa-list"></i>

Advance List

</a>

</div>

</form><?php

if(isset($_POST['save']))
{

    $advance_no   = trim($_POST['advance_no']);
    $advance_date = $_POST['advance_date'];
    $employee_id  = (int)$_POST['employee_id'];
    $cash_account = (int)$_POST['cash_account'];
    $amount       = (float)$_POST['amount'];
    $reference_no = trim($_POST['reference_no']);
    $remarks      = trim($_POST['remarks']);

    if($employee_id==0)
    {
        $error="Select Employee.";
    }
    elseif($cash_account==0)
    {
        $error="Select Cash/Bank Account.";
    }
    elseif($amount<=0)
    {
        $error="Invalid Advance Amount.";
    }
    else
    {

        mysqli_begin_transaction($conn);

        try{

            /*=====================================
            FIND STAFF ADVANCE ACCOUNT
            =====================================*/

            $acc=mysqli_fetch_assoc(

                mysqli_query(

                    $conn,

                    "SELECT id
                     FROM accounts
                     WHERE account_name='Staff Advance'
                     LIMIT 1"

                )

            );

            if(!$acc)
            {
                throw new Exception("Staff Advance account not found.");
            }

            $staffAdvanceAccount=$acc['id'];

            /*=====================================
            SAVE STAFF ADVANCE
            =====================================*/

            $stmt=mysqli_prepare($conn,"
            INSERT INTO staff_advances
            (
                advance_no,
                advance_date,
                employee_id,
                amount,
                cash_account_id,
                reference_no,
                remarks
            )
            VALUES
            (?,?,?,?,?,?,?)
            ");

            mysqli_stmt_bind_param(

                $stmt,

                "ssidiss",

                $advance_no,
                $advance_date,
                $employee_id,
                $amount,
                $cash_account,
                $reference_no,
                $remarks

            );

            mysqli_stmt_execute($stmt);

            $advance_id=mysqli_insert_id($conn);

            /*=====================================
            CREATE VOUCHER
            =====================================*/

            $voucherNo="JV-".date("YmdHis");

            $stmt=mysqli_prepare($conn,"
            INSERT INTO vouchers
            (
                voucher_no,
                voucher_date,
                voucher_type,
                narration,
                remarks,
                status,
                created_by
            )
            VALUES
            (?,?,?,?,?,'Approved',?)
            ");

            $voucherType="Journal";

            $createdBy=$_SESSION['user_id'];

            mysqli_stmt_bind_param(

                $stmt,

                "sssssi",

                $voucherNo,
                $advance_date,
                $voucherType,
                $remarks,
                $reference_no,
                $createdBy

            );

            mysqli_stmt_execute($stmt);

            $voucherId=mysqli_insert_id($conn);            /*=====================================
            DEBIT : STAFF ADVANCE
            =====================================*/

            $stmt=mysqli_prepare($conn,"
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

            $debit=$amount;
            $credit=0;

            mysqli_stmt_bind_param(

                $stmt,

                "iidd",

                $voucherId,
                $staffAdvanceAccount,
                $debit,
                $credit

            );

            mysqli_stmt_execute($stmt);

            /*=====================================
            CREDIT : CASH/BANK
            =====================================*/

            $stmt=mysqli_prepare($conn,"
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

            $debit=0;
            $credit=$amount;

            mysqli_stmt_bind_param(

                $stmt,

                "iidd",

                $voucherId,
                $cash_account,
                $debit,
                $credit

            );

            mysqli_stmt_execute($stmt);

            /*=====================================
            LINK ADVANCE WITH VOUCHER
            =====================================*/

            mysqli_query($conn,"
            UPDATE staff_advances
            SET voucher_id=$voucherId
            WHERE id=$advance_id
            ");

            mysqli_commit($conn);

            header("Location:staff_advance_list.php?success=1");

            exit();

        }
        catch(Exception $e)
        {

            mysqli_rollback($conn);

            $error=$e->getMessage();

        }

    }

}

?>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>