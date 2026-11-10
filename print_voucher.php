<?php

require_once 'auth.php';
require_once 'config/db.php';

if(!isset($_GET['id']))
{
    die("Voucher ID Missing.");
}

$id=(int)$_GET['id'];

/* =====================================
   COMPANY INFORMATION
===================================== */

$company=mysqli_fetch_assoc(

mysqli_query(

$conn,

"SELECT *
FROM company
LIMIT 1"

)

);

/* =====================================
   VOUCHER HEADER
===================================== */

$stmt=mysqli_prepare(

$conn,

"SELECT

v.*,

u1.full_name AS created_by_name,

u2.full_name AS checked_by_name,

u3.full_name AS approved_by_name

FROM vouchers v

LEFT JOIN users u1
ON u1.id=v.created_by

LEFT JOIN users u2
ON u2.id=v.checked_by

LEFT JOIN users u3
ON u3.id=v.approved_by

WHERE v.id=?"

);

mysqli_stmt_bind_param(

$stmt,

"i",

$id

);

mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0)
{
    die("Voucher Not Found.");
}

$voucher=mysqli_fetch_assoc($result);

/* =====================================
   VOUCHER DETAILS
===================================== */

$details=mysqli_query(

$conn,

"

SELECT

vd.*,

a.account_code,

a.account_name

FROM voucher_details vd

INNER JOIN accounts a

ON a.id=vd.account_id

WHERE vd.voucher_id=".$id."

ORDER BY vd.id ASC

"

);

$totalDebit=0;

$totalCredit=0;

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="utf-8">

<title>

Print Voucher

</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{

background:white;

font-size:14px;

}

.company-logo{

height:80px;

}

.company-name{

font-size:28px;

font-weight:bold;

}

.voucher-title{

font-size:22px;

font-weight:bold;

text-align:center;

margin-top:10px;

margin-bottom:20px;

}

.table td,
.table th{

padding:8px;

}

.signature{

margin-top:80px;

text-align:center;

}

.signature hr{

border:1px solid #000;

margin-bottom:5px;

}

@media print{

.no-print{

display:none;

}

}

</style>

</head>

<body>

<div class="container mt-4">

<div class="text-center">

<?php

$logoPath = "assets/uploads/" . $company['logo'];

if(
    !empty($company['logo']) &&
    file_exists($logoPath)
){
?>

<img
src="<?= $logoPath ?>"
class="company-logo"
style="height:80px; max-width:120px;">

<?php
}
else
{
    echo "<p style='color:red;'>Logo not found.</p>";
}
?>

<div class="company-name">

<?= htmlspecialchars($company['company_name']) ?>

</div>

<div>

<?= nl2br(htmlspecialchars($company['address'])) ?>

</div>

<div>

Phone :
<?= htmlspecialchars($company['phone']) ?>

|

Email :
<?= htmlspecialchars($company['email']) ?>

</div>

<div>

Website :
<?= htmlspecialchars($company['website']) ?>

</div>

</div>

<hr>

<div class="voucher-title">

<?= strtoupper($voucher['voucher_type']) ?>

VOUCHER

</div><!-- =====================================
     VOUCHER INFORMATION
===================================== -->

<table class="table table-bordered mb-4">

<tr>

<th width="20%">Voucher No</th>

<td width="30%">

<?= htmlspecialchars($voucher['voucher_no']) ?>

</td>

<th width="20%">Voucher Date</th>

<td width="30%">

<?= date('d-M-Y',strtotime($voucher['voucher_date'])) ?>

</td>

</tr>

<tr>

<th>Voucher Type</th>

<td>

<?= htmlspecialchars($voucher['voucher_type']) ?>

</td>

<th>Status</th>

<td>

<?php

switch($voucher['status'])
{

case "Pending":

echo '<span class="badge bg-warning text-dark">Pending</span>';

break;

case "Checked":

echo '<span class="badge bg-info">Checked</span>';

break;

case "Approved":

echo '<span class="badge bg-success">Approved</span>';

break;

case "Rejected":

echo '<span class="badge bg-danger">Rejected</span>';

break;

default:

echo htmlspecialchars($voucher['status']);

}

?>

</td>

</tr>

<tr>

<th>Prepared By</th>

<td>

<?= htmlspecialchars($voucher['created_by_name']) ?>

</td>

<th>Prepared Date</th>

<td>

<?= date('d-M-Y h:i A',strtotime($voucher['created_at'])) ?>

</td>

</tr>

<tr>

<th>Checked By</th>

<td>

<?= $voucher['checked_by_name'] ? htmlspecialchars($voucher['checked_by_name']) : "-" ?>

</td>

<th>Checked Date</th>

<td>

<?php

if(!empty($voucher['checked_at']))
{

echo date('d-M-Y h:i A',strtotime($voucher['checked_at']));

}
else
{

echo "-";

}

?>

</td>

</tr>

<tr>

<th>Approved By</th>

<td>

<?= $voucher['approved_by_name'] ? htmlspecialchars($voucher['approved_by_name']) : "-" ?>

</td>

<th>Approved Date</th>

<td>

<?php

if(!empty($voucher['approved_at']))
{

echo date('d-M-Y h:i A',strtotime($voucher['approved_at']));

}
else
{

echo "-";

}

?>

</td>

</tr>

<?php if(!empty($voucher['remarks'])){ ?>

<tr>

<th>Remarks</th>

<td colspan="3">

<?= nl2br(htmlspecialchars($voucher['remarks'])) ?>

</td>

</tr>

<?php } ?>

</table>

<!-- =====================================
     ACCOUNT DETAILS
===================================== -->

<table class="table table-bordered">

<thead class="table-dark">

<tr>

<th width="8%">

SL

</th>

<th width="18%">

Account Code

</th>

<th>

Account Name

</th>

<th width="18%" class="text-end">

Debit (<?= htmlspecialchars($company['currency']) ?>)

</th>

<th width="18%" class="text-end">

Credit (<?= htmlspecialchars($company['currency']) ?>)

</th>

</tr>

</thead>

<tbody>

<?php

$sl=1;

while($row=mysqli_fetch_assoc($details))
{

$totalDebit += $row['debit'];

$totalCredit += $row['credit'];

?><tr>

<td>

<?= $sl++ ?>

</td>

<td>

<?= htmlspecialchars($row['account_code']) ?>

</td>

<td>

<?= htmlspecialchars($row['account_name']) ?>

</td>

<td class="text-end">

<?php

if($row['debit']>0)
{
    echo number_format($row['debit'],2);
}
else
{
    echo "-";
}

?>

</td>

<td class="text-end">

<?php

if($row['credit']>0)
{
    echo number_format($row['credit'],2);
}
else
{
    echo "-";
}

?>

</td>

</tr>

<?php

}

?>

</tbody>

<tfoot>

<tr class="table-secondary">

<th colspan="3" class="text-end">

TOTAL

</th>

<th class="text-end">

<?= number_format($totalDebit,2) ?>

</th>

<th class="text-end">

<?= number_format($totalCredit,2) ?>

</th>

</tr>

</tfoot>

</table>

<!-- =====================================
     NARRATION
===================================== -->

<div class="card mt-3">

<div class="card-header bg-light">

<strong>

Narration

</strong>

</div>

<div class="card-body">

<?php

if(!empty($voucher['narration']))
{
    echo nl2br(htmlspecialchars($voucher['narration']));
}
else
{
    echo "<em>No narration provided.</em>";
}

?>

</div>

</div><!-- =====================================
     SIGNATURE SECTION
===================================== -->

<div class="row mt-5">

    <div class="col-4 text-center">

        <br><br><br>

        <hr>

        <strong>Prepared By</strong><br>

        <?= htmlspecialchars($voucher['created_by_name']) ?>

    </div>

    <div class="col-4 text-center">

        <br><br><br>

        <hr>

        <strong>Checked By</strong><br>

        <?php
        if(!empty($voucher['checked_by_name']))
        {
            echo htmlspecialchars($voucher['checked_by_name']);
        }
        else
        {
            echo "________________";
        }
        ?>

    </div>

    <div class="col-4 text-center">

        <br><br><br>

        <hr>

        <strong>Approved By</strong><br>

        <?php
        if(!empty($voucher['approved_by_name']))
        {
            echo htmlspecialchars($voucher['approved_by_name']);
        }
        else
        {
            echo "________________";
        }
        ?>

    </div>

</div>

<!-- =====================================
     FOOTER
===================================== -->

<hr class="mt-5">

<div class="row">

    <div class="col-md-6">

        <small>

            Printed By :
            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>

        </small>

    </div>

    <div class="col-md-6 text-end">

        <small>

            Print Time :
            <?= date("d-M-Y h:i:s A") ?>

        </small>

    </div>

</div>

<div class="text-center mt-3">

    <small>

        <?= htmlspecialchars($company['company_name']) ?>

        <?php if(!empty($company['website'])){ ?>

            |
            <?= htmlspecialchars($company['website']) ?>

        <?php } ?>

    </small>

</div>

</div>

<!-- End Container --><!-- =====================================
     PRINT BUTTON
===================================== -->

<div class="text-center mt-4 no-print">

<a href="javascript:window.print();"
class="btn btn-primary">

<i class="fa-solid fa-print"></i>

Print Voucher

</a>

<a href="javascript:history.back();"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

</div>

<!-- =====================================
     PRINT STYLE
===================================== -->

<style>

@media print{

body{

background:#fff;

margin:0;

padding:0;

font-size:13px;

}

.no-print{

display:none !important;

}

.container{

width:100%;

max-width:100%;

margin:0;

padding:15px;

}

.table{

width:100% !important;

border-collapse:collapse;

}

.table th,
.table td{

border:1px solid #000 !important;

padding:6px !important;

}

.card{

border:none !important;

box-shadow:none !important;

}

.card-header{

background:#fff !important;

color:#000 !important;

border:none !important;

font-weight:bold;

padding-left:0;

}

.badge{

border:1px solid #000;

color:#000 !important;

background:#fff !important;

}

hr{

border:1px solid #000;

}

img{

max-height:80px;

}

}

</style>

<script>

window.onload=function(){

window.print();

/* Uncomment if you want to close automatically after printing */

// window.onafterprint=function(){
//     window.close();
// };

};

</script>

</body>

</html>