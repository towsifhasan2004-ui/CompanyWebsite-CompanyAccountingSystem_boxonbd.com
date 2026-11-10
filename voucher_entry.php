<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['accountant']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

$error="";
$success="";

/*=========================================
AUTO VOUCHER NUMBER
=========================================*/

$voucherNo="V-000001";

$sql="

SELECT voucher_no

FROM vouchers

ORDER BY id DESC

LIMIT 1

";

$result=mysqli_query($conn,$sql);

if(mysqli_num_rows($result)>0)
{

$row=mysqli_fetch_assoc($result);

$lastNumber=(int)preg_replace('/[^0-9]/','',$row['voucher_no']);

$voucherNo="V-".str_pad(

$lastNumber+1,

6,

"0",

STR_PAD_LEFT

);

}

/*=========================================
LOAD ACTIVE ACCOUNTS
=========================================*/

$accounts=mysqli_query(

$conn,

"

SELECT

id,

account_code,

account_name

FROM accounts

WHERE status='Active'

ORDER BY account_code ASC

"

);

?><div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">

<i class="fa-solid fa-file-invoice-dollar"></i>

ERP Voucher Entry

</h4>

</div>

<div class="card-body">

<?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<?php if($success!=""){ ?>

<div class="alert alert-success">

<?= $success ?>

</div>

<?php } ?>

<form method="POST" id="voucherForm">

<div class="row">

<div class="col-md-3">

<label class="form-label">

Voucher No

</label>

<input
type="text"
class="form-control"
value="<?= $voucherNo ?>"
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
value="<?= date('Y-m-d') ?>"
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

<option value="Receipt">Receipt</option>

<option value="Payment">Payment</option>

<option value="Journal">Journal</option>

<option value="Contra">Contra</option>

</select>

</div>

<div class="col-md-3">

<label class="form-label">

Reference No

</label>

<input
type="text"
name="reference_no"
class="form-control">

</div>

</div>

<div class="row mt-3">

<div class="col-lg-12">

<label class="form-label">

Narration

</label>

<textarea
name="narration"
class="form-control"
rows="2"></textarea>

</div>

</div>

<hr>

<h5 class="mb-3">

Account Allocation

</h5>

<div class="table-responsive">

<table
class="table table-bordered align-middle"
id="allocationTable">

<thead class="table-secondary">

<tr>

<th width="55%">

Account

</th>

<th width="20%">

Amount

</th>

<th width="15%">

DR / CR

</th>

<th width="10%">

Action

</th>

</tr>

</thead>

<tbody>

<tr>

<td>

<select
name="account_id[]"
class="form-select"
required>

<option value="">

Select Account

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

<?php

}

?>

</select>

</td>

<td>

<input

type="number"

step="0.01"

name="amount[]"

class="form-control amount"

value="0.00"

required>

</td>

<td>

<select

name="type[]"

class="form-select drcr"

required>

<option value="DR">

DR

</option>

<option value="CR">

CR

</option>

</select>

</td>

<td class="text-center">

<button

type="button"

class="btn btn-success btn-sm"

onclick="addRow()">

<i class="fa-solid fa-plus"></i>

</button>

</td>

</tr>

</tbody>

</table>

</div><!-- =========================================
TOTAL SECTION
========================================= -->

<div class="row mt-3">

<div class="col-md-4">

<h5>

Total Debit :

<span id="totalDebit" class="text-success">

0.00

</span>

</h5>

</div>

<div class="col-md-4">

<h5>

Total Credit :

<span id="totalCredit" class="text-danger">

0.00

</span>

</h5>

</div>

<div class="col-md-4 text-end">

<h5 id="balanceStatus" class="text-danger">

Not Balanced

</h5>

</div>

</div>

<hr>

<div class="text-end">

<button
type="submit"
name="save"
class="btn btn-primary btn-lg"
id="saveBtn"
disabled>

<i class="fa-solid fa-floppy-disk"></i>

Save Voucher

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<script>

function addRow()
{

let tbody=document.querySelector("#allocationTable tbody");

let row=tbody.rows[0].cloneNode(true);

row.querySelector("select[name='account_id[]']").selectedIndex=0;

row.querySelector("input[name='amount[]']").value="0.00";

row.querySelector("select[name='type[]']").selectedIndex=0;

row.cells[3].innerHTML=

'<button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>';

tbody.appendChild(row);

bindEvents();

}

function removeRow(btn)
{

let tbody=document.querySelector("#allocationTable tbody");

if(tbody.rows.length>1)
{

btn.closest("tr").remove();

calculateTotals();

}

}

function bindEvents()
{

document.querySelectorAll(".amount").forEach(function(item){

item.onkeyup=calculateTotals;

item.onchange=calculateTotals;

});

document.querySelectorAll(".drcr").forEach(function(item){

item.onchange=calculateTotals;

});

}

