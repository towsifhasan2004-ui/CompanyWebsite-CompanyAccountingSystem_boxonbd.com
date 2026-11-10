<?php
require_once 'auth.php';
require_once 'config/db.php';

$error = "";
$success = "";

// Check ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: accounts.php");
    exit();
}

$id = (int)$_GET['id'];

// Load Account
$stmt = mysqli_prepare($conn, "SELECT * FROM accounts WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$account = mysqli_fetch_assoc($result)) {
    header("Location: accounts.php");
    exit();
}

// Update Account
if (isset($_POST['update'])) {

    $account_code    = trim($_POST['account_code']);
    $account_name    = trim($_POST['account_name']);
    $description     = trim($_POST['description']);
    $group_id        = (int)$_POST['group_id'];
    $parent_id       = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
    $opening_balance = $_POST['opening_balance'];
    $opening_date    = $_POST['opening_date'];
    $balance_type    = $_POST['balance_type'];
    $status          = $_POST['status'];

    if ($account_code == "" || $account_name == "") {

        $error = "Account Code and Account Name are required.";

    } else {

        // Duplicate Code Check
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM accounts
             WHERE account_code=? AND id<>?"
        );

        mysqli_stmt_bind_param(
            $check,
            "si",
            $account_code,
            $id
        );

        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $error = "Account code already exists.";

        } else {

            $sql = "UPDATE accounts SET

                account_code=?,
                account_name=?,
                description=?,
                group_id=?,
                parent_id=?,
                opening_balance=?,
                opening_date=?,
                balance_type=?,
                status=?

                WHERE id=?";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "sssiidsssi",
                $account_code,
                $account_name,
                $description,
                $group_id,
                $parent_id,
                $opening_balance,
                $opening_date,
                $balance_type,
                $status,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {

                header("Location: accounts.php?updated=1");
                exit();

            } else {

                $error = "Unable to update account.";

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

<div class="col-lg-8">

<div class="card shadow-sm">

<div class="card-header bg-warning text-dark">

<h5 class="mb-0">

<i class="fa-solid fa-pen-to-square"></i>

Edit Account

</h5>

</div>

<div class="card-body">

<?php if($error!=""){ ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php } ?>

<form method="POST">

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">Account Code</label>

<input
type="text"
name="account_code"
class="form-control"
required
value="<?= htmlspecialchars($account['account_code']) ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">Account Name</label>

<input
type="text"
name="account_name"
class="form-control"
required
value="<?= htmlspecialchars($account['account_name']) ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">Account Group</label>

<select
name="group_id"
class="form-select"
required>

<?php
$groups = mysqli_query($conn, "SELECT * FROM account_groups ORDER BY group_name");

while($g = mysqli_fetch_assoc($groups)) {

$selected = ($account['group_id'] == $g['id']) ? "selected" : "";
?>

<option value="<?= $g['id'] ?>" <?= $selected ?>>
<?= htmlspecialchars($g['group_name']) ?>
</option>

<?php } ?>

</select>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">Parent Account</label>

<select
name="parent_id"
class="form-select">

<option value="">None</option>

<?php
$parents = mysqli_query($conn,
"SELECT id, account_name FROM accounts
 WHERE id <> $id
 ORDER BY account_name");

while($p = mysqli_fetch_assoc($parents)) {

$selected = ($account['parent_id'] == $p['id']) ? "selected" : "";
?>

<option value="<?= $p['id'] ?>" <?= $selected ?>>
<?= htmlspecialchars($p['account_name']) ?>
</option>

<?php } ?>

</select>

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">Opening Balance</label>

    <input
        type="number"
        step="0.01"
        name="opening_balance"
        class="form-control"
        value="<?= $account['opening_balance']; ?>">

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">Opening Date</label>

    <input
        type="date"
        name="opening_date"
        class="form-control"
        value="<?= $account['opening_date']; ?>">

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">Balance Type</label>

    <select
        name="balance_type"
        class="form-select">

        <option value="Debit"
            <?= ($account['balance_type']=="Debit") ? "selected" : ""; ?>>
            Debit
        </option>

        <option value="Credit"
            <?= ($account['balance_type']=="Credit") ? "selected" : ""; ?>>
            Credit
        </option>

    </select>

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">Status</label>

    <select
        name="status"
        class="form-select">

        <option value="Active"
            <?= ($account['status']=="Active") ? "selected" : ""; ?>>
            Active
        </option>

        <option value="Inactive"
            <?= ($account['status']=="Inactive") ? "selected" : ""; ?>>
            Inactive
        </option>

    </select>

</div>

<div class="col-12 mb-3">

    <label class="form-label">Description</label>

    <textarea
        name="description"
        rows="4"
        class="form-control"><?= htmlspecialchars($account['description']); ?></textarea>

</div>

<div class="col-12">

    <div class="d-flex justify-content-between">

        <a
            href="accounts.php"
            class="btn btn-secondary">

            <i class="fa-solid fa-arrow-left"></i>

            Back

        </a>

        <button
            type="submit"
            name="update"
            class="btn btn-primary">

            <i class="fa-solid fa-floppy-disk"></i>

            Update Account

        </button>

    </div>

</div>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>