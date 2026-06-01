<?php
$currentUser = Auth::user();
$flash = get_flash();
$pageTitle = $pageTitle ?? 'WKU Admission';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | WKU Admission</title>
    <link rel="stylesheet" href="assets/styles.css?v=<?= filemtime(__DIR__ . '/../assets/styles.css') ?>">
</head>
<body>
<?php if ($currentUser): ?>
    <header class="topbar">
        <a class="brand" href="index.php">
            <span class="brand-mark">WKU</span>
            <span>International Admission</span>
        </a>
        <nav class="nav">
            <?php if ($currentUser['role'] === 'student'): ?>
                <a href="student_dashboard.php">Dashboard</a>
                <a href="application_form.php">Application</a>
                <a href="upload_document.php">Documents</a>
            <?php elseif ($currentUser['role'] === 'officer'): ?>
                <a href="officer_dashboard.php">Review</a>
            <?php elseif ($currentUser['role'] === 'admin'): ?>
                <a href="admin_dashboard.php">Admin</a>
            <?php endif; ?>
        </nav>
        <div class="account">
            <span><?= e($currentUser['name']) ?></span>
            <a class="btn ghost" href="logout.php">Logout</a>
        </div>
    </header>
<?php endif; ?>

<main class="<?= $currentUser ? 'shell' : 'auth-shell' ?>">
    <?php if ($flash): ?>
        <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

