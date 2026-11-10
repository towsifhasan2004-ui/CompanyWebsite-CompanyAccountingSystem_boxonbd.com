<?php
require_once "config/db.php";

$full_name = "Administrator";
$username = "admin";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "admin";
$status = "active";

$sql = "INSERT INTO users (full_name, username, password, role, status)
        VALUES (?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssss", $full_name, $username, $password, $role, $status);

if (mysqli_stmt_execute($stmt)) {
    echo "Admin created successfully.";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>