<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkRole($roles = [])
{
    if (!isset($_SESSION['role'])) {
        header("Location: login.php");
        exit();
    }

    if (!in_array($_SESSION['role'], $roles)) {
        echo "<h3 style='color:red;text-align:center;margin-top:50px'>
                Access Denied
              </h3>";
        exit();
    }
}

function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}

function isManager() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'manager');
}

function isAccountant() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'accountant');
}