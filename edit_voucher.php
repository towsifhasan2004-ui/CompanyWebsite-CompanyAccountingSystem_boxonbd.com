<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['accountant']);

if(!isset($_GET['id']))
{
    header("Location:voucher_list.php");
    exit();
}

$id=(int)$_GET['id'];

/*=========================================
LOAD VOUCHER
=========================================*/

$stmt=mysqli_prepare($conn,"
SELECT *
FROM vouchers
WHERE id=?
");

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$voucher=mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($voucher)==0)
{
    die("Voucher not found.");
}

$voucher=mysqli_fetch_assoc($voucher);

/*=========================================
ONLY PENDING VOUCHER CAN BE EDITED
=========================================*/

if($voucher['status']!="Pending")
{
    die("Only Pending Voucher can be edited.");
}

/*=========================================
LOAD DETAILS
=========================================*/

$details=mysqli_query($conn,"
SELECT
vd.*,
a.account_code,
a.account_name
FROM voucher_details vd
INNER JOIN accounts a
ON a.id=vd.account_id
WHERE voucher_id=$id
ORDER BY vd.id ASC
");

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

$error="";
$success="";

?><div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-warning text-dark">

<h4 class="mb-0">

<i class="fa-solid fa-pen"></i>

Edit Voucher

</h4>

</div>

<div class="card-body">

<?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error; ?>

</div>

<?php } ?>

<?php if($success!=""){ ?>

<div class="alert alert-success">

<?= $success; ?>

</div>

<?php } ?>

<form method="POST">

<input
type="hidden"
name="voucher_id"
value="<?= $voucher['id']; ?>">

<div class="row">

<div class="col-md-3">

<label class="form-label">

Voucher No

</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($voucher['voucher_no']); ?>"
readonly>

</div>

<div class="col-md-3">

<label class="form-label">

Voucher Date

</label>

<input
type="date"
name="voucher_date"
class="form-control"
value="<?= $voucher['voucher_date']; ?>"
required>

</div>

<div class="col-md-3">

<label class="form-label">

Voucher Type

</label>

<select
name="voucher_type"
class="form-select"
required>

<option value="Receipt" <?=($voucher['voucher_type']=="Receipt")?'selected':'';?>>
Receipt
</option>

<option value="Payment" <?=($voucher['voucher_type']=="Payment")?'selected':'';?>>
Payment
</option>

<option value="Journal" <?=($voucher['voucher_type']=="Journal")?'selected':'';?>>
Journal
</option>

<option value="Contra" <?=($voucher['voucher_type']=="Contra")?'selected':'';?>>
Contra
</option>

</select>

</div>

<div class="col-md-3">

<label class="form-label">

Reference No

</label>

<input
type="text"
name="reference_no"
class="form-control"
value="<?= htmlspecialchars($voucher['remarks']); ?>">

</div>

</div>

<div class="row mt-3">

<div class="col-lg-12">

<label class="form-label">

Narration

</label>

<textarea
name="narration"
rows="3"
class="form-control"><?= htmlspecialchars($voucher['narration']); ?></textarea>

</div>

</div>

<hr>

<h5>

Account Allocation

</h5>

<div class="table-responsive">

<table class="table table-bordered" id="allocationTable">

<thead class="table-dark">

<tr>

<th width="50%">Account</th>

<th width="20%">Amount</th>

<th width="15%">DR/CR</th>

