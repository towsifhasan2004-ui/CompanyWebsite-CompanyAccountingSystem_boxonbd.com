<?php
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// Save Account
if (isset($_POST['save'])) {
    $account_code    = trim($_POST['account_code']);
    $account_name    = trim($_POST['account_name']);
    $group_id        = $_POST['group_id'];
    $parent_id       = empty($_POST['parent_id']) ? NULL : $_POST['parent_id'];
    $opening_balance = $_POST['opening_balance'];
    $opening_date    = $_POST['opening_date'];
    $balance_type    = $_POST['balance_type'];
    $description     = trim($_POST['description']);
    $status          = $_POST['status'];

    $sql = "INSERT INTO accounts
    (account_code, account_name, group_id, parent_id,
     opening_balance, opening_date, balance_type,
     description, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssiidssss",
        $account_code,
        $account_name,
        $group_id,
        $parent_id,
        $opening_balance,
        $opening_date,
        $balance_type,
        $description,
        $status
    );
    logActivity(
    $conn,
    "User",
    "Create",
    $new_user_id,
    "Created user: ".$username
    );


    if (mysqli_stmt_execute($stmt)) {
        $message = "<div class='alert alert-success'>Account added successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>" . mysqli_error($conn) . "</div>";
    }
}
?>

<?php include "header.php"; ?>
<?php include "sidebar.php"; ?>

<div class="main">

<?php include "navbar.php"; ?>

<div class="container-fluid mt-4">

<div class="card">

<div class="card-header bg-primary text-white">
<h4>Add New Account</h4>
</div>

<div class="card-body">

<?= $message; ?>

<form method="POST">

<div class="row">

<div class="col-md-4 mb-3">
<label>Account Code</label>
<input type="text" name="account_code" class="form-control" required>
</div>

<div class="col-md-8 mb-3">
<label>Account Name</label>
<input type="text" name="account_name" class="form-control" required>
</div>

<div class="col-md-6 mb-3">
<label>Account Group</label>

<select name="group_id" class="form-select" required>

<option value="">Select Group</option>

<?php
$groups = mysqli_query($conn, "SELECT * FROM account_groups ORDER BY group_name");

while ($g = mysqli_fetch_assoc($groups)) {
?>

<option value="<?= $g['id']; ?>">

<?= $g['group_name']; ?>

</option>

<?php } ?>

</select>

</div>

<div class="col-md-6 mb-3">

<label>Parent Account</label>

<select name="parent_id" class="form-select">

<option value="">None</option>

<?php
$accounts = mysqli_query($conn, "SELECT * FROM accounts ORDER BY account_name");

while ($a = mysqli_fetch_assoc($accounts)) {
?>

<option value="<?= $a['id']; ?>">

<?= $a['account_name']; ?>

</option>

<?php } ?>

</select>

</div>

<div class="col-md-4 mb-3">

<label>Opening Balance</label>

<input type="number"
step="0.01"
name="opening_balance"
class="form-control"
value="0">

</div>

<div class="col-md-4 mb-3">

<label>Opening Date</label>

<input
type="date"
name="opening_date"
class="form-control"
value="<?= date('Y-m-d'); ?>">

</div>

<div class="col-md-4 mb-3">

<label>Balance Type</label>

<select name="balance_type" class="form-select">

<option value="Debit">Debit</option>

<option value="Credit">Credit</option>

</select>

</div>

<div class="col-md-12 mb-3">

<label>Description</label>

<textarea
name="description"
class="form-control"
rows="3"></textarea>

</div>

<div class="col-md-4 mb-3">

<label>Status</label>

<select name="status" class="form-select">

<option value="Active">Active</option>

<option value="Inactive">Inactive</option>

</select>

</div>

</div>

<button class="btn btn-success" name="save">
Save Account
</button>

<a href="index.php" class="btn btn-secondary">
Back
</a>

</form>

</div>

</div>

</div>

</div>

<?php include "footer.php"; ?>