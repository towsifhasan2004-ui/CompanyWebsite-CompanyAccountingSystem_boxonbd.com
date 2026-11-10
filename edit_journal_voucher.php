<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';
require_once 'config/audit.php';
require_once 'config/notification.php';

checkRole(['accountant']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = "";
$success = "";

/* ==========================================
   GET VOUCHER ID
========================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: journal_voucher.php");
    exit();
}

$id = (int)$_GET['id'];

/* ==========================================
   LOAD JOURNAL VOUCHER
========================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM vouchers
     WHERE id=?
     AND voucher_type='Journal'
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$voucher = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if(!$voucher)
{
    die("Journal Voucher not found.");
}

/* ==========================================
   STATUS CHECK
========================================== */

if(
    $voucher['status']=="Checked" ||
    $voucher['status']=="Approved"
)
{
    die("Approved or Checked vouchers cannot be edited.");
}

/* ==========================================
   UPDATE JOURNAL VOUCHER
========================================== */

if(isset($_POST['update']))
{

    $voucher_date = $_POST['voucher_date'];

    $narration = trim($_POST['narration']);

    $accounts = $_POST['account_id'];

    $debits = $_POST['debit'];

    $credits = $_POST['credit'];

    $totalDebit = 0;

    $totalCredit = 0;

    /* ==========================
       CALCULATE TOTAL
    ========================== */

    foreach($debits as $d)
    {
        $totalDebit += (float)$d;
    }

    foreach($credits as $c)
    {
        $totalCredit += (float)$c;
    }

    /* ==========================
       VALIDATION
    ========================== */

    if($totalDebit<=0 || $totalCredit<=0)
    {
        $error = "Debit and Credit cannot be zero.";
    }
    elseif(round($totalDebit,2)!=round($totalCredit,2))
    {
        $error = "Debit and Credit must be equal.";
    }
    else
    {

        /* Validate each row */

        foreach($accounts as $i=>$account)
        {

            $account = (int)$account;

            $debit = (float)$debits[$i];

            $credit = (float)$credits[$i];

            if($account==0)
            {
                $error="Please select an account in every row.";
                break;
            }

            if($debit>0 && $credit>0)
            {
                $error="A row cannot contain both Debit and Credit.";
                break;
            }

            if($debit==0 && $credit==0)
            {
                $error="Each row must contain either Debit or Credit.";
                break;
            }

        }

    }

    if($error=="")
    {

        mysqli_begin_transaction($conn);

        try
        {

            /* ==========================
               UPDATE HEADER
            ========================== */

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE vouchers
                SET
                    voucher_date=?,
                    narration=?,
                    status='Pending',
                    remarks=NULL,
                    checked_by=NULL,
                    checked_at=NULL,
                    approved_by=NULL,
                    approved_at=NULL
                WHERE id=?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssi",
                $voucher_date,
                $narration,
                $id
            );

            mysqli_stmt_execute($stmt);

            /* ==========================
               DELETE OLD DETAILS
            ========================== */

            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM voucher_details
                 WHERE voucher_id=?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $id
            );

            mysqli_stmt_execute($stmt);

            /* ==========================
               INSERT NEW DETAILS
            ========================== */

            for($i=0;$i<count($accounts);$i++)
            {

                $account = (int)$accounts[$i];

                $debit = (float)$debits[$i];

                $credit = (float)$credits[$i];

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO voucher_details
                    (
                        voucher_id,
                        account_id,
                        debit,
                        credit
                    )
                    VALUES
                    (?,?,?,?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iidd",
                    $id,
                    $account,
                    $debit,
                    $credit
                );

                mysqli_stmt_execute($stmt);

            }            /* ==========================
               AUDIT LOG
            ========================== */

            logActivity(
                $conn,
                "Journal Voucher",
                "Update",
                $id,
                "Updated Journal Voucher : ".$voucher['voucher_no']
            );

            /* ==========================
               SEND NOTIFICATION TO MANAGERS
            ========================== */

            $managerQuery = mysqli_query(
                $conn,
                "SELECT id
                 FROM users
                 WHERE role='manager'
                 AND status='active'"
            );

            while($manager = mysqli_fetch_assoc($managerQuery))
            {
                addNotification(
                    $conn,
                    $manager['id'],
                    "Journal Voucher Updated",
                    "Journal Voucher ".$voucher['voucher_no']." has been updated and is waiting for review.",
                    "warning",
                    "view_voucher.php?id=".$id
                );
            }

            /* ==========================
               COMMIT TRANSACTION
            ========================== */

            mysqli_commit($conn);

            header("Location: journal_voucher.php?updated=1");
            exit();

        }
        catch(Exception $e)
        {
            mysqli_rollback($conn);

            $error = "Unable to update Journal Voucher.<br>".$e->getMessage();
        }

    }

}

