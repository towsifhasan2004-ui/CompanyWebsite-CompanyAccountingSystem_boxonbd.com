<?php
require_once 'auth.php';
require_once 'config/db.php';

$success = "";
$error = "";

/*=====================================
=            SAVE ACCOUNT             =
=====================================*/
if (isset($_POST['save'])) {

    $account_code     = trim($_POST['account_code']);
    $account_name     = trim($_POST['account_name']);
    $description      = trim($_POST['description']);
    $group_id         = (int)$_POST['group_id'];

    $parent_id = !empty($_POST['parent_id'])
        ? (int)$_POST['parent_id']
        : NULL;

    $opening_balance  = $_POST['opening_balance'];
    $opening_date     = $_POST['opening_date'];
    $balance_type     = $_POST['balance_type'];
    $status           = $_POST['status'];

    if ($account_code == "" || $account_name == "") {

        $error = "Account Code and Account Name are required.";

    } else {

        // Duplicate Account Code Check
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM accounts WHERE account_code=? LIMIT 1"
        );

        mysqli_stmt_bind_param($check, "s", $account_code);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $error = "Account Code already exists.";

        } else {

            $sql = "INSERT INTO accounts
            (
                account_code,
                account_name,
                description,
                group_id,
                parent_id,
                opening_balance,
                opening_date,
                balance_type,
                status
            )
            VALUES
            (?,?,?,?,?,?,?,?,?)";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "sssiidsss",
                $account_code,
                $account_name,
                $description,
                $group_id,
                $parent_id,
                $opening_balance,
                $opening_date,
                $balance_type,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {

                $success = "Account created successfully.";

            } else {

                $error = "Unable to save account.";

            }
        }
    }
}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="row">

<!-- =====================================
     ADD ACCOUNT
====================================== -->

<div class="col-lg-4">

<div class="card shadow-sm">

<div class="card-header bg-primary text-white">

<h5 class="mb-0">

<i class="fa-solid fa-wallet"></i>

Add New Account

</h5>

</div>

<div class="card-body">

<?php if($success!=""){ ?>

<div class="alert alert-success">

<?= $success ?>

</div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<form method="POST">

<div class="mb-3">

<label class="form-label">

Account Code

</label>

<input
type="text"
name="account_code"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

Account Name

</label>

<input
type="text"
name="account_name"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

Account Group

</label>

<select
name="group_id"
class="form-select"
required>

<option value="">

Select Account Group

</option>

<?php

$groups = mysqli_query(
$conn,
"SELECT * FROM account_groups
ORDER BY group_name ASC");

while($g = mysqli_fetch_assoc($groups))
{

?>

<option value="<?= $g['id']; ?>">

<?= htmlspecialchars($g['group_name']); ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

Parent Account

</label>

<select
name="parent_id"
class="form-select">

<option value="">

None

</option>

<?php

$parents = mysqli_query(
$conn,
"SELECT id,account_name
FROM accounts
ORDER BY account_name ASC");

while($p = mysqli_fetch_assoc($parents))
{

?>

<option value="<?= $p['id']; ?>">

<?= htmlspecialchars($p['account_name']); ?>

</option>

<?php } ?>

</select>

</div>

<div class="row">

<div class="col-md-6 mb-3">

<label>

Opening Balance

</label>

<input
type="number"
step="0.01"
name="opening_balance"
value="0.00"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>

Opening Date

</label>

<input
type="date"
name="opening_date"
value="<?= date('Y-m-d'); ?>"
class="form-control">

</div>

</div>

<div class="mb-3">

<label>

Balance Type

</label>

<select
name="balance_type"
class="form-select">

<option value="Debit">

Debit

</option>

<option value="Credit">

Credit

</option>

</select>

</div>

<div class="mb-3">

<label>

Status

</label>

<select
name="status"
class="form-select">

<option value="Active">

Active

</option>

<option value="Inactive">

Inactive

</option>

</select>

</div>

<div class="mb-3">

<label>

Description

</label>

<textarea
name="description"
rows="4"
class="form-control"></textarea>

</div>

<button
type="submit"
name="save"
class="btn btn-primary w-100">

<i class="fa-solid fa-floppy-disk"></i>

Save Account

</button>

</form>

</div>

</div>

</div>

<!-- Account List starts here in Part 2 -->
<div class="col-lg-8">

<div class="card shadow-sm">

    <div class="card-header bg-success text-white">

        <h5 class="mb-0">

            <i class="fa-solid fa-list"></i>

            Chart of Accounts

        </h5>

    </div>

    <div class="card-body">

        <?php if(isset($_GET['updated'])){ ?>

            <div class="alert alert-success alert-dismissible fade show">

                Account updated successfully.

                <button class="btn-close" data-bs-dismiss="alert"></button>

            </div>

        <?php } ?>

        <?php if(isset($_GET['deleted'])){ ?>

            <div class="alert alert-success alert-dismissible fade show">

                Account deleted successfully.

                <button class="btn-close" data-bs-dismiss="alert"></button>

            </div>

        <?php } ?>

        <table class="table table-bordered table-hover datatable">

            <thead class="table-dark">

            <tr>

                <th width="60">ID</th>
                <th>Code</th>
                <th>Account Name</th>
                <th>Group</th>
                <th>Opening Balance</th>
                <th>Status</th>
                <th width="120">Action</th>

            </tr>

            </thead>

            <tbody>

<?php

$sql = "

SELECT
a.*,
g.group_name

FROM accounts a

LEFT JOIN account_groups g
ON a.group_id = g.id

ORDER BY a.account_code ASC

";

$result = mysqli_query($conn,$sql);

while($row = mysqli_fetch_assoc($result))
{

?>

<tr>

<td>

<?= $row['id']; ?>

</td>

<td>

<strong>

<?= htmlspecialchars($row['account_code']); ?>

</strong>

</td>

<td>

<?= htmlspecialchars($row['account_name']); ?>

<?php

if($row['parent_id']!=NULL)
{
    echo "<br><small class='text-muted'>Sub Account</small>";
}

?>

</td>

<td>

<?= htmlspecialchars($row['group_name']); ?>

</td>

<td class="text-end">

<?= number_format($row['opening_balance'],2); ?>

<br>

<small class="text-muted">

<?= $row['balance_type']; ?>

</small>

</td>

<td>

<?php

if($row['status']=="Active")
{

?>

<span class="badge bg-success">

Active

</span>

<?php

}
else
{

?>

<span class="badge bg-danger">

Inactive

</span>

<?php

}

?>

</td>

<td>

<a
href="edit_account.php?id=<?= $row['id']; ?>"
class="btn btn-warning btn-sm">

<i class="fa-solid fa-pen"></i>

</a>

<a
href="delete_account.php?id=<?= $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Are you sure you want to delete this account?');">

<i class="fa-solid fa-trash"></i>

</a>

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

<?php include 'footer.php'; ?>
