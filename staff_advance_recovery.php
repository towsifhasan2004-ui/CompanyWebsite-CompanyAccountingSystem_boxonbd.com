<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager','accountant']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*==================================================
CHECK ADVANCE ID
==================================================*/

if(!isset($_GET['id']))
{
    header("Location: staff_advance_list.php");
    exit();
}

$advanceId = (int)$_GET['id'];

/*==================================================
LOAD ADVANCE
==================================================*/

$stmt = mysqli_prepare($conn,"
SELECT
    sa.*,
    e.employee_code,
    e.employee_name
FROM staff_advances sa
INNER JOIN employees e
    ON e.id=sa.employee_id
WHERE sa.id=?
");

mysqli_stmt_bind_param($stmt,"i",$advanceId);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0)
{
    die("Advance record not found.");
}

$advance = mysqli_fetch_assoc($result);

/*==================================================
CURRENT BALANCE
==================================================*/

$currentBalance = (float)$advance['balance_amount'];

$error   = "";
$success = "";

/*==================================================
RECOVER ADVANCE
==================================================*/

if(isset($_POST['recover']))
{

    $recoveryDate = $_POST['recovery_date'];

    $amount = (float)$_POST['amount'];

    $cashAccount = (int)$_POST['cash_account'];

    $remarks = trim($_POST['remarks']);

    if($amount<=0)
    {
        $error="Recovery amount must be greater than zero.";
    }
    elseif($amount>$currentBalance)
    {
        $error="Recovery amount cannot exceed outstanding balance.";
    }
    else
    {

        mysqli_begin_transaction($conn);

        try{

            /*====================================
            CREATE VOUCHER NUMBER
            ====================================*/

            $voucherNo = "RV-".date("YmdHis");

            $narration = "Staff Advance Recovery - ".$advance['employee_name'];

            /*====================================
            INSERT VOUCHER
            ====================================*/

            $stmt = mysqli_prepare($conn,"
            INSERT INTO vouchers
            (
                voucher_no,
                voucher_date,
                voucher_type,
                narration,
                status,
                created_by
            )
            VALUES
            (
                ?,
                ?,
                'Receipt',
                ?,
                'Approved',
                ?
            )
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "sssi",
                $voucherNo,
                $recoveryDate,
                $narration,
                $_SESSION['user_id']
            );

            mysqli_stmt_execute($stmt);

            $voucherId = mysqli_insert_id($conn);

            /*====================================
            FIND STAFF ADVANCE ACCOUNT
            ====================================*/

            $advanceAccount = 0;

            $acc = mysqli_query($conn,"
            SELECT id
            FROM accounts
            WHERE
            LOWER(account_name) LIKE '%staff advance%'
            LIMIT 1
            ");

            if(mysqli_num_rows($acc))
            {
                $advanceAccount = mysqli_fetch_assoc($acc)['id'];
            }

            if($advanceAccount==0)
            {
                throw new Exception("Staff Advance account not found.");
            }            /*====================================
            DEBIT CASH/BANK ACCOUNT
            ====================================*/

            $stmt = mysqli_prepare($conn,"
            INSERT INTO voucher_details
            (
                voucher_id,
                account_id,
                debit,
                credit
            )
            VALUES
            (
                ?,
                ?,
                ?,
                0
            )
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "iid",
                $voucherId,
                $cashAccount,
                $amount
            );

            mysqli_stmt_execute($stmt);

            /*====================================
            CREDIT STAFF ADVANCE ACCOUNT
            ====================================*/

            $stmt = mysqli_prepare($conn,"
            INSERT INTO voucher_details
            (
                voucher_id,
                account_id,
                debit,
                credit
            )
            VALUES
            (
                ?,
                ?,
                0,
                ?
            )
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "iid",
                $voucherId,
                $advanceAccount,
                $amount
            );

            mysqli_stmt_execute($stmt);

            /*====================================
            INSERT RECOVERY HISTORY
            ====================================*/

            $stmt = mysqli_prepare($conn,"
            INSERT INTO staff_advance_recoveries
            (
                advance_id,
                voucher_id,
                recovery_date,
                amount,
                remarks,
                created_by
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "iisdsi",
                $advanceId,
                $voucherId,
                $recoveryDate,
                $amount,
                $remarks,
                $_SESSION['user_id']
            );

            mysqli_stmt_execute($stmt);

            /*====================================
            UPDATE ADVANCE BALANCE
            ====================================*/

            $newRecovered = $advance['recovered_amount'] + $amount;

            $newBalance = $advance['balance_amount'] - $amount;

            $status = ($newBalance <= 0)
                ? 'Recovered'
                : 'Partial';

            $stmt = mysqli_prepare($conn,"
            UPDATE staff_advances
            SET
                recovered_amount=?,
                balance_amount=?,
                status=?
            WHERE id=?
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "ddsi",
                $newRecovered,
                $newBalance,
                $status,
                $advanceId
            );

            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);

            $_SESSION['success'] = "Advance recovered successfully.";

            header("Location: staff_advance_list.php");

            exit();

        }
        catch(Exception $e)
        {

            mysqli_rollback($conn);

            $error = $e->getMessage();

        }

    }

}

?><div class="container-fluid">

<div class="row">

<div class="col-lg-8 mx-auto">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h4 class="mb-0">

<i class="fa-solid fa-money-bill-transfer"></i>

Recover Staff Advance

</h4>

</div>

<div class="card-body">

<?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<?php if(isset($_SESSION['success'])){ ?>

<div class="alert alert-success">

<?= $_SESSION['success']; ?>

</div>

<?php unset($_SESSION['success']); } ?>

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

<div class="col-md-3 mb-3">

<label class="form-label">

Advance Amount

</label>

<input
type="text"
class="form-control text-end"
value="<?= number_format($advance['advance_amount'],2) ?>"
readonly>

</div>

<div class="col-md-3 mb-3">

<label class="form-label">

Outstanding

</label>

<input
type="text"
class="form-control text-end fw-bold text-danger"
value="<?= number_format($advance['balance_amount'],2) ?>"
readonly>

</div>

</div>

<div class="row">

<div class="col-md-4 mb-3">

<label class="form-label">

Recovery Date

</label>

<input
type="date"
name="recovery_date"
class="form-control"
value="<?= date('Y-m-d') ?>"
required>

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Recovery Amount

</label>

<input
type="number"
step="0.01"
min="0.01"
max="<?= $advance['balance_amount'] ?>"
name="amount"
class="form-control"
required>

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Receive To

</label>

<select
name="cash_account"
class="form-select"
required>

<option value="">Select Cash / Bank</option>

<?php

$cashAccounts=mysqli_query($conn,"
SELECT
id,
account_code,
account_name
FROM accounts
WHERE
status='Active'
AND
(
LOWER(account_name) LIKE '%cash%'
OR
LOWER(account_name) LIKE '%bank%'
)
ORDER BY account_name
");

while($cash=mysqli_fetch_assoc($cashAccounts))
{
?>

<option value="<?= $cash['id'] ?>">

<?= htmlspecialchars($cash['account_code']) ?>

-

<?= htmlspecialchars($cash['account_name']) ?>

</option>

<?php } ?>

</select>

</div>

</div><div class="row">

<div class="col-lg-12 mb-3">

<label class="form-label">

Remarks

</label>

<textarea
name="remarks"
class="form-control"
rows="4"
placeholder="Recovery remarks (optional)"></textarea>

</div>

</div>

<hr>

<div class="d-flex justify-content-between">

<a
href="staff_advance_list.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

<button
type="submit"
name="recover"
class="btn btn-success">

<i class="fa-solid fa-money-bill-transfer"></i>

Recover Advance

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>