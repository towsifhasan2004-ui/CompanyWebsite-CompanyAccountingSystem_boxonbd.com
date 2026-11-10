<?php

require_once 'auth.php';
require_once 'config/db.php';

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

/* ==========================================
   COMPANY INFORMATION
========================================== */

$company = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT * FROM company LIMIT 1"
    )
);

/* ==========================================
   TOTAL ACTIVE ACCOUNTS
========================================== */

$totalAccounts = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) total
         FROM accounts
         WHERE status='Active'"
    )
)['total'];

/* ==========================================
   TOTAL ACTIVE USERS
========================================== */

$totalUsers = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) total
         FROM users
         WHERE status='active'"
    )
)['total'];

/* ==========================================
   CASH BALANCE
========================================== */

$cash = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "
        SELECT
        IFNULL(SUM(vd.debit-vd.credit),0) balance

        FROM voucher_details vd

        INNER JOIN accounts a

        ON a.id=vd.account_id

        WHERE

        LOWER(a.account_name) LIKE '%cash%'
        "

    )

)['balance'];

/* ==========================================
   BANK BALANCE
========================================== */

$bank = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "
        SELECT
        IFNULL(SUM(vd.debit-vd.credit),0) balance

        FROM voucher_details vd

        INNER JOIN accounts a

        ON a.id=vd.account_id

        WHERE

        LOWER(a.account_name) LIKE '%bank%'
        "

    )

)['balance'];

/* ==========================================
   TODAY RECEIPTS
========================================== */

$todayReceipt = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "
        SELECT

        IFNULL(SUM(vd.debit),0) total

        FROM vouchers v

        INNER JOIN voucher_details vd

        ON vd.voucher_id=v.id

        WHERE

        v.voucher_type='Receipt'

        AND

        v.status='Approved'

        AND

        vd.debit>0

        AND

        DATE(v.voucher_date)=CURDATE()

        "

    )

)['total'];

/* ==========================================
   TODAY PAYMENTS
========================================== */

$todayPayment = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "
        SELECT

        IFNULL(SUM(vd.credit),0) total

        FROM vouchers v

        INNER JOIN voucher_details vd

        ON vd.voucher_id=v.id

        WHERE

        v.voucher_type='Payment'

        AND

        v.status='Approved'

        AND

        vd.credit>0

        AND

        DATE(v.voucher_date)=CURDATE()

        "

    )

)['total'];

/* ==========================================
   NET BALANCE
========================================== */

$netBalance = $cash + $bank;/* ==========================================
   MONTHLY RECEIPT & PAYMENT (LAST 6 MONTHS)
========================================== */

$months = [];
$receiptData = [];
$paymentData = [];

for($i = 5; $i >= 0; $i--)
{

    $month = date("Y-m", strtotime("-$i month"));

    $months[] = date("M", strtotime($month."-01"));

    /* -----------------------------
       MONTHLY RECEIPT
    ------------------------------ */

    $receiptQuery = mysqli_query(

        $conn,

        "
        SELECT

        IFNULL(SUM(vd.debit),0) total

        FROM vouchers v

        INNER JOIN voucher_details vd

        ON vd.voucher_id = v.id

        WHERE

        v.voucher_type='Receipt'

        AND

        v.status='Approved'

        AND

        vd.debit>0

        AND

        DATE_FORMAT(v.voucher_date,'%Y-%m')='$month'
        "

    );

    $receipt = mysqli_fetch_assoc($receiptQuery);

    $receiptData[] = (float)$receipt['total'];

    /* -----------------------------
       MONTHLY PAYMENT
    ------------------------------ */

    $paymentQuery = mysqli_query(

        $conn,

        "
        SELECT

        IFNULL(SUM(vd.credit),0) total

        FROM vouchers v

        INNER JOIN voucher_details vd

        ON vd.voucher_id = v.id

        WHERE

        v.voucher_type='Payment'

        AND

        v.status='Approved'

        AND

        vd.credit>0

        AND

        DATE_FORMAT(v.voucher_date,'%Y-%m')='$month'
        "

    );

    $payment = mysqli_fetch_assoc($paymentQuery);

    $paymentData[] = (float)$payment['total'];

}

/* ==========================================
   RECENT TRANSACTIONS
========================================== */

$transactions = mysqli_query(

    $conn,

    "
    SELECT

        v.id,

        v.voucher_no,

        v.voucher_date,

        v.voucher_type,

        a.account_name,

        vd.debit,

        vd.credit,

        CASE

            WHEN vd.debit>0

            THEN vd.debit

            ELSE vd.credit

        END AS amount

    FROM vouchers v

    INNER JOIN voucher_details vd

    ON vd.voucher_id = v.id

    INNER JOIN accounts a

    ON a.id = vd.account_id

    WHERE

    v.status='Approved'

    ORDER BY

    v.voucher_date DESC,

    v.id DESC

    LIMIT 10
    "

);

