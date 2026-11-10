<?php
require_once 'config/db.php';

// Company Information
$company = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM company LIMIT 1")
);

$companyName = $company['company_name'] ?? 'Office Accounting';

// Notification Count
$user_id = $_SESSION['user_id'];

$count = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM notifications
         WHERE user_id='$user_id'
         AND is_read=0"
    )
);

$notification_count = $count['total'];
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">

<div class="container-fluid">

    <!-- Company Name -->
    <a class="navbar-brand fw-bold" href="dashboard.php">

        <i class="fa-solid fa-building"></i>

        <?= htmlspecialchars($companyName); ?>

    </a>

    <div class="ms-auto d-flex align-items-center">

        <!-- Notification -->
        <div class="dropdown me-3">

            <a
            href="#"
            class="text-white position-relative text-decoration-none"
            data-bs-toggle="dropdown">

                <i class="fa-solid fa-bell fa-lg"></i>

                <?php if($notification_count>0){ ?>

                <span
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">

                    <?= $notification_count ?>

                </span>

                <?php } ?>

            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow" style="width:350px;">

                <li>

                    <h6 class="dropdown-header">

                        Notifications

                    </h6>

                </li>

                <?php

                $notify = mysqli_query(
                    $conn,
                    "SELECT *
                     FROM notifications
                     WHERE user_id='$user_id'
                     ORDER BY id DESC
                     LIMIT 5"
                );

                if(mysqli_num_rows($notify)>0)
                {
                    while($n=mysqli_fetch_assoc($notify))
                    {
                ?>

                <li>

                    <a
                    class="dropdown-item"
                    href="<?= htmlspecialchars($n['link']) ?>">

                        <strong>

                            <?= htmlspecialchars($n['title']) ?>

                        </strong>

                        <br>

                        <small>

                            <?= htmlspecialchars($n['message']) ?>

                        </small>

                    </a>

                </li>

                <?php
                    }
                }
                else
                {
                ?>

                <li>

                    <span class="dropdown-item text-muted">

                        No Notifications

                    </span>

                </li>

                <?php } ?>

                <li><hr class="dropdown-divider"></li>

                <li>

                    <a
                    href="notifications.php"
                    class="dropdown-item text-center fw-bold">

                        View All Notifications

                    </a>

                </li>

            </ul>

        </div>

        <!-- Current Date -->
        <span class="text-white me-4">

            <i class="fa-solid fa-calendar-days"></i>

            <?= date('d M Y'); ?>

        </span>

        <!-- Logged In User -->
        <span class="text-white me-4">

            <i class="fa-solid fa-user"></i>

            <?= htmlspecialchars($_SESSION['name']); ?>

        </span>

        <!-- Logout -->
        <a href="logout.php" class="btn btn-danger btn-sm">

            <i class="fa-solid fa-right-from-bracket"></i>

            Logout

        </a>

    </div>

</div>

</nav>