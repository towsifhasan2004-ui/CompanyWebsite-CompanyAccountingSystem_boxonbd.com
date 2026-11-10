<?php
require_once 'auth.php';
require_once 'config/db.php';

$success = "";
$error = "";

// ===========================
// Save Account Group
// ===========================
if (isset($_POST['save'])) {

    $group_name   = trim($_POST['group_name']);
    $account_type = trim($_POST['account_type']);

    if ($group_name == "") {

        $error = "Group name is required.";

    } else {

        // Check duplicate
        $check = mysqli_prepare($conn,
            "SELECT id FROM account_groups WHERE group_name=? LIMIT 1");

        mysqli_stmt_bind_param($check, "s", $group_name);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $error = "Account group already exists.";

        } else {

            // Insert
            $insert = mysqli_prepare($conn,
                "INSERT INTO account_groups (group_name, account_type)
                 VALUES (?, ?)");

            mysqli_stmt_bind_param(
                $insert,
                "ss",
                $group_name,
                $account_type
            );

            if (mysqli_stmt_execute($insert)) {

                $success = "Account group added successfully.";

            } else {

                $error = "Failed to save account group.";

            }
        }
    }
}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

    <?php if(isset($_GET['updated'])){ ?>
    <div class="alert alert-success alert-dismissible fade show">
        Account group updated successfully.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php } ?>

    <?php if(isset($_GET['deleted'])){ ?>
    <div class="alert alert-success alert-dismissible fade show">
        Account group deleted successfully.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php } ?>

    <?php if(isset($_GET['error'])){ ?>
    <div class="alert alert-danger alert-dismissible fade show">
        Unable to delete the account group.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php } ?>

    <?php if(isset($_GET['used'])){ ?>
    <div class="alert alert-warning alert-dismissible fade show">
        This account group is being used by one or more accounts and cannot be deleted.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php } ?>

    <div class="row">

        <!-- Add Form -->
        <div class="col-lg-4">

            <div class="card shadow-sm">

                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-layer-group"></i>
                        Add Account Group
                    </h5>
                </div>

                <div class="card-body">

                    <?php if ($success != "") { ?>
                        <div class="alert alert-success">
                            <?= $success; ?>
                        </div>
                    <?php } ?>

                    <?php if ($error != "") { ?>
                        <div class="alert alert-danger">
                            <?= $error; ?>
                        </div>
                    <?php } ?>

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Group Name
                            </label>

                            <input
                                type="text"
                                name="group_name"
                                class="form-control"
                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Account Type
                            </label>

                            <select
                                name="account_type"
                                class="form-select"
                                required>

                                <option value="">Select Type</option>
                                <option value="Asset">Asset</option>
                                <option value="Liability">Liability</option>
                                <option value="Equity">Equity</option>
                                <option value="Income">Income</option>
                                <option value="Expense">Expense</option>

                            </select>

                        </div>

                        <button
                            type="submit"
                            name="save"
                            class="btn btn-primary w-100">

                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Group

                        </button>

                    </form>

                </div>

            </div>

        </div>

        <!-- Group List -->
        <div class="col-lg-8">

            <div class="card shadow-sm">

                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-table"></i>
                        Account Group List
                    </h5>
                </div>

                <div class="card-body">

                    <table class="table table-bordered table-hover datatable">

                        <thead class="table-dark">

                            <tr>
                                <th>ID</th>
                                <th>Group Name</th>
                                <th>Account Type</th>
                                <th>Created</th>
                                <th width="120">Action</th>
                            </tr>

                        </thead>

                        <tbody>

<?php
$result = mysqli_query($conn,
    "SELECT * FROM account_groups ORDER BY id DESC");

while ($row = mysqli_fetch_assoc($result)) {
?>

<tr>

    <td><?= $row['id']; ?></td>

    <td><?= htmlspecialchars($row['group_name']); ?></td>

    <td>
        <span class="badge bg-primary">
            <?= $row['account_type']; ?>
        </span>
    </td>

    <td><?= date("d M Y", strtotime($row['created_at'])); ?></td>

    <td>

        <a href="edit_account_group.php?id=<?= $row['id']; ?>"
           class="btn btn-warning btn-sm">

            <i class="fa fa-edit"></i>

        </a>

        <a href="delete_account_group.php?id=<?= $row['id']; ?>"
           class="btn btn-danger btn-sm"
           onclick="return confirm('Delete this account group?')">

            <i class="fa fa-trash"></i>

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