if(!$transactions)
{
    die("Recent Transaction Query Error : ".mysqli_error($conn));
}

/* ==========================================
   VOUCHER STATUS SUMMARY
========================================== */

$pending = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "SELECT COUNT(*) total
         FROM vouchers
         WHERE status='Pending'"

    )

)['total'];

$checked = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "SELECT COUNT(*) total
         FROM vouchers
         WHERE status='Checked'"

    )

)['total'];

$approved = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "SELECT COUNT(*) total
         FROM vouchers
         WHERE status='Approved'"

    )

)['total'];

$rejected = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "SELECT COUNT(*) total
         FROM vouchers
         WHERE status='Rejected'"

    )

)['total'];

?><div class="container-fluid">

<!-- ==========================
     PAGE HEADER
========================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h3 class="fw-bold mb-0">
            <i class="fa-solid fa-chart-line text-primary"></i>
            Dashboard
        </h3>

        <small class="text-muted">
            Welcome,
            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
        </small>

    </div>

    <div>

        <h6 class="mb-0">

            <?= htmlspecialchars($company['company_name']) ?>

        </h6>

        <small class="text-muted">

            Financial Year :
            <?= htmlspecialchars($company['financial_year']) ?>

        </small>

    </div>

</div>

<!-- ==========================
     FIRST ROW
========================== -->

<div class="row">

<div class="col-lg-3 col-md-6 mb-4">

<div class="card shadow border-start border-success border-4">

<div class="card-body">

<h6 class="text-muted">

Cash Balance

</h6>

<h3 class="text-success fw-bold">

৳ <?= number_format($cash,2) ?>

</h3>

<i class="fa-solid fa-money-bill-wave fa-2x text-success float-end"></i>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6 mb-4">

<div class="card shadow border-start border-primary border-4">

<div class="card-body">

<h6 class="text-muted">

Bank Balance

</h6>

<h3 class="text-primary fw-bold">

৳ <?= number_format($bank,2) ?>

</h3>

<i class="fa-solid fa-building-columns fa-2x text-primary float-end"></i>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6 mb-4">

<div class="card shadow border-start border-info border-4">

<div class="card-body">

<h6 class="text-muted">

Today's Receipt

</h6>

<h3 class="text-info fw-bold">

৳ <?= number_format($todayReceipt,2) ?>

</h3>

<i class="fa-solid fa-arrow-down fa-2x text-info float-end"></i>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6 mb-4">

<div class="card shadow border-start border-danger border-4">

<div class="card-body">

<h6 class="text-muted">

Today's Payment

</h6>

<h3 class="text-danger fw-bold">

৳ <?= number_format($todayPayment,2) ?>

</h3>

<i class="fa-solid fa-arrow-up fa-2x text-danger float-end"></i>

</div>

</div>

</div>

</div>

<!-- ==========================
     SECOND ROW
========================== -->

<div class="row">

<div class="col-lg-3 col-md-6 mb-4">

<div class="card shadow">

<div class="card-body text-center">

<i class="fa-solid fa-book fa-2x text-primary mb-2"></i>

<h6>Total Accounts</h6>

<h3><?= $totalAccounts ?></h3>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6 mb-4">

<div class="card shadow">

<div class="card-body text-center">

<i class="fa-solid fa-users fa-2x text-success mb-2"></i>

<h6>Total Users</h6>

<h3><?= $totalUsers ?></h3>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6 mb-4">

<div class="card shadow">

<div class="card-body text-center">

<i class="fa-solid fa-building fa-2x text-warning mb-2"></i>

<h6>Company</h6>

<h5><?= htmlspecialchars($company['company_name']) ?></h5>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6 mb-4">

<div class="card shadow">

<div class="card-body text-center">

<i class="fa-solid fa-wallet fa-2x text-success mb-2"></i>

<h6>Net Balance</h6>

<h3 class="text-success">

৳ <?= number_format($netBalance,2) ?>

</h3>

</div>

</div>

</div>

</div>

<!-- ==========================
     VOUCHER STATUS
========================== -->

<div class="row">

<div class="col-lg-3 mb-3">

<div class="card bg-warning text-dark shadow">

<div class="card-body text-center">

<h6>Pending</h6>

<h2><?= $pending ?></h2>

</div>

</div>

</div>

<div class="col-lg-3 mb-3">

<div class="card bg-info text-white shadow">

<div class="card-body text-center">

<h6>Checked</h6>

<h2><?= $checked ?></h2>

</div>

</div>

</div>

<div class="col-lg-3 mb-3">

<div class="card bg-success text-white shadow">

