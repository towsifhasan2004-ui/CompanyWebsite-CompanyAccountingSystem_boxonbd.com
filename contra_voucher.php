<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';
checkRole(['accountant']);

$error = "";
$success = "";

/* =========================
   Voucher Number Generator
========================= */

$voucher_no = "CV-000001";

$result = mysqli_query($conn,"
SELECT voucher_no
FROM vouchers
WHERE voucher_type='Contra'
ORDER BY id DESC
LIMIT 1");

if(mysqli_num_rows($result)>0)
{
    $row = mysqli_fetch_assoc($result);

    $last = intval(substr($row['voucher_no'],3));

    $voucher_no = "CV-".str_pad($last+1,6,"0",STR_PAD_LEFT);
}

/* =========================
   SAVE CONTRA VOUCHER
========================= */

if(isset($_POST['save']))
{
    $voucher_date = $_POST['voucher_date'];
    $from_account = (int)$_POST['from_account'];
    $to_account   = (int)$_POST['to_account'];
    $amount       = (float)$_POST['amount'];
    $narration    = trim($_POST['narration']);
    $created_by   = $_SESSION['user_id'];

    if($from_account==0 || $to_account==0 || $amount<=0)
    {
        $error = "All fields are required.";
    }
    else
    {
        mysqli_begin_transaction($conn);

        try
        {
            /* ======================
               Insert Voucher Header
            ====================== */

            $stmt = mysqli_prepare($conn,"
                INSERT INTO vouchers
                (voucher_no,voucher_date,voucher_type,narration,created_by,status)
                VALUES (?,?,?,?,?,?)
            ");

            $type = "Contra";
            $status = "Pending";

            mysqli_stmt_bind_param(
                $stmt,
                "ssssis",
                $voucher_no,
                $voucher_date,
                $type,
                $narration,
                $created_by,
                $status
            );

            mysqli_stmt_execute($stmt);

            $voucher_id = mysqli_insert_id($conn);

            /* ======================
               Debit (TO Account)
======================== */

            $stmt1 = mysqli_prepare($conn,"
                INSERT INTO voucher_details
                (voucher_id,account_id,debit,credit)
                VALUES (?,?,?,0)
            ");

            mysqli_stmt_bind_param(
                $stmt1,
                "iid",
                $voucher_id,
                $to_account,
                $amount
            );

            mysqli_stmt_execute($stmt1);

            /* ======================
               Credit (FROM Account)
======================== */

            $stmt2 = mysqli_prepare($conn,"
                INSERT INTO voucher_details
                (voucher_id,account_id,debit,credit)
                VALUES (?,?,0,?)
            ");

            mysqli_stmt_bind_param(
                $stmt2,
                "iid",
                $voucher_id,
                $from_account,
                $amount
            );

            mysqli_stmt_execute($stmt2);

            mysqli_commit($conn);
            logActivity(
    $conn,
    "Contra Voucher",
    "Create",
    $voucher_id,
    "Created Contra Voucher : ".$voucher_no
);

            header("Location: contra_voucher.php?saved=1");
            exit();

        }
        catch(Exception $e)
        {
            mysqli_rollback($conn);
            $error = "Unable to save contra voucher.";
        }
    }
}
?>

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="container-fluid">

<div class="row">

<!-- =========================
     CONTRA FORM
========================= -->

<div class="col-lg-4">

<div class="card shadow">

<div class="card-header bg-secondary text-white">

<h5 class="mb-0">

<i class="fa-solid fa-right-left"></i>

Contra Voucher

</h5>

</div>

<div class="card-body">

<?php if(isset($_GET['saved'])){ ?>

<div class="alert alert-success">
Voucher Saved Successfully
</div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="alert alert-danger">
<?= $error ?>
</div>

<?php } ?>

<form method="POST">

<div class="mb-3">

<label>Voucher No</label>

<input type="text"
class="form-control"
value="<?= $voucher_no ?>"
readonly>

</div>

<div class="mb-3">

<label>Date</label>

<input type="date"
name="voucher_date"
class="form-control"
value="<?= date('Y-m-d') ?>"
required>

</div>

<!-- FROM ACCOUNT -->

<div class="mb-3">

<label>From Account (Cash/Bank)</label>

<select name="from_account"
class="form-select"
required>

<option value="">Select</option>

<?php
$q=mysqli_query($conn,"
SELECT id,account_name
FROM accounts
WHERE status='Active'
ORDER BY account_name
");

while($a=mysqli_fetch_assoc($q)){
?>

<option value="<?= $a['id'] ?>">
<?= htmlspecialchars($a['account_name']) ?>
</option>

<?php } ?>

</select>

</div>

<!-- TO ACCOUNT -->

<div class="mb-3">

<label>To Account (Cash/Bank)</label>

<select name="to_account"
class="form-select"
required>

<option value="">Select</option>

<?php
$q=mysqli_query($conn,"
SELECT id,account_name
FROM accounts
WHERE status='Active'
ORDER BY account_name
");

while($a=mysqli_fetch_assoc($q)){
?>

<option value="<?= $a['id'] ?>">
<?= htmlspecialchars($a['account_name']) ?>
</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label>Amount</label>

<input type="number"
step="0.01"
name="amount"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Narration</label>

<textarea name="narration"
rows="3"
class="form-control"></textarea>

</div>

<button type="submit"
name="save"
class="btn btn-secondary w-100">

Save Contra Voucher

</button>

</form>

</div>

</div>

</div>

<!-- =========================
     VOUCHER LIST
========================= -->

<div class="col-lg-8">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h5 class="mb-0">

Contra Voucher List

</h5>

</div>

<div class="card-body">

<table class="table table-bordered table-hover datatable">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Voucher No</th>
<th>Date</th>
<th>From</th>
<th>To</th>
<th class="text-end">Amount</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php

$sql="

SELECT
v.id,
v.voucher_no,
v.voucher_date,
v.status,

MAX(CASE WHEN vd.credit>0 THEN a.account_name END) AS from_acc,
MAX(CASE WHEN vd.debit>0 THEN a.account_name END) AS to_acc,

SUM(vd.debit) AS amount

FROM vouchers v

INNER JOIN voucher_details vd
ON vd.voucher_id=v.id

INNER JOIN accounts a
ON a.id=vd.account_id

WHERE v.voucher_type='Contra'

GROUP BY v.id,v.voucher_no,v.voucher_date,v.status

ORDER BY v.id DESC
";

$res=mysqli_query($conn,$sql);

while($row=mysqli_fetch_assoc($res)){
?>

<tr>

<td><?= $row['id'] ?></td>

<td><b><?= $row['voucher_no'] ?></b></td>

<td><?= date('d-M-Y',strtotime($row['voucher_date'])) ?></td>

<td><?= $row['from_acc'] ?></td>

<td><?= $row['to_acc'] ?></td>

<td class="text-end">
<?= number_format($row['amount'],2) ?>
</td>

<td>
<?php
switch($row['status'])
{
    case 'Pending':
        echo '<span class="badge bg-warning">Pending</span>';
        break;

    case 'Checked':
        echo '<span class="badge bg-info">Checked</span>';
        break;

    case 'Approved':
        echo '<span class="badge bg-success">Approved</span>';
        break;

    case 'Rejected':
        echo '<span class="badge bg-danger">Rejected</span>';
        break;
}
?>
</td>

<td>

<a href="view_voucher.php?id=<?= $row['id']; ?>"
class="btn btn-sm btn-info"
title="View">
<i class="fa-solid fa-eye"></i>
</a>

<?php if($row['status']=="Approved"){ ?>

<a
href="print_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-sm btn-secondary"
target="_blank"
title="Print">

<i class="fa-solid fa-print"></i>

</a>

<?php } else { ?>

<button
class="btn btn-sm btn-secondary"
disabled
title="Only approved vouchers can be printed">

<i class="fa-solid fa-print"></i>

</button>

<?php } ?>

<?php if($row['status']=="Pending" || $row['status']=="Rejected"){ ?>

<a
href="edit_contra_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-sm btn-warning"
title="Edit">

<i class="fa-solid fa-pen-to-square"></i>

</a>

<?php } else { ?>

<button
class="btn btn-sm btn-warning"
disabled
title="Checked/Approved vouchers cannot be edited">

<i class="fa-solid fa-lock"></i>

</button>

<?php } ?>
<?php if($row['status']=="Pending" || $row['status']=="Rejected"){ ?>

<a
href="delete_contra_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-sm btn-danger"
onclick="return confirm('Are you sure you want to delete this Contra Voucher?')"
title="Delete">

<i class="fa-solid fa-trash"></i>

</a>

<?php } else { ?>

<button
class="btn btn-sm btn-danger"
disabled
title="Checked/Approved vouchers cannot be deleted">

<i class="fa-solid fa-lock"></i>

</button>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>