<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*=========================================
CHECK EMPLOYEE ID
=========================================*/

if(!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: employee_list.php");
    exit();
}

$id = (int)$_GET['id'];

/*=========================================
CHECK EMPLOYEE EXISTS
=========================================*/

$stmt = mysqli_prepare($conn,"
SELECT
id,
employee_name,
status
FROM employees
WHERE id=?
LIMIT 1
");

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0)
{
    $_SESSION['error']="Employee not found.";
    header("Location: employee_list.php");
    exit();
}

$employee = mysqli_fetch_assoc($result);

/*=========================================
ALREADY INACTIVE
=========================================*/

if($employee['status']=="Inactive")
{
    $_SESSION['error']="Employee is already inactive.";
    header("Location: employee_list.php");
    exit();
}

/*=========================================
SOFT DELETE
=========================================*/

$stmt = mysqli_prepare($conn,"
UPDATE employees
SET status='Inactive'
WHERE id=?
");

mysqli_stmt_bind_param($stmt,"i",$id);

if(mysqli_stmt_execute($stmt))
{

    $_SESSION['success']="Employee deactivated successfully.";

}
else
{

    $_SESSION['error']="Unable to deactivate employee.";

}

header("Location: employee_list.php");

exit();

?>