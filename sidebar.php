<?php require_once 'config/role.php'; ?>

<div class="d-flex">

    <!-- Sidebar -->
    <div class="sidebar bg-dark text-white">

        <div class="text-center py-3 border-bottom">
            <h5 class="mb-0">
                <i class="fa-solid fa-chart-line"></i>
                Accounting
            </h5>
        </div>

        <ul class="nav flex-column mt-3">

            <li class="nav-item">
                <a href="dashboard.php" class="nav-link text-white">
                    <i class="fa-solid fa-gauge"></i>
                    Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a href="user_management.php" class="nav-link text-white">
                    <i class="fa-solid fa-users"></i>
                    User Management
                </a>
            </li>

            <li class="nav-item">
                <a href="company.php" class="nav-link text-white">
                    <i class="fa-solid fa-building"></i>
                    Company
                </a>
            </li>

            <li class="nav-item">
                <a href="account_group.php" class="nav-link text-white">
                    <i class="fa-solid fa-layer-group"></i>
                    Account Groups
                </a>
            </li>

            <li class="nav-item">
                <a href="accounts.php" class="nav-link text-white">
                    <i class="fa-solid fa-wallet"></i>
                    Chart of Accounts
                </a>
            </li>

            <hr class="text-secondary">

            <li class="nav-item">
                <a href="receipt_voucher.php" class="nav-link text-white">
                    <i class="fa-solid fa-arrow-down"></i>
                    Receipt Voucher
                </a>
            </li>

            <li class="nav-item">
                <a href="payment_voucher.php" class="nav-link text-white">
                    <i class="fa-solid fa-arrow-up"></i>
                    Payment Voucher
                </a>
            </li>

            <li class="nav-item">
                <a href="contra_voucher.php" class="nav-link text-white">
                    <i class="fa-solid fa-right-left"></i>
                    Contra Voucher
                </a>
            </li>

            <li class="nav-item">
                <a href="journal_voucher.php" class="nav-link text-white">
                    <i class="fa-solid fa-book"></i>
                    Journal Voucher
                </a>
            </li>

            <hr class="text-secondary">

            <?php if(isManager()){ ?>
            <li class="nav-item">

                <a href="manager_review.php" class="nav-link text-white">

                    <i class="fa fa-check-square"></i>

                    Manager Review

                </a>

            </li>
            <?php } ?>

            <?php if(isAdmin()){ ?>
            <li class="nav-item">
                <a href="admin_approval.php" class="nav-link text-white">
                    <i class="fa fa-check-circle"></i>
                    Voucher Approval
                </a>
            </li>
            <li class="nav-item">
                <a href="audit_logs.php" class="nav-link text-white">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Audit Logs
                </a>
            </li>
            <?php } ?>

            <hr class="text-secondary">

            <li class="nav-item">
                <a href="reports.php" class="nav-link text-white">
                    <i class="fa-solid fa-print"></i>
                    Reports
                </a>
            </li>

            <li class="nav-item">
                <?php if(isset($_SESSION['role']) && $_SESSION['role']=="admin"){ ?>

<li class="nav-item">

    <a href="setting.php" class="nav-link text-white">

        <i class="fa-solid fa-gear"></i>

        Settings

    </a>

</li>

<?php } ?>
            </li>

            <li class="nav-item">
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>
            </li>

        </ul>

    </div>

    <!-- Main Content -->
    <div class="content flex-grow-1 p-4">