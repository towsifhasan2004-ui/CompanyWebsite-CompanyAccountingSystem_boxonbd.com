<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    require_once 'config/db.php';

    $company = $pdo->query("SELECT * FROM company LIMIT 1")->fetch();
    ?>

    <title><?= htmlspecialchars($company['company_name'] ?? 'Office Accounting System'); ?></title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- DataTables -->
    <link rel="stylesheet"
        href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
    @media print {

        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        table {
            font-size: 12px;
        }
    }
    </style>
</head>

<body class="bg-light">
<div class="container-fluid p-0">

<script>
window.onbeforeprint = function() {
    document.title = "Accounting Report Print";
};
</script>