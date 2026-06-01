<?php
require_once __DIR__ . '/includes/Auth.php';

$user = Auth::user();
if ($user) {
    Auth::redirectByRole($user['role']);
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-card">
    <h1>WKU Admission Portal</h1>
    <p>International Online Admission Management System</p>

    <form action="login.php" method="post" class="grid" style="margin-top: 24px;">
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required placeholder="student@wku.edu">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required placeholder="Enter password">
        </div>
        <button class="btn" type="submit">Login</button>
    </form>

    <p style="margin-top: 18px;">New international applicant? <a href="register.php">Create student account</a></p>
    <p class="muted" style="margin-top: 14px;">Demo: student@wku.edu / student123, officer@wku.edu / officer123, admin@wku.edu / admin123</p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
