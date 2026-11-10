<?php
require_once 'auth.php';
require_once 'config/db.php';

$success = "";
$error = "";

// Check ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: account_group.php");
    exit();
}

$id = (int)$_GET['id'];

// Load Data
$stmt = mysqli_prepare($conn, "SELECT * FROM account_groups WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$group = mysqli_fetch_assoc($result)) {
    header("Location: account_group.php");
    exit();
}

// Update
if (isset($_POST['update'])) {

    $group_name   = trim($_POST['group_name']);
    $account_type = trim($_POST['account_type']);

    if ($group_name == "") {

        $error = "Group name is required.";

    } else {

        // Check duplicate
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM account_groups
             WHERE group_name=? AND id<>?"
        );

        mysqli_stmt_bind_param(
            $check,
            "si",
            $group_name,
            $id
        );

        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $error = "Group name already exists.";

        } else {

            $update = mysqli_prepare(
                $conn,
                "UPDATE account_groups
                 SET group_name=?, account_type=?
                 WHERE id=?"
            );

            mysqli_stmt_bind_param(
                $update,
                "ssi",
                $group_name,
                $account_type,
                $id
            );

            if (mysqli_stmt_execute($update)) {

                header("Location: account_group.php?updated=1");
                exit();

            } else {

                $error = "Update failed.";

            }
        }
    }
}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-6">

<div class="card shadow">

<div class="card-header bg-warning">

<h4 class="mb-0">

<i class="fa-solid fa-pen"></i>

Edit Account Group

</h4>

</div>

<div class="card-body">

<?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<form method="post">

<div class="mb-3">

<label class="form-label">

Group Name

</label>

<input
type="text"
name="group_name"
class="form-control"
required
value="<?= htmlspecialchars($group['group_name']) ?>">

</div>

<div class="mb-3">

<label class="form-label">

Account Type

</label>

<select
name="account_type"
class="form-select"
required>

<?php

$types = [
"Asset",
"Liability",
"Equity",
"Income",
"Expense"
];

foreach($types as $type){

$selected = ($group['account_type']==$type) ? "selected" : "";

echo "<option value='$type' $selected>$type</option>";

}

?>

</select>

</div>

<div class="d-flex justify-content-between">

<a
href="account_group.php"
class="btn btn-secondary">

<i class="fa fa-arrow-left"></i>

Back

</a>

<button
type="submit"
name="update"
class="btn btn-primary">

<i class="fa fa-save"></i>

Update

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>