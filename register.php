<?php
require_once __DIR__ . '/includes/Auth.php';

if (Auth::user()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $nationality = trim($_POST['nationality'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        set_flash('error', 'Name, email, and password are required.');
    } elseif (strlen($password) < 6) {
        set_flash('error', 'Password must be at least 6 characters.');
    } elseif (!Auth::registerStudent($name, $email, $password, $nationality)) {
        set_flash('error', 'That email is already registered.');
    } else {
        set_flash('success', 'Account created. You can log in now.');
        redirect('index.php');
    }
}

$pageTitle = 'Student Registration';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-card">
    <h1>Student Registration</h1>
    <p>Create an applicant account for the international admission process.</p>

    <form action="register.php" method="post" class="grid" style="margin-top: 24px;">
        <div class="field">
            <label for="name">Full Name</label>
            <input id="name" name="name" required>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>
        </div>
        <div class="field">
            <label for="nationality">Nationality</label>
            <input id="nationality" name="nationality" placeholder="e.g. Malaysia">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required minlength="6">
        </div>
        <div class="actions">
            <button class="btn" type="submit">Create Account</button>
            <a class="btn secondary" href="index.php">Back to Login</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
