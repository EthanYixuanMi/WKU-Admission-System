<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

final class Auth
{
    public static function login(string $email, string $password): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT user_id, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['role'] = $user['role'];
        return true;
    }

    public static function registerStudent(string $name, string $email, string $password, string $nationality): bool
    {
        $db = Database::connection();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student';

        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password_hash, role, nationality) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssss', $name, $email, $hash, $role, $nationality);

        try {
            return $stmt->execute();
        } catch (mysqli_sql_exception $exception) {
            if ((int) $exception->getCode() === 1062) {
                return false;
            }
            throw $exception;
        }
    }

    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT user_id, name, email, role, phone, nationality FROM users WHERE user_id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if (!$user) {
            redirect('index.php');
        }

        return $user;
    }

    public static function requireRole(array|string $roles): array
    {
        $user = self::requireLogin();
        $allowed = is_array($roles) ? $roles : [$roles];

        if (!in_array($user['role'], $allowed, true)) {
            set_flash('error', 'You do not have permission to access that page.');
            self::redirectByRole($user['role']);
        }

        return $user;
    }

    public static function redirectByRole(string $role): never
    {
        if ($role === 'admin') {
            redirect('admin_dashboard.php');
        }

        if ($role === 'officer') {
            redirect('officer_dashboard.php');
        }

        redirect('student_dashboard.php');
    }

    public static function logout(): never
    {
        $_SESSION = [];
        session_destroy();
        redirect('index.php');
    }
}
