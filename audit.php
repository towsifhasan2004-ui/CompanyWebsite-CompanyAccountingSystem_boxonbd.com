<?php

function logActivity(
    $conn,
    $module,
    $action,
    $record_id = null,
    $description = ""
)
{
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $user_id = $_SESSION['user_id'];

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO audit_logs
        (
            user_id,
            module,
            action,
            record_id,
            description,
            ip_address
        )
        VALUES
        (?,?,?,?,?,?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ississ",
        $user_id,
        $module,
        $action,
        $record_id,
        $description,
        $ip_address
    );

    mysqli_stmt_execute($stmt);
}