/* ==========================================
   LOAD JOURNAL DETAILS
========================================== */

$journalRows = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        account_id,
        debit,
        credit
     FROM voucher_details
     WHERE voucher_id=?
     ORDER BY id"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while($row = mysqli_fetch_assoc($result))
{
    $journalRows[] = $row;
}

/* ==========================================
   LOAD ACCOUNT LIST (ONLY ONCE)
========================================== */

$accountList = [];

$result = mysqli_query(
    $conn,
    "SELECT
        id,
        account_name
     FROM accounts
     WHERE status='Active'
     ORDER BY account_name"
);

while($row = mysqli_fetch_assoc($result))
{
    $accountList[] = $row;
}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-10">

<div class="card shadow">

<div class="card-header bg-warning text-dark">

<h4 class="mb-0">

<i class="fa-solid fa-pen-to-square"></i>

Edit Journal Voucher

</h4>

</div>

<div class="card-body">

<?php if(isset($_GET['updated'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

<i class="fa-solid fa-circle-check"></i>

Journal Voucher updated successfully.

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="alert alert-danger alert-dismissible fade show">

<?= $error ?>

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<form method="POST"><div class="row mb-3">

<div class="col-md-4">

<label class="form-label">

Voucher No

</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($voucher['voucher_no']) ?>"
readonly>

</div>

<div class="col-md-4">

<label class="form-label">

Voucher Date

</label>

<input
type="date"
name="voucher_date"
class="form-control"
value="<?= $voucher['voucher_date'] ?>"
required>

</div>

<div class="col-md-4">

<label class="form-label">

Status

</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($voucher['status']) ?>"
readonly>

</div>

</div>

<div class="mb-3">

<label class="form-label">

Narration

</label>

<textarea
name="narration"
rows="3"
class="form-control"><?= htmlspecialchars($voucher['narration']) ?></textarea>

</div>

<?php if($voucher['status']=="Rejected" && !empty($voucher['remarks'])){ ?>

<div class="alert alert-danger">

<strong>

Manager Rejection Reason

</strong>

<hr>

<?= nl2br(htmlspecialchars($voucher['remarks'])) ?>

</div>

<?php } ?>

<table class="table table-bordered align-middle" id="journalTable">

<thead class="table-dark">

<tr>

<th width="45%">Account</th>

<th width="20%">Debit</th>

<th width="20%">Credit</th>

<th width="15%" class="text-center">

Action

</th>

</tr>

</thead>

<tbody>

<?php foreach($journalRows as $row){ ?>

<tr>

<td>

<select
name="account_id[]"
class="form-select account"
required>

<option value="">Select Account</option>

<?php foreach($accountList as $acc){ ?>

<option
value="<?= $acc['id'] ?>"
<?= ($acc['id']==$row['account_id'])?'selected':'' ?>>

<?= htmlspecialchars($acc['account_name']) ?>

</option>

<?php } ?>

</select>

</td>

<td>

<input
type="number"
step="0.01"
min="0"
name="debit[]"
class="form-control debit"
value="<?= $row['debit'] ?>">

</td>

<td>

<input
type="number"
step="0.01"
min="0"
name="credit[]"
class="form-control credit"
value="<?= $row['credit'] ?>">

</td>

<td class="text-center">

<button
type="button"
class="btn btn-danger btn-sm removeRow">

<i class="fa-solid fa-trash"></i>

</button>

</td>

</tr>

<?php } ?>

</tbody>

<tfoot>

<tr class="table-light">

<th class="text-end">

Totals

</th>

<th>

<span id="totalDebit">0.00</span>

</th>

<th>

<span id="totalCredit">0.00</span>

</th>

<th class="text-center">

<button
type="button"
id="addRow"
class="btn btn-success btn-sm">

<i class="fa-solid fa-plus"></i>

Add Row

</button>

</th>

</tr>

</tfoot>

</table>

<hr>

<div class="d-flex justify-content-between">

<a
href="journal_voucher.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

<button
type="submit"
name="update"
class="btn btn-primary">

<i class="fa-solid fa-floppy-disk"></i>

Update Journal Voucher

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div><?php include 'footer.php'; ?>

<script>

function calculateTotal()
{
    let totalDebit = 0;
    let totalCredit = 0;

    document.querySelectorAll(".debit").forEach(function(input){

        totalDebit += parseFloat(input.value) || 0;

    });

    document.querySelectorAll(".credit").forEach(function(input){

        totalCredit += parseFloat(input.value) || 0;

    });

    document.getElementById("totalDebit").innerHTML = totalDebit.toFixed(2);
    document.getElementById("totalCredit").innerHTML = totalCredit.toFixed(2);
}

/* ==========================
   BIND EVENTS
========================== */

function bindEvents()
{

    /* Debit / Credit Change */

    document.querySelectorAll(".debit,.credit").forEach(function(input){

        input.removeEventListener("input", calculateTotal);
        input.addEventListener("input", calculateTotal);

    });

    /* Remove Row */

    document.querySelectorAll(".removeRow").forEach(function(btn){

        btn.onclick = function(){

            let tbody = document.querySelector("#journalTable tbody");

            if(tbody.rows.length<=1)
            {
                alert("At least one row is required.");
                return;
            }

            this.closest("tr").remove();

            calculateTotal();

        };

    });

}

/* ==========================
   ADD NEW ROW
========================== */

document.getElementById("addRow").onclick = function(){

    let tbody = document.querySelector("#journalTable tbody");

    let newRow = tbody.rows[0].cloneNode(true);

    /* Reset Controls */

    newRow.querySelector("select").selectedIndex = 0;

    newRow.querySelectorAll("input").forEach(function(input){

        input.value = "0.00";

    });

    tbody.appendChild(newRow);

    bindEvents();

    calculateTotal();

};

/* ==========================
   FORM VALIDATION
========================== */

document.querySelector("form").addEventListener("submit", function(e){

    let debit = parseFloat(document.getElementById("totalDebit").innerHTML);
    let credit = parseFloat(document.getElementById("totalCredit").innerHTML);

    if(debit<=0 || credit<=0)
    {
        alert("Debit and Credit cannot be zero.");
        e.preventDefault();
        return;
    }

    if(debit.toFixed(2)!=credit.toFixed(2))
    {
        alert("Total Debit and Total Credit must be equal.");
        e.preventDefault();
        return;
    }

    let valid = true;

    document.querySelectorAll("#journalTable tbody tr").forEach(function(row){

        let account = row.querySelector("select").value;

        let d = parseFloat(row.querySelector(".debit").value) || 0;

        let c = parseFloat(row.querySelector(".credit").value) || 0;

        if(account=="")
        {
            alert("Please select an account in every row.");
            valid = false;
            return;
        }

        if(d>0 && c>0)
        {
            alert("One row cannot contain both Debit and Credit.");
            valid = false;
            return;
        }

        if(d==0 && c==0)
        {
            alert("Every row must contain either Debit or Credit.");
            valid = false;
            return;
        }

    });

    if(!valid)
    {
        e.preventDefault();
    }

});

/* ==========================
   INITIALIZE
========================== */

bindEvents();

calculateTotal();

</script>