function calculateTotals()
{

let debit=0;

let credit=0;

document.querySelectorAll("#allocationTable tbody tr").forEach(function(row){

let amount=parseFloat(

row.querySelector("input[name='amount[]']").value

)||0;

let type=row.querySelector("select[name='type[]']").value;

if(type=="DR")
{

debit+=amount;

}
else
{

credit+=amount;

}

});

document.getElementById("totalDebit").innerHTML=

debit.toFixed(2);

document.getElementById("totalCredit").innerHTML=

credit.toFixed(2);

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

</script>/*=========================================================
SAVE ERP VOUCHER
=========================================================*/

if(isset($_POST['save']))
{

    $voucher_date = $_POST['voucher_date'];

    $voucher_type = $_POST['voucher_type'];

    $reference_no = trim($_POST['reference_no']);

    $narration = trim($_POST['narration']);

    $created_by = $_SESSION['user_id'];

    $account_id = $_POST['account_id'];

    $amount = $_POST['amount'];

    $type = $_POST['type'];

    $totalDebit = 0;
    $totalCredit = 0;

    foreach($amount as $k=>$value)
    {

        $value = (float)$value;

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

        try
        {

            /*=========================================
            INSERT VOUCHER HEADER
            =========================================*/

            $status="Pending";

            $stmt=mysqli_prepare(

                $conn,

                "

                INSERT INTO vouchers
                (

                    voucher_no,
                    voucher_date,
                    voucher_type,
                    narration,
                    created_by,
                    status,
                    remarks

                )

                VALUES

                (?,?,?,?,?,?,?)

                "

            );

            mysqli_stmt_bind_param(

                $stmt,

                "ssssiss",

                $voucherNo,

                $voucher_date,

                $voucher_type,

                $narration,

                $created_by,

                $status,

                $reference_no

            );

            mysqli_stmt_execute($stmt);

            $voucher_id=mysqli_insert_id($conn);

            /*=========================================
            INSERT DETAILS
            =========================================*/

            $detail=mysqli_prepare(

                $conn,

                "

                INSERT INTO voucher_details

                (

                    voucher_id,
                    account_id,
                    debit,
                    credit

                )

                VALUES

                (?,?,?,?)

                "

            );

            for($i=0;$i<count($account_id);$i++)
            {

                $acc=(int)$account_id[$i];

                $amt=(float)$amount[$i];

                $debit=0;
                $credit=0;

                if($amt<=0)
                {
                    continue;
                }

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

                    $voucher_id,

                    $acc,

                    $debit,

                    $credit

                );

                mysqli_stmt_execute($detail);

            }

            mysqli_commit($conn);

            $success="Voucher Saved Successfully.";

            header("Location:voucher_entry.php?saved=1");

            exit();

        }
        catch(Exception $e)
        {

            mysqli_rollback($conn);

            $error=$e->getMessage();

        }

    }

}<hr class="mt-5">

<div class="card shadow mt-4">

<div class="card-header bg-dark text-white">

<h4 class="mb-0">

<i class="fa-solid fa-list"></i>

Voucher List

</h4>

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-primary">

<tr>

<th width="6%">ID</th>

<th width="12%">Voucher No</th>

<th width="10%">Date</th>

<th width="10%">Type</th>

<th>Narration</th>

<th width="12%" class="text-end">

Amount

</th>

<th width="10%">

Status

</th>

<th width="18%">

Action

</th>

</tr>

</thead>

<tbody>

<?php

$list=mysqli_query(

$conn,

"

SELECT

v.*

FROM vouchers v

ORDER BY

v.id DESC

"

);

while($voucher=mysqli_fetch_assoc($list))
{

    $voucherTotal=mysqli_fetch_assoc(

        mysqli_query(

            $conn,

            "

            SELECT

            SUM(debit) debit,

            SUM(credit) credit

            FROM voucher_details

            WHERE voucher_id=".$voucher['id']

        )

    );

    $amount=max(

        (float)$voucherTotal['debit'],

        (float)$voucherTotal['credit']

    );

?>

<tr>

<td>

<?= $voucher['id'] ?>

</td>

<td>

<strong>

<?= htmlspecialchars($voucher['voucher_no']) ?>

</strong>

</td>

<td>

<?= date("d-M-Y",strtotime($voucher['voucher_date'])) ?>

</td>

<td>

<span class="badge bg-primary">

<?= htmlspecialchars($voucher['voucher_type']) ?>

</span>

</td>

<td>

<?= htmlspecialchars($voucher['narration']) ?>

</td>

<td class="text-end">

<?= number_format($amount,2) ?>

</td>

<td>

<?php

switch($voucher['status'])
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

<a

href="view_voucher.php?id=<?= $voucher['id'] ?>"

class="btn btn-info btn-sm"

title="View">

<i class="fa-solid fa-eye"></i>

</a>

<a

href="edit_voucher.php?id=<?= $voucher['id'] ?>"

class="btn btn-warning btn-sm"

title="Edit">

<i class="fa-solid fa-pen"></i>

</a>

<a

href="delete_voucher.php?id=<?= $voucher['id'] ?>"

class="btn btn-danger btn-sm"

onclick="return confirm('Delete this voucher?')"

title="Delete">

<i class="fa-solid fa-trash"></i>

</a>

<?php if($voucher['status']=="Approved"){ ?>

<a

href="print_voucher.php?id=<?= $voucher['id'] ?>"

target="_blank"

class="btn btn-secondary btn-sm"

title="Print">

<i class="fa-solid fa-print"></i>

</a>

<?php }else{ ?>

<button

class="btn btn-secondary btn-sm"

disabled>

<i class="fa-solid fa-print"></i>

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