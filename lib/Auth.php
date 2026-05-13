<?php

namespace FlexCore\Lib;

/**
 * Auth — session and authentication management.
 * Compatible with PHP 7.4+
 *
 * Registered in the Container as a singleton in config/container.php.
 * Global alias \Auth preserved in bootstrap.php for compatibility.
 */
class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public static function is(string $role): bool
    {
        return ($_SESSION['user']['role'] ?? '') === $role;
    }

    public static function require(array $roles = []): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }
        if ($roles && !in_array($_SESSION['user']['role'] ?? '', $roles)) {
            http_response_code(403);
            include BASE . '/app/views/errors/403.php';
            exit;
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user']    = $user;
        DB::run('UPDATE users SET last_login=NOW() WHERE id=?', [$user['id']]);
    }

    public static function logout(): void
    {
        session_destroy();
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = DB::one('SELECT * FROM users WHERE email=? AND active=1', [trim($email)]);
        if (!$user || !password_verify($password, $user['password'])) return false;
        self::login($user);
        return true;
    }
}
