<?php
declare(strict_types=1);

/**
 * Loaded first by every entry point: config, autoloading, error handling,
 * session and automatic CSRF verification for POST requests.
 */

define('ROOT_PATH', dirname(__DIR__));
define('IS_CLI', PHP_SAPI === 'cli');

spl_autoload_register(static function (string $class): void {
    foreach (['classes', 'services'] as $dir) {
        $file = ROOT_PATH . "/$dir/$class.php";
        if (preg_match('/^[A-Za-z0-9_]+$/', $class) && is_file($file)) {
            require $file;
            return;
        }
    }
});

require ROOT_PATH . '/includes/helpers.php';

Env::load(ROOT_PATH . '/.env');
$GLOBALS['__config'] = require ROOT_PATH . '/config/config.php';

date_default_timezone_set(config('app.timezone'));
mb_internal_encoding('UTF-8');

// Errors: always log, only display in development
error_reporting(E_ALL);
ini_set('display_errors', is_debug() ? '1' : '0');
ini_set('log_errors', '1');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e): void {
    Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
    if (IS_CLI) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    if (is_ajax()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
        return;
    }
    $dbDown = $e instanceof PDOException;
    try {
        View::render('errors/500', [
            'title'     => 'Something went wrong',
            'exception' => is_debug() ? $e : null,
            'dbDown'    => $dbDown,
        ], 'minimal');
    } catch (Throwable) {
        echo '<h1>Something went wrong</h1><p>Please try again later.</p>';
    }
});

if (!IS_CLI) {
    Session::start();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !Csrf::isValid()) {
        if (is_ajax()) {
            json_response(['ok' => false, 'error' => 'Your session expired. Please refresh the page.'], 419);
        }
        Flash::error('Your session expired. Please try again.');
        redirect(back_url());
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
