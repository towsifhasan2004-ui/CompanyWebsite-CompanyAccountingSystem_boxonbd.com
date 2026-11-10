<?php
require_once "config/db.php";

$newPassword = password_hash("admin123", PASSWORD_DEFAULT);

$sql = "UPDATE users
        SET password = ?, status = 'active', role = 'admin'
        WHERE username = 'admin'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $newPassword);

if (mysqli_stmt_execute($stmt)) {
    echo "Admin password updated successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>