<div class="card-body text-center">

<h6>Approved</h6>

<h2><?= $approved ?></h2>

</div>

</div>

</div>

<div class="col-lg-3 mb-3">

<div class="card bg-danger text-white shadow">

<div class="card-body text-center">

<h6>Rejected</h6>

<h2><?= $rejected ?></h2>

</div>

</div>

</div>

</div>
<!-- ==========================================
     QUICK SUMMARY
========================================== -->

<div class="row">

    <div class="col-lg-4 mb-4">

        <div class="card shadow border-left-success">

            <div class="card-body">

                <h5 class="text-success">

                    <i class="fa-solid fa-arrow-trend-up"></i>

                    Total Receipt (6 Months)

                </h5>

                <h3>

                    ৳ <?= number_format(array_sum($receiptData),2); ?>

                </h3>

            </div>

        </div>

    </div>

    <div class="col-lg-4 mb-4">

        <div class="card shadow border-left-danger">

            <div class="card-body">

                <h5 class="text-danger">

                    <i class="fa-solid fa-arrow-trend-down"></i>

                    Total Payment (6 Months)

                </h5>

                <h3>

                    ৳ <?= number_format(array_sum($paymentData),2); ?>

                </h3>

            </div>

        </div>

    </div>

    <div class="col-lg-4 mb-4">

        <div class="card shadow border-left-primary">

            <div class="card-body">

                <h5 class="text-primary">

                    <i class="fa-solid fa-scale-balanced"></i>

                    Difference

                </h5>

                <h3>

                    ৳ <?= number_format(array_sum($receiptData)-array_sum($paymentData),2); ?>

                </h3>

            </div>

        </div>

    </div>

</div>

<!-- ==========================================
     RECENT TRANSACTIONS TITLE
========================================== -->

<div class="card shadow">

    <div class="card-header bg-dark text-white">

        <h5 class="mb-0">

            <i class="fa-solid fa-clock-rotate-left"></i>

            Recent Transactions

        </h5>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-bordered table-hover">

                <thead class="table-primary">

                <tr>

                    <th>Date</th>

                    <th>Voucher No</th>

                    <th>Type</th>

                    <th>Account</th>

                    <th class="text-end">Debit</th>

                    <th class="text-end">Credit</th>

                    <th class="text-end">Amount</th>

                </tr>

                </thead>

                <tbody><?php

if(mysqli_num_rows($transactions)>0)
{

    while($row=mysqli_fetch_assoc($transactions))
    {

?>

<tr>

    <td>

        <?= date('d-M-Y',strtotime($row['voucher_date'])) ?>

    </td>

    <td>

        <strong>

            <?= htmlspecialchars($row['voucher_no']) ?>

        </strong>

    </td>

    <td>

        <?php

        switch($row['voucher_type'])
        {

            case 'Receipt':

                echo '<span class="badge bg-success">Receipt</span>';

                break;

            case 'Payment':

                echo '<span class="badge bg-danger">Payment</span>';

                break;

            case 'Contra':

                echo '<span class="badge bg-primary">Contra</span>';

                break;

            case 'Journal':

                echo '<span class="badge bg-warning text-dark">Journal</span>';

                break;

        }

        ?>

    </td>

    <td>

        <?= htmlspecialchars($row['account_name']) ?>

    </td>

    <td class="text-end">

        <?= number_format($row['debit'],2) ?>

    </td>

    <td class="text-end">

        <?= number_format($row['credit'],2) ?>

    </td>

    <td class="text-end fw-bold">

        <?= number_format($row['amount'],2) ?>

    </td>

</tr>

<?php

    }

}

else

{

?>

<tr>

    <td colspan="7" class="text-center text-danger">

        No Approved Transactions Found.

    </td>

</tr>

<?php

}

?>

</tbody>

</table>

</div>

</div>

</div>

</div>

<!-- ==========================================
     ACCOUNT SUMMARY
========================================== -->

    <div class="col-lg-6">

        <div class="card shadow">

            <div class="card-header bg-success text-white">

                <h5 class="mb-0">

                    <i class="fa-solid fa-file-invoice"></i>

                    Voucher Summary

                </h5>

            </div>

            <div class="card-body">

                <table class="table table-bordered">

                    <tr>

                        <th>Total Receipt</th>

                        <td><?= $approved ?></td>

                    </tr>

                    <tr>

                        <th>Total Accounts</th>

                        <td><?= $totalAccounts ?></td>

                    </tr>

                    <tr>

                        <th>Total Users</th>

                        <td><?= $totalUsers ?></td>

                    </tr>

                    <tr>

                        <th>Financial Year</th>

                        <td><?= htmlspecialchars($company['financial_year']) ?></td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

<?php include 'footer.php'; ?>