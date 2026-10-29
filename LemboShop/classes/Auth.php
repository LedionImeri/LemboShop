<?php
declare(strict_types=1);

/**
 * Session-based authentication and role checks.
 * The user row is reloaded on every request so suspensions and role changes apply immediately.
 */
final class Auth
{
    public const ROLES = ['customer', 'staff', 'admin'];
    /** Roles allowed into /admin. Staff gets a reduced menu (see Auth::can). */
    public const ADMIN_ROLES = ['staff', 'admin'];

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = $_SESSION['user_id'] ?? null;
            if ($id) {
                $user = Database::one(
                    'SELECT id, name, email, phone, role, status, created_at, last_login_at FROM users WHERE id = ?',
                    [$id]
                );
                if ($user && $user['status'] === 'active') {
                    self::$user = $user;
                } else {
                    // Account removed or suspended since login
                    unset($_SESSION['user_id']);
                }
            }
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $user = self::user();
        return $user !== null && in_array($user['role'], $roles, true);
    }

    public static function isAdmin(): bool
    {
        return self::hasRole(...self::ADMIN_ROLES);
    }

    /**
     * Permission map for admin features. Admins can do everything;
     * staff handles day-to-day store operations only.
     */
    public static function can(string $permission): bool
    {
        if (self::hasRole('admin')) {
            return true;
        }
        $staff = ['dashboard', 'products', 'categories', 'orders', 'inventory', 'reviews', 'messages', 'customers.view'];
        return self::hasRole('staff') && in_array($permission, $staff, true);
    }

    /**
     * Verify credentials. Returns the user row or null. Uses a dummy hash when the
     * email is unknown so response time does not reveal which emails exist.
     */
    public static function attempt(string $email, string $password): ?array
    {
        $user = Database::one('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
        $hash = $user['password_hash'] ?? '$2y$12$C6UzMDM.H6dfI/f/IKcEeO3U8k4pTjQOfvLwB0V9z7bZ4sPZ0Ue7a';
        $ok = password_verify($password, $hash);
        if (!$user || !$ok) {
            return null;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        return $user;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Csrf::rotate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['login_at'] = time();
        self::$loaded = false;
        Database::query('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = ?', [$user['id']]);

        foreach (self::$loginHooks as $hook) {
            $hook((int) $user['id']);
        }
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
        self::$loaded = true;
    }

    /** @var callable[] functions called with the user id right after login (e.g. cart merge) */
    private static array $loginHooks = [];

    public static function onLogin(callable $hook): void
    {
        self::$loginHooks[] = $hook;
    }

    /** Redirect guests to the login page, remembering where they wanted to go. */
    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            if (is_ajax()) {
                json_response(['ok' => false, 'error' => 'Please log in to continue.', 'login' => true], 401);
            }
            Flash::info('Please log in to continue.');
            redirect('login.php?next=' . urlencode(current_relative_url()));
        }
        return $user;
    }

    /** Server-side gate for admin/staff pages. */
    public static function requireAdmin(?string $permission = null): array
    {
        $user = self::requireLogin();
        if (!self::isAdmin() || ($permission !== null && !self::can($permission))) {
            abort(403);
        }
        return $user;
    }
}
