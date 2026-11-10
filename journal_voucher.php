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

$voucher_no = "JV-000001";

$result = mysqli_query($conn,"
SELECT voucher_no
FROM vouchers
WHERE voucher_type='Journal'
ORDER BY id DESC
LIMIT 1");

if(mysqli_num_rows($result)>0)
{
    $row = mysqli_fetch_assoc($result);

    $last = intval(substr($row['voucher_no'],3));

    $voucher_no = "JV-".str_pad($last+1,6,"0",STR_PAD_LEFT);
}

/* =========================
   SAVE JOURNAL VOUCHER
========================= */

if(isset($_POST['save']))
{
    $voucher_date = $_POST['voucher_date'];
    $narration    = trim($_POST['narration']);
    $created_by   = $_SESSION['user_id'];

    $accounts = $_POST['account_id'];
    $debits   = $_POST['debit'];
    $credits  = $_POST['credit'];

    $total_debit = 0;
    $total_credit = 0;

    foreach($debits as $d) $total_debit += (float)$d;
    foreach($credits as $c) $total_credit += (float)$c;

    if($total_debit <= 0 || $total_credit <= 0)
    {
        $error = "Debit and Credit cannot be empty.";
    }
    elseif($total_debit != $total_credit)
    {
        $error = "Debit and Credit must be equal.";
    }
    else
    {
        mysqli_begin_transaction($conn);

        try
        {
            /* =========================
               Insert Voucher Header
            ========================= */

            $stmt = mysqli_prepare($conn,"
                INSERT INTO vouchers
                (voucher_no,voucher_date,voucher_type,narration,created_by,status)
                VALUES (?,?,?,?,?,?)
            ");

            $type = "Journal";
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

            /* =========================
               Insert Details (Multiple Rows)
            ========================= */

            for($i=0;$i<count($accounts);$i++)
            {
                $acc = (int)$accounts[$i];
                $debit = (float)$debits[$i];
                $credit = (float)$credits[$i];

                if($acc > 0)
                {
                    $stmt2 = mysqli_prepare($conn,"
                        INSERT INTO voucher_details
                        (voucher_id,account_id,debit,credit)
                        VALUES (?,?,?,?)
                    ");

                    mysqli_stmt_bind_param(
                        $stmt2,
                        "iidd",
                        $voucher_id,
                        $acc,
                        $debit,
                        $credit
                    );

                    mysqli_stmt_execute($stmt2);
                }
            }

            mysqli_commit($conn);
            logActivity(
    $conn,
    "Journal Voucher",
    "Create",
    $voucher_id,
    "Created Journal Voucher : ".$voucher_no
);

            header("Location: journal_voucher.php?saved=1");
            exit();

        }
        catch(Exception $e)
        {
            mysqli_rollback($conn);
            $error = "Failed to save journal voucher.";
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
     JOURNAL FORM
========================= -->

<div class="col-lg-5">

<div class="card shadow">

<div class="card-header bg-dark text-white">

<h5 class="mb-0">

<i class="fa-solid fa-file-invoice"></i>

Journal Voucher

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

<div class="mb-3">

<label>Narration</label>

<textarea name="narration"
rows="3"
class="form-control"></textarea>

</div>

<!-- =========================
     JOURNAL TABLE
========================= -->

<table class="table table-bordered" id="journalTable">

<thead class="table-dark">

<tr>

<th>Account</th>
<th>Debit</th>
<th>Credit</th>
<th width="50">+</th>

</tr>

</thead>

<tbody>

<tr>

<td>

<select name="account_id[]"
class="form-select"
required>

<option value="">Select</option>

<?php
$acc=mysqli_query($conn,"
SELECT id,account_name
FROM accounts
WHERE status='Active'
ORDER BY account_name
");

while($a=mysqli_fetch_assoc($acc)){
?>

<option value="<?= $a['id'] ?>">
<?= htmlspecialchars($a['account_name']) ?>
</option>

<?php } ?>

</select>

</td>

<td>

<input type="number"
step="0.01"
name="debit[]"
class="form-control debit"
value="0">

</td>

<td>

<input type="number"
step="0.01"
name="credit[]"
class="form-control credit"
value="0">

</td>

<td>

<button type="button"
class="btn btn-success btn-sm"
onclick="addRow()">

+

</button>

</td>

</tr>

</tbody>

</table>

<!-- =========================
     TOTALS
========================= -->

<div class="row">

<div class="col-md-6">

<strong>Total Debit:</strong>
<span id="totalDebit">0.00</span>

</div>

<div class="col-md-6 text-end">

<strong>Total Credit:</strong>
<span id="totalCredit">0.00</span>

</div>

</div>

<br>

<button type="submit"
name="save"
class="btn btn-dark w-100">

Save Journal Voucher

</button>

</form>

</div>

</div>

</div>

<!-- =========================
     LIST
========================= -->

<div class="col-lg-7">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h5>Journal Voucher List</h5>

</div>

<div class="card-body">

<table class="table table-bordered table-hover datatable">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Voucher No</th>
<th>Date</th>
<th>Narration</th>
<th class="text-end">Amount</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php

$sql="
SELECT *
FROM vouchers
WHERE voucher_type='Journal'
ORDER BY id DESC
";

$res=mysqli_query($conn,$sql);

while($row=mysqli_fetch_assoc($res)){
?>

<tr>

<td><?= $row['id'] ?></td>

<td><b><?= $row['voucher_no'] ?></b></td>

<td><?= date('d-M-Y',strtotime($row['voucher_date'])) ?></td>

<td><?= $row['narration'] ?></td>

<td class="text-end">
-
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

<a href="view_voucher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info" title="View">
    <i class="fa-solid fa-eye"></i>
</a>
<a href="edit_journal_voucher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
    <i class="fa-solid fa-pen"></i>
</a>

<a href="delete_journal_voucher.php?id=<?= $row['id'] ?>"
   class="btn btn-sm btn-danger"
   onclick="return confirm('Delete this voucher?');">
    <i class="fa-solid fa-trash"></i>
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
</a>

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

<!-- =========================
     JAVASCRIPT
========================= -->

<script>

function addRow()
{
    let table = document.querySelector("#journalTable tbody");

    let row = table.rows[0].cloneNode(true);

    row.querySelectorAll("input").forEach(i=>i.value=0);
    row.querySelector("select").selectedIndex=0;

    table.appendChild(row);
    bindEvents();
}

function bindEvents()
{
    document.querySelectorAll(".debit, .credit").forEach(input=>{
        input.oninput = calculateTotal;
    });
}

function calculateTotal()
{
    let d=0,c=0;

    document.querySelectorAll(".debit").forEach(i=>{
        d += parseFloat(i.value)||0;
    });

    document.querySelectorAll(".credit").forEach(i=>{
        c += parseFloat(i.value)||0;
    });

    document.getElementById("totalDebit").innerText=d.toFixed(2);
    document.getElementById("totalCredit").innerText=c.toFixed(2);
}

bindEvents();

</script>

<?php include 'footer.php'; ?>