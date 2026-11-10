<?php
require_once 'auth.php';
include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="row">

<!-- Ledger -->
<div class="col-md-4 mb-3">
<a href="ledger.php" class="text-decoration-none">
<div class="card shadow text-center p-4">
<i class="fa-solid fa-book fa-2x text-primary"></i>
<h5 class="mt-2">Ledger</h5>
</div>
</a>
</div>

<!-- Trial Balance -->
<div class="col-md-4 mb-3">
<a href="trial_balance.php" class="text-decoration-none">
<div class="card shadow text-center p-4">
<i class="fa-solid fa-scale-balanced fa-2x text-dark"></i>
<h5 class="mt-2">Trial Balance</h5>
</div>
</a>
</div>

<!-- Cash Book -->
<div class="col-md-4 mb-3">
<a href="cash_book.php" class="text-decoration-none">
<div class="card shadow text-center p-4">
<i class="fa-solid fa-cash-register fa-2x text-success"></i>
<h5 class="mt-2">Cash Book</h5>
</div>
</a>
</div>

<!-- Bank Book -->
<div class="col-md-4 mb-3">
<a href="bank_book.php" class="text-decoration-none">
<div class="card shadow text-center p-4">
<i class="fa-solid fa-building-columns fa-2x text-primary"></i>
<h5 class="mt-2">Bank Book</h5>
</div>
</a>
</div>

<!-- Income Statement -->
<div class="col-md-4 mb-3">
<a href="income_statement.php" class="text-decoration-none">
<div class="card shadow text-center p-4">
<i class="fa-solid fa-chart-line fa-2x text-success"></i>
<h5 class="mt-2">Income Statement</h5>
</div>
</a>
</div>

<!-- Balance Sheet -->
<div class="col-md-4 mb-3">
<a href="balance_sheet.php" class="text-decoration-none">
<div class="card shadow text-center p-4">
<i class="fa-solid fa-file-invoice fa-2x text-danger"></i>
<h5 class="mt-2">Balance Sheet</h5>
</div>
</a>
</div>

</div>

</div>

<?php include 'footer.php'; ?>