<?php
require_once __DIR__ . '/includes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    set_flash('error', 'Email and password are required.');
    redirect('index.php');
}

if (!Auth::login($email, $password)) {
    set_flash('error', 'Invalid email or password.');
    redirect('index.php');
}

$user = Auth::user();
Auth::redirectByRole($user['role']);
