<?php

/*==========================================================
    ERP ACCOUNTING ENGINE
==========================================================*/

if (!defined('ACCOUNTING_ENGINE')) {

define('ACCOUNTING_ENGINE', true);

/*==========================================================
    ACCOUNT BALANCE
==========================================================*/

function accountBalance($conn, $accountId)
{

    $sql = "

    SELECT

        a.opening_balance,
        a.balance_type,

        IFNULL(SUM(vd.debit),0) debit,

        IFNULL(SUM(vd.credit),0) credit

    FROM accounts a

    LEFT JOIN voucher_details vd

        ON vd.account_id = a.id

    LEFT JOIN vouchers v

        ON v.id = vd.voucher_id

        AND v.status='Approved'

    WHERE a.id=?

    GROUP BY a.id

    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $accountId);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (!$row = mysqli_fetch_assoc($result)) {

        return 0;

    }

    $opening = (float)$row['opening_balance'];

    $debit = (float)$row['debit'];

    $credit = (float)$row['credit'];

    if ($row['balance_type'] == "Debit") {

        return $opening + $debit - $credit;

    } else {

        return $opening + $credit - $debit;

    }

}

/*==========================================================
    ACCOUNT GROUP BALANCE
==========================================================*/

function groupBalance($conn, $type)
{

    $total = 0;

    $accounts = mysqli_query(

        $conn,

        "

        SELECT a.id

        FROM accounts a

        INNER JOIN account_groups ag

            ON ag.id=a.group_id

        WHERE ag.account_type='$type'

        "

    );

    while ($row = mysqli_fetch_assoc($accounts)) {

        $total += accountBalance($conn, $row['id']);

    }

    return $total;

}/*==========================================================
    CASH BALANCE
==========================================================*/

function cashBalance($conn)
{

    $total = 0;

    $sql = "

    SELECT id

    FROM accounts

    WHERE LOWER(account_name) LIKE '%cash%'

    ";

    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {

        $total += accountBalance($conn, $row['id']);

    }

    return $total;

}


/*==========================================================
    BANK BALANCE
==========================================================*/

function bankBalance($conn)
{

    $total = 0;

    $sql = "

    SELECT id

    FROM accounts

    WHERE LOWER(account_name) LIKE '%bank%'

    ";

    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {

        $total += accountBalance($conn, $row['id']);

    }

    return $total;

}


/*==========================================================
    TOTAL ASSETS
==========================================================*/

function totalAssets($conn)
{

    return groupBalance($conn, 'Asset');

}


/*==========================================================
    TOTAL LIABILITIES
==========================================================*/

function totalLiabilities($conn)
{

    return groupBalance($conn, 'Liability');

}


/*==========================================================
    TOTAL EQUITY
==========================================================*/

function totalEquity($conn)
{

    return groupBalance($conn, 'Equity');

}


/*==========================================================
    TOTAL INCOME
==========================================================*/

function totalIncome($conn)
{

    return groupBalance($conn, 'Income');

}


/*==========================================================
    TOTAL EXPENSE
==========================================================*/

function totalExpense($conn)
{

    return groupBalance($conn, 'Expense');

}/*==========================================================
    NET PROFIT
==========================================================*/

function netProfit($conn)
{

    return totalIncome($conn) - totalExpense($conn);

}


/*==========================================================
    GRAND EQUITY
==========================================================*/

function grandEquity($conn)
{

    return totalEquity($conn) + netProfit($conn);

}


/*==========================================================
    WORKING CAPITAL
==========================================================*/

function workingCapital($conn)
{

    return totalAssets($conn) - totalLiabilities($conn);

}


/*==========================================================
    TOTAL CASH & BANK
==========================================================*/

function cashAndBank($conn)
{

    return cashBalance($conn) + bankBalance($conn);

}


/*==========================================================
    TRIAL BALANCE DIFFERENCE
==========================================================*/

function trialBalanceDifference($conn)
{

    $difference =

        totalAssets($conn)

        -

        (

            totalLiabilities($conn)

            +

            grandEquity($conn)

        );

    return round($difference,2);

}


/*==========================================================
    IS TRIAL BALANCE BALANCED
==========================================================*/

function isBalanced($conn)
{

    return abs(trialBalanceDifference($conn)) < 0.01;

}


/*==========================================================
    TOTAL DEBIT
==========================================================*/

function totalDebit($conn)
{

    $sql="

    SELECT

    IFNULL(SUM(debit),0) total

    FROM voucher_details vd

    INNER JOIN vouchers v

    ON v.id=vd.voucher_id

    WHERE v.status='Approved'

    ";

    $row=mysqli_fetch_assoc(mysqli_query($conn,$sql));

    return (float)$row['total'];

}


/*==========================================================
    TOTAL CREDIT
==========================================================*/

function totalCredit($conn)
{

    $sql="

    SELECT

    IFNULL(SUM(credit),0) total

    FROM voucher_details vd

    INNER JOIN vouchers v

    ON v.id=vd.voucher_id

    WHERE v.status='Approved'

    ";

    $row=mysqli_fetch_assoc(mysqli_query($conn,$sql));

    return (float)$row['total'];

}/*==========================================================
    GET ALL ACCOUNTS BY ACCOUNT TYPE
==========================================================*/

function getAccountsByType($conn,$type)
{

    $rows=[];

    $sql="

    SELECT

        a.id,
        a.account_code,
        a.account_name,
        a.balance_type,
        ag.account_type

    FROM accounts a

    INNER JOIN account_groups ag

        ON ag.id=a.group_id

    WHERE

        ag.account_type=?

    AND

        a.status='Active'

    ORDER BY

        a.account_code ASC

    ";

    $stmt=mysqli_prepare($conn,$sql);

    mysqli_stmt_bind_param($stmt,"s",$type);

    mysqli_stmt_execute($stmt);

    $result=mysqli_stmt_get_result($stmt);

    while($row=mysqli_fetch_assoc($result))
    {

        $row['balance']=accountBalance(

            $conn,

            $row['id']

        );

        $rows[]=$row;

    }

    return $rows;

}

/*==========================================================
    ASSET ACCOUNTS
==========================================================*/

function assetAccounts($conn)
{

    return getAccountsByType(

        $conn,

        'Asset'

    );

}

/*==========================================================
    LIABILITY ACCOUNTS
==========================================================*/

function liabilityAccounts($conn)
{

    return getAccountsByType(

        $conn,

        'Liability'

    );

}

/*==========================================================
    EQUITY ACCOUNTS
==========================================================*/

function equityAccounts($conn)
{

    return getAccountsByType(

        $conn,

        'Equity'

    );

}

/*==========================================================
    INCOME ACCOUNTS
==========================================================*/

function incomeAccounts($conn)
{

    return getAccountsByType(

        $conn,

        'Income'

    );

}

/*==========================================================
    EXPENSE ACCOUNTS
==========================================================*/

function expenseAccounts($conn)
{

    return getAccountsByType(

        $conn,

        'Expense'

    );

}
}