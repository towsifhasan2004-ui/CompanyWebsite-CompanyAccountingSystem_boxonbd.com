<?php
require_once 'auth.php';
require_once 'config/db.php';

// Check ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: account_group.php");
    exit();
}

$id = (int)$_GET['id'];

/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
| After we build the Accounts module, uncomment the code below.
| It prevents deleting an account group that is already assigned
| to one or more accounts.
|
| $check = mysqli_prepare($conn,
|     "SELECT id FROM accounts WHERE group_id=? LIMIT 1");
|
| mysqli_stmt_bind_param($check,"i",$id);
| mysqli_stmt_execute($check);
|
| $result = mysqli_stmt_get_result($check);
|
| if(mysqli_num_rows($result)>0){
|     header("Location: account_group.php?used=1");
|     exit();
| }
|--------------------------------------------------------------------------
*/

// Delete Group
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM account_groups WHERE id=?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {

    header("Location: account_group.php?deleted=1");
    exit();

} else {

    header("Location: account_group.php?error=1");
    exit();

}
?>