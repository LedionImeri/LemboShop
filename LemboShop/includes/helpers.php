<?php
declare(strict_types=1);

/* ---------- Config ---------- */

/** Read config with dot notation: config('app.name'). */
function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function is_debug(): bool
{
    return config('app.env') === 'development';
}

/** Store setting from the `settings` table, cached per request. */
function setting(string $key, string $default = ''): string
{
    static $settings = null;
    if ($settings === null) {
        try {
            $settings = array_column(Database::all('SELECT `key`, `value` FROM settings'), 'value', 'key');
        } catch (PDOException) {
            $settings = [];
        }
    }
    return (string) ($settings[$key] ?? $default);
}

/* ---------- Output ---------- */

/** Escape for HTML output. Use for every dynamic value in templates. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(string|int|float|null $amount): string
{
    $amount = (float) ($amount ?? 0);
    $sign = $amount < 0 ? '-' : '';
    return $sign . config('app.currency_symbol', '€') . number_format(abs($amount), 2, '.', ',');
}

/** Format a UTC database timestamp in the store's timezone. */
function format_date(?string $utc, string $format = 'M j, Y'): string
{
    if (!$utc) {
        return '';
    }
    $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    return $date->setTimezone(new DateTimeZone(config('app.timezone')))->format($format);
}

function str_limit(string $text, int $length = 120): string
{
    return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length - 1)) . '…' : $text;
}

function slugify(string $text): string
{
    // intl is not enabled in every XAMPP install, so fall back to iconv
    $ascii = function_exists('transliterator_transliterate')
        ? transliterator_transliterate('Any-Latin; Latin-ASCII', $text)
        : @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = mb_strtolower($ascii ?: $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-');
}

/** Inline SVG icon from the sprite in assets/images/icons.svg. */
function icon(string $name, string $class = 'icon'): string
{
    return '<svg class="' . e($class) . '" aria-hidden="true" focusable="false"><use href="'
        . e(asset('images/icons.svg')) . '#i-' . e($name) . '"></use></svg>';
}

/** Header badge counts. */
function store_counts(): array
{
    return [
        'cart'     => class_exists('CartService') ? CartService::count() : 0,
        'wishlist' => class_exists('WishlistService') ? WishlistService::count() : 0,
    ];
}

function csrf_field(): string
{
    return Csrf::field();
}

/* ---------- Forms ---------- */

function old(string $key, string $default = ''): string
{
    $old = Flash::old();
    return isset($old[$key]) && is_scalar($old[$key]) ? (string) $old[$key] : $default;
}

function error_for(string $key): ?string
{
    return Flash::errors()[$key] ?? null;
}

/** Render a field error message with an id that aria-describedby can reference. */
function field_error(string $key): string
{
    $msg = error_for($key);
    return $msg ? '<p class="field-error" id="' . e($key) . '-error">' . e($msg) . '</p>' : '';
}

/** aria attributes for an input that may have an error. */
function field_aria(string $key): string
{
    return error_for($key) ? 'aria-invalid="true" aria-describedby="' . e($key) . '-error"' : '';
}

/* ---------- URLs ---------- */

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443);
}

/** URL path prefix of the app, e.g. "/LemboShop" (no trailing slash). */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configured = config('app.url');
    if ($configured) {
        return $base = rtrim((string) parse_url($configured, PHP_URL_PATH), '/');
    }
    $root = str_replace('\\', '/', (string) realpath(ROOT_PATH));
    $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($docRoot !== '' && stripos($root, $docRoot) === 0) {
        return $base = rtrim(substr($root, strlen($docRoot)), '/');
    }
    return $base = '';
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT_PATH . '/assets/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $version;
}

/** Current URL relative to the app root, e.g. "products.php?page=2". */
function current_relative_url(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    return ltrim($uri, '/');
}

function current_page(): string
{
    return basename(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '');
}

/**
 * Only allow redirects to paths inside this app (prevents open redirects via ?next=).
 */
function safe_next(?string $next, string $fallback = 'index.php'): string
{
    $next = (string) $next;
    if ($next === '' || preg_match('#^(?:[a-z][a-z0-9+.-]*:|//|\\\\)#i', $next) || str_contains($next, "\n")) {
        return $fallback;
    }
    return ltrim($next, '/');
}

function back_url(string $fallback = 'index.php'): string
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === explode(':', $host)[0]) {
        $path = (string) parse_url($ref, PHP_URL_PATH);
        $query = parse_url($ref, PHP_URL_QUERY);
        $base = base_path();
        if ($base === '' || str_starts_with($path, $base . '/')) {
            return ltrim(substr($path, strlen($base)), '/') . ($query ? '?' . $query : '');
        }
    }
    return $fallback;
}

/** Build a query string from the current GET params with overrides (null removes a key). */
function query_with(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
    return $params ? '?' . http_build_query($params) : '';
}

/* ---------- Responses ---------- */

function redirect(string $path): never
{
    $target = preg_match('#^https?://#i', $path) ? $path : url($path);
    header('Location: ' . $target, true, 303);
    exit;
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Reject anything but POST for state-changing endpoints. */
function require_post(): void
{
    if (!is_post()) {
        header('Allow: POST');
        abort(405);
    }
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function abort(int $status): never
{
    http_response_code($status);
    if (is_ajax()) {
        $messages = [403 => 'You do not have permission to do that.', 404 => 'Not found.', 405 => 'Method not allowed.'];
        json_response(['ok' => false, 'error' => $messages[$status] ?? 'Request failed.'], $status);
    }
    $template = in_array($status, [403, 404, 405], true) ? (string) $status : '404';
    View::render('errors/' . $template, ['title' => ['403' => 'Access denied', '404' => 'Page not found', '405' => 'Method not allowed'][$template]], 'minimal');
    exit;
}

/* ---------- Request ---------- */

function input(string $key, mixed $default = null): mixed
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

function client_ip(): string
{
    // REMOTE_ADDR only: X-Forwarded-For is client-controlled without a trusted proxy
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ip_hash(): string
{
    return hash('sha256', client_ip() . '|' . config('app.name'));
}
