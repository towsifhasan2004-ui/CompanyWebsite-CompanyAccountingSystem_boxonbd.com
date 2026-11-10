<?php

function addNotification($conn, $user_id, $title, $message, $type='info', $link=null)
{
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO notifications
        (user_id, title, message, type, link, is_read)
        VALUES (?, ?, ?, ?, ?, 0)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "issss",
        $user_id,
        $title,
        $message,
        $type,
        $link
    );

    mysqli_stmt_execute($stmt);
}