<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin']);

if (!isset($_GET['id'])) {
    header("Location: audit_logs.php");
    exit();
}

$id = (int)$_GET['id'];

$sql = "
SELECT
a.*,
u.full_name,
u.username

FROM audit_logs a

LEFT JOIN users u
ON u.id = a.user_id

WHERE a.id=?
LIMIT 1
";

$stmt = mysqli_prepare($conn,$sql);
mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$audit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if(!$audit){
    die("Audit record not found.");
}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4>

<i class="fa-solid fa-eye"></i>

Audit Details

</h4>

</div>

<div class="card-body">

<table class="table table-bordered">

<tr>
<th width="220">Audit ID</th>
<td><?= $audit['id']; ?></td>
</tr>

<tr>
<th>User</th>
<td><?= htmlspecialchars($audit['full_name']); ?></td>
</tr>

<tr>
<th>Username</th>
<td><?= htmlspecialchars($audit['username']); ?></td>
</tr>

<tr>
<th>Module</th>
<td><?= htmlspecialchars($audit['module']); ?></td>
</tr>

<tr>
<th>Action</th>
<td><?= htmlspecialchars($audit['action']); ?></td>
</tr>

<tr>
<th>Record ID</th>
<td><?= $audit['record_id']; ?></td>
</tr>

<tr>
<th>Description</th>
<td><?= nl2br(htmlspecialchars($audit['description'])); ?></td>
</tr>

<tr>
<th>IP Address</th>
<td><?= htmlspecialchars($audit['ip_address']); ?></td>
</tr>

<tr>
<th>Date & Time</th>
<td><?= date("d-M-Y h:i:s A", strtotime($audit['created_at'])); ?></td>
</tr>

</table>

<a href="audit_logs.php" class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

</div>

</div>

</div>

<?php include 'footer.php'; ?>