<th width="15%">Action</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($details)){ ?>

<tr>

<td>

<select
name="account_id[]"
class="form-select"
required>

<option value="">Select Account</option>

<?php

$acc=mysqli_query($conn,"
SELECT id,account_code,account_name
FROM accounts
WHERE status='Active'
ORDER BY account_code
");

while($a=mysqli_fetch_assoc($acc))
{

?>

<option
value="<?= $a['id']; ?>"
<?=($a['id']==$row['account_id'])?'selected':'';?>>

<?= htmlspecialchars($a['account_code']); ?>

-

<?= htmlspecialchars($a['account_name']); ?>

</option>

<?php } ?>

</select>

</td>

<td>

<input
type="number"
step="0.01"
name="amount[]"
class="form-control amount"
value="<?= ($row['debit']>0)?$row['debit']:$row['credit']; ?>">

</td>

<td>

<select
name="type[]"
class="form-select drcr">

<option
value="DR"
<?=($row['debit']>0)?'selected':'';?>>

Debit

</option>

<option
value="CR"
<?=($row['credit']>0)?'selected':'';?>>

Credit

</option>

</select>

</td>

<td class="text-center">

<button
type="button"
class="btn btn-danger btn-sm"
onclick="removeRow(this)">

<i class="fa-solid fa-trash"></i>

</button>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div><hr>

<div class="row mt-3">

<div class="col-md-4">

<div class="card border-success">

<div class="card-body text-center">

<h6 class="text-success">

Total Debit

</h6>

<h4 id="totalDebit">

0.00

</h4>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card border-danger">

<div class="card-body text-center">

<h6 class="text-danger">

Total Credit

</h6>

<h4 id="totalCredit">

0.00

</h4>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card border-primary">

<div class="card-body text-center">

<h6>

Voucher Status

</h6>

<h4 id="balanceStatus" class="text-danger">

✘ Not Balanced

</h4>

</div>

</div>

</div>

</div>

<hr>

<div class="text-end">

<a href="voucher_list.php" class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

<button
type="submit"
name="update"
id="saveBtn"
class="btn btn-primary"
disabled>

<i class="fa-solid fa-floppy-disk"></i>

Update Voucher

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div><?php

/*=========================================================
UPDATE VOUCHER
=========================================================*/

if(isset($_POST['update']))
{

    $voucher_date = $_POST['voucher_date'];
    $voucher_type = $_POST['voucher_type'];
    $reference_no = trim($_POST['reference_no']);
    $narration    = trim($_POST['narration']);

    $account_id = $_POST['account_id'];
    $amount     = $_POST['amount'];
    $type       = $_POST['type'];

    $totalDebit  = 0;
    $totalCredit = 0;

    foreach($amount as $k=>$value)
    {

        $value=(float)$value;

        if($type[$k]=="DR")
        {
            $totalDebit += $value;
        }
        else
        {
            $totalCredit += $value;
        }

    }

    if($totalDebit<=0)
    {
        $error="Debit amount cannot be zero.";
    }
    elseif($totalCredit<=0)
    {
        $error="Credit amount cannot be zero.";
    }
    elseif(round($totalDebit,2)!=round($totalCredit,2))
    {
        $error="Voucher is not balanced.";
    }
    else
    {

        mysqli_begin_transaction($conn);

        try{

            /*=========================================
            UPDATE HEADER
            =========================================*/

            $stmt=mysqli_prepare($conn,"
            UPDATE vouchers
            SET
                voucher_date=?,
                voucher_type=?,
                narration=?,
                remarks=?
            WHERE id=?
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "ssssi",
                $voucher_date,
                $voucher_type,
                $narration,
                $reference_no,
                $id
            );

            mysqli_stmt_execute($stmt);

            /*=========================================
            DELETE OLD DETAILS
            =========================================*/

            mysqli_query($conn,"
            DELETE FROM voucher_details
            WHERE voucher_id=$id
            ");

            /*=========================================
            INSERT NEW DETAILS
            =========================================*/

            $detail=mysqli_prepare($conn,"
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

            for($i=0;$i<count($account_id);$i++)
            {

                $acc=(int)$account_id[$i];

                $amt=(float)$amount[$i];

                if($amt<=0)
                {
                    continue;
                }

                $debit=0;
                $credit=0;

                if($type[$i]=="DR")
                {
                    $debit=$amt;
                }
                else
                {
                    $credit=$amt;
                }

                mysqli_stmt_bind_param(
                    $detail,
                    "iidd",
                    $id,
                    $acc,
                    $debit,
                    $credit
                );

                mysqli_stmt_execute($detail);

            }

            mysqli_commit($conn);

            header("Location:voucher_list.php?updated=1");

            exit();

        }
        catch(Exception $e)
        {

            mysqli_rollback($conn);

            $error=$e->getMessage();

        }

    }

}?>
<script>

function addRow()
{
    let tbody=document.querySelector("#allocationTable tbody");

    let firstRow=tbody.rows[0];

    let row=firstRow.cloneNode(true);

    row.querySelector("select[name='account_id[]']").selectedIndex=0;

    row.querySelector("input[name='amount[]']").value="0.00";

    row.querySelector("select[name='type[]']").selectedIndex=0;

    row.cells[3].innerHTML=
    '<button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>';

    tbody.appendChild(row);

    bindEvents();

    calculateTotals();
}

function removeRow(btn)
{
    let tbody=document.querySelector("#allocationTable tbody");

    if(tbody.rows.length>1)
    {
        btn.closest("tr").remove();
    }

    calculateTotals();
}

function bindEvents()
{
    document.querySelectorAll(".amount").forEach(function(item){

        item.removeEventListener("keyup",calculateTotals);
        item.removeEventListener("change",calculateTotals);

        item.addEventListener("keyup",calculateTotals);
        item.addEventListener("change",calculateTotals);

    });

    document.querySelectorAll(".drcr").forEach(function(item){

        item.removeEventListener("change",calculateTotals);

        item.addEventListener("change",calculateTotals);

    });
}

function calculateTotals()
{

    let debit=0;
    let credit=0;

    document.querySelectorAll("#allocationTable tbody tr").forEach(function(row){

        let amount=parseFloat(
            row.querySelector(".amount").value
        ) || 0;

        let type=row.querySelector(".drcr").value;

        if(type=="DR")
        {
            debit+=amount;
        }
        else
        {
            credit+=amount;
        }

    });

    document.getElementById("totalDebit").innerHTML=debit.toFixed(2);

    document.getElementById("totalCredit").innerHTML=credit.toFixed(2);

    if(debit>0 && debit===credit)
    {
        document.getElementById("balanceStatus").innerHTML="✔ Balanced";
        document.getElementById("balanceStatus").className="text-success";
        document.getElementById("saveBtn").disabled=false;
    }
    else
    {
        document.getElementById("balanceStatus").innerHTML="✘ Not Balanced";
        document.getElementById("balanceStatus").className="text-danger";
        document.getElementById("saveBtn").disabled=true;
    }

}

bindEvents();

calculateTotals();

</script>

<?php include 'footer.php'; ?>