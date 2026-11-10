<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager','accountant']);

$user_id = $_SESSION['user_id'];

$error = "";
$success = "";

/*=========================================
MARK SINGLE READ
=========================================*/

if(isset($_GET['read']))
{
    $id = (int)$_GET['read'];

    mysqli_query(
        $conn,
        "UPDATE notifications
         SET is_read=1
         WHERE id='$id'
         AND user_id='$user_id'"
    );

    header("Location: notifications.php");
    exit();
}

/*=========================================
MARK ALL READ
=========================================*/

if(isset($_GET['readall']))
{
    mysqli_query(
        $conn,
        "UPDATE notifications
        SET is_read=1
        WHERE user_id='$user_id'"
    );

    header("Location: notifications.php");
    exit();
}

/*=========================================
DELETE
=========================================*/

if(isset($_GET['delete']))
{
    $id=(int)$_GET['delete'];

    mysqli_query(
        $conn,
        "DELETE FROM notifications
        WHERE id='$id'
        AND user_id='$user_id'"
    );

    header("Location: notifications.php");
    exit();
}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

<h4 class="mb-0">

<i class="fa-solid fa-bell"></i>

Notifications

</h4>

<div>

<a
href="?readall=1"
class="btn btn-success btn-sm">

<i class="fa-solid fa-check-double"></i>

Mark All Read

</a>

</div>

</div>

<div class="card-body">

<table class="table table-bordered table-hover datatable">

<thead class="table-dark">

<tr>

<th width="60">#</th>

<th>Title</th>

<th>Message</th>

<th width="120">Type</th>

<th width="170">Date</th>

<th width="220">Action</th>

</tr>

</thead>

<tbody>

<?php

$sql = "
SELECT *
FROM notifications
WHERE user_id=?
ORDER BY created_at DESC
";

$stmt = mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$sl=1;

while($row=mysqli_fetch_assoc($result))
{

$class="";

if($row['is_read']==0)
{
    $class="table-warning";
}

?><tr class="<?= $class ?>">

<td>

<?= $sl++; ?>

</td>

<td>

<strong>

<?= htmlspecialchars($row['title']); ?>

</strong>

<?php if($row['is_read']==0){ ?>

<span class="badge bg-danger ms-2">

New

</span>

<?php } ?>

</td>

<td>

<?= nl2br(htmlspecialchars($row['message'])); ?>

</td>

<td>

<?php

switch($row['type'])
{
    case "success":
        echo '<span class="badge bg-success">Success</span>';
        break;

    case "warning":
        echo '<span class="badge bg-warning text-dark">Warning</span>';
        break;

    case "danger":
        echo '<span class="badge bg-danger">Danger</span>';
        break;

    default:
        echo '<span class="badge bg-info">Info</span>';
        break;
}

?>

</td>

<td>

<?= date("d-M-Y h:i A", strtotime($row['created_at'])); ?>

</td>

<td>

<?php if($row['is_read']==0){ ?>

<a
href="?read=<?= $row['id']; ?>"
class="btn btn-success btn-sm"
title="Mark as Read">

<i class="fa-solid fa-check"></i>

</a>

<?php } ?>

<?php if(!empty($row['link'])){ ?>

<a
href="<?= htmlspecialchars($row['link']); ?>"
class="btn btn-primary btn-sm"
title="Open">

<i class="fa-solid fa-arrow-up-right-from-square"></i>

</a>

<?php } ?>

<a
href="?delete=<?= $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this notification?')"
title="Delete">

<i class="fa-solid fa-trash"></i>

</a>

</td>

</tr>

<?php

}

?></tbody>

</table>

<?php

if(mysqli_num_rows($result)==0)
{
?>

<div class="alert alert-info text-center mt-3">

<i class="fa-solid fa-bell-slash fa-2x mb-2"></i>

<h5>No Notifications Found</h5>

<p class="mb-0">

You don't have any notifications.

</p>

</div>

<?php
}
?>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>