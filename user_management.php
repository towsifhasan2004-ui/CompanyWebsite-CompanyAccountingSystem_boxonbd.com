<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once __DIR__ . '/config/role.php';

checkRole(['admin']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

/* =========================
   CREATE USER
========================= */

if(isset($_POST['save']))
{
    $full_name = $_POST['full_name'];
    $username  = $_POST['username'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role      = $_POST['role'];
    $status    = $_POST['status'];

    mysqli_query($conn,"
        INSERT INTO users(full_name,username,password,role,status)
        VALUES('$full_name','$username','$password','$role','$status')
    ");
}

/* =========================
   DELETE USER
========================= */

if(isset($_GET['delete']))
{
    $id = (int)$_GET['delete'];
    mysqli_query($conn,"DELETE FROM users WHERE id=$id");
    header("Location: user_management.php");
    exit();
}

/* =========================
   USERS LIST
========================= */

$users = mysqli_query($conn,"SELECT * FROM users ORDER BY id DESC");
?>

<?php if(isset($_GET['updated'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

User updated successfully.

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if(isset($_GET['deleted'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

User deleted successfully.

<button type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if(isset($_GET['error']) && $_GET['error']=='self_delete'){ ?>

<div class="alert alert-danger alert-dismissible fade show">

You cannot delete your own account.

<button type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if(isset($_GET['error']) && $_GET['error']=='notfound'){ ?>

<div class="alert alert-warning alert-dismissible fade show">

User not found.

<button type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if(isset($_GET['error']) && $_GET['error']=='delete'){ ?>

<div class="alert alert-danger alert-dismissible fade show">

Unable to delete user.

<button type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<div class="container-fluid">

<div class="row">

<!-- =========================
     USER FORM
========================= -->

<div class="col-md-4">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h5>Create User</h5>

</div>

<div class="card-body">

<form method="POST">

<div class="mb-2">
<label>Full Name</label>
<input type="text" name="full_name" class="form-control" required>
</div>

<div class="mb-2">
<label>Username</label>
<input type="text" name="username" class="form-control" required>
</div>

<div class="mb-2">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="mb-2">
<label>Role</label>
<select name="role" class="form-control">
    <option value="admin">Admin</option>
    <option value="manager">Manager</option>
    <option value="accountant">Accountant</option>
</select>
</div>

<div class="mb-2">
<label>Status</label>
<select name="status" class="form-control">
    <option value="active">Active</option>
    <option value="inactive">Inactive</option>
</select>
</div>

<button name="save" class="btn btn-success w-100">
Save User
</button>

</form>

</div>

</div>

</div>

<!-- =========================
     USER LIST
========================= -->

<div class="col-md-8">

<div class="card shadow">

<div class="card-header bg-dark text-white">

<h5>User List</h5>

</div>

<div class="card-body">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>
<th>ID</th>
<th>Name</th>
<th>Username</th>
<th>Role</th>
<th>Status</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php while($u = mysqli_fetch_assoc($users)){ ?>

<tr>

<td><?= $u['id'] ?></td>
<td><?= htmlspecialchars($u['full_name']) ?></td>
<td><?= htmlspecialchars($u['username']) ?></td>
<td>
<span class="badge bg-info">
<?= $u['role'] ?>
</span>
</td>

<td>
<span class="badge bg-<?= $u['status']=='active'?'success':'danger' ?>">
<?= $u['status'] ?>
</span>
</td>

<td>

<a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-warning btn-sm">
    <i class="fa-solid fa-pen"></i>
</a>

<a href="delete.php?id=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
    onclick="return confirm('Are you sure you want to delete this user?')">
    <i class="fa-solid fa-trash"></i>
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

<?php include 'footer.php'; ?>