<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin']);

$error = "";
$success = "";

/*=========================
GET USER ID
=========================*/

if(!isset($_GET['id']))
{
    header("Location: user_management.php");
    exit();
}

$id = (int)$_GET['id'];

/*=========================
LOAD USER
=========================*/

$stmt = mysqli_prepare($conn,"
SELECT *
FROM users
WHERE id=?
LIMIT 1");

mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if(!$user)
{
    die("User not found.");
}

/*=========================
UPDATE USER
=========================*/

if(isset($_POST['update']))
{

    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $role      = $_POST['role'];
    $status    = $_POST['status'];
    $password  = trim($_POST['password']);

    if(
        $full_name=="" ||
        $username==""
    )
    {
        $error="Please fill all required fields.";
    }
    else
    {

        /* Check Duplicate Username */

        $stmt=mysqli_prepare($conn,"
        SELECT id
        FROM users
        WHERE username=?
        AND id<>?");

        mysqli_stmt_bind_param(
        $stmt,
        "si",
        $username,
        $id);

        mysqli_stmt_execute($stmt);

        mysqli_stmt_store_result($stmt);

        if(mysqli_stmt_num_rows($stmt)>0)
        {
            $error="Username already exists.";
        }
        else
        {

            /*=========================
            UPDATE WITHOUT PASSWORD
            =========================*/

            if($password=="")
            {

                $stmt=mysqli_prepare($conn,"
                UPDATE users
                SET
                full_name=?,
                username=?,
                role=?,
                status=?
                WHERE id=?");

                mysqli_stmt_bind_param(
                $stmt,
                "ssssi",
                $full_name,
                $username,
                $role,
                $status,
                $id);

            }
            else
            {

                /*=========================
                UPDATE WITH PASSWORD
                =========================*/

                $hash=password_hash(
                $password,
                PASSWORD_DEFAULT);

                $stmt=mysqli_prepare($conn,"
                UPDATE users
                SET
                full_name=?,
                username=?,
                password=?,
                role=?,
                status=?
                WHERE id=?");

                mysqli_stmt_bind_param(
                $stmt,
                "sssssi",
                $full_name,
                $username,
                $hash,
                $role,
                $status,
                $id);

            }

            if(mysqli_stmt_execute($stmt))
            {

                header(
                "Location:user_management.php?updated=1");

                exit();

            }
            else
            {

                $error="Unable to update user.";

            }

        }

    }

}
logActivity(
    $conn,
    "User",
    "Update",
    $id,
    "Updated user: ".$username
);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-8">

<div class="card shadow">

<div class="card-header bg-warning text-dark">

<h4>

<i class="fa-solid fa-user-pen"></i>

Edit User

</h4>

</div>

<div class="card-body">
    <?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php } ?>

<form method="POST">

<div class="mb-3">

<label class="form-label">
Full Name
</label>

<input
type="text"
name="full_name"
class="form-control"
value="<?= htmlspecialchars($user['full_name']) ?>"
required>

</div>

<div class="mb-3">

<label class="form-label">
Username
</label>

<input
type="text"
name="username"
class="form-control"
value="<?= htmlspecialchars($user['username']) ?>"
required>

</div>

<div class="mb-3">

<label class="form-label">
New Password
</label>

<input
type="password"
name="password"
class="form-control">

<small class="text-muted">
Leave blank to keep the current password.
</small>

</div>

<div class="mb-3">

<label class="form-label">
Role
</label>

<select
name="role"
class="form-select"
required>

<option value="admin"
<?= ($user['role']=='admin')?'selected':''; ?>>
Admin
</option>

<option value="manager"
<?= ($user['role']=='manager')?'selected':''; ?>>
Manager
</option>

<option value="accountant"
<?= ($user['role']=='accountant')?'selected':''; ?>>
Accountant
</option>

</select>

</div>

<div class="mb-3">

<label class="form-label">
Status
</label>

<select
name="status"
class="form-select"
required>

<option value="active"
<?= ($user['status']=='active')?'selected':''; ?>>
Active
</option>

<option value="inactive"
<?= ($user['status']=='inactive')?'selected':''; ?>>
Inactive
</option>

</select>

</div>

<div class="d-flex gap-2">

<button
type="submit"
name="update"
class="btn btn-primary">

<i class="fa-solid fa-floppy-disk"></i>

Update User

</button>

<a
href="user_management.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>