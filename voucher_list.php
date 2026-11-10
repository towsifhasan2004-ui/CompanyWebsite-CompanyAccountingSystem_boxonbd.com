<?php

session_start();

require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','accountant']);


include 'header.php';
include 'sidebar.php';

?>


<div class="container-fluid mt-4">


    <div class="d-flex justify-content-between align-items-center mb-3">


        <h4>

            <i class="fa-solid fa-file-invoice"></i>

            Voucher List

        </h4>



        <a href="create_voucher.php" 
           class="btn btn-success">

            <i class="fa-solid fa-plus"></i>

            Create Voucher

        </a>


    </div>



<div class="card">


<div class="card-body">


<div class="table-responsive">


<table id="voucherTable"
       class="table table-bordered table-striped align-middle">


<thead class="table-dark">


<tr>

<th>#</th>

<th>Voucher No</th>

<th>Type</th>

<th>Date</th>

<th>Description</th>

<th class="text-end">Debit</th>

<th class="text-end">Credit</th>

<th>Status</th>

<th width="160">Action</th>


</tr>


</thead>



<tbody>


<?php


$sql = "

SELECT

v.id,
v.voucher_no,
v.voucher_type,
v.voucher_date,
v.description,
v.status,

SUM(vd.debit) AS total_debit,

SUM(vd.credit) AS total_credit


FROM vouchers v


LEFT JOIN voucher_details vd

ON v.id = vd.voucher_id


GROUP BY v.id


ORDER BY v.id DESC


";


$stmt = $pdo->prepare($sql);

$stmt->execute();


$i = 1;


while($row = $stmt->fetch(PDO::FETCH_ASSOC)){


?>


<tr>


<td>

<?= $i++; ?>

</td>



<td>

<?= htmlspecialchars($row['voucher_no']); ?>

</td>



<td>

<span class="badge bg-primary">

<?= ucfirst($row['voucher_type']); ?>

</span>

</td>



<td>

<?= date('d-m-Y',strtotime($row['voucher_date'])); ?>

</td>



<td>

<?= htmlspecialchars($row['description']); ?>

</td>



<td class="text-end">

<?= number_format($row['total_debit'],2); ?>

</td>



<td class="text-end">

<?= number_format($row['total_credit'],2); ?>

</td>



<td>


<?php


if($row['status']=="approved"){

?>


<span class="badge bg-success">

Approved

</span>


<?php

}elseif($row['status']=="pending"){

?>


<span class="badge bg-warning text-dark">

Pending

</span>


<?php

}else{

?>


<span class="badge bg-danger">

Rejected

</span>


<?php

}


?>


</td>



<td>



<a href="voucher_view.php?id=<?= $row['id']; ?>"

class="btn btn-info btn-sm">

<i class="fa-solid fa-eye"></i>

</a>




<?php if($_SESSION['role']=="admin"){ ?>


<a href="voucher_delete.php?id=<?= $row['id']; ?>"

onclick="return confirm('Delete this voucher?')"

class="btn btn-danger btn-sm">

<i class="fa-solid fa-trash"></i>

</a>


<?php } ?>



</td>



</tr>



<?php

}

?>


</tbody>


</table>


</div>


</div>


</div>


</div>



<script>

$(document).ready(function(){

    $('#voucherTable').DataTable({

        "pageLength":10,

        "order":[[0,"desc"]]

    });

});


</script>



<?php

include 'footer.php';

?>