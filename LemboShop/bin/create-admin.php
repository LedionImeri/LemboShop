<?php
declare(strict_types=1);

/**
 * Create (or promote) an administrator account from the command line.
 *
 *   C:\xampp\php\php.exe bin\create-admin.php
 *
 * Runs only from the CLI, so there is never a public setup page or default password.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/bootstrap.php';

function prompt(string $label, bool $hidden = false): string
{
    echo $label;
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
        $value = trim((string) fgets(STDIN));
        system('stty echo');
        echo PHP_EOL;
        return $value;
    }
    // Windows consoles cannot hide input from PHP; warn instead of failing
    return trim((string) fgets(STDIN));
}

echo "LemboShop — create administrator\n";
echo str_repeat('-', 34) . "\n";
if (DIRECTORY_SEPARATOR === '\\') {
    echo "Note: the password will be visible while typing in this console.\n\n";
}

$name = prompt('Full name: ');
$email = mb_strtolower(prompt('Email: '));
$password = prompt('Password (min. 12 characters): ', true);
$confirm = prompt('Confirm password: ', true);

$v = Validator::make(
    ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirm' => $confirm],
    [
        'name'             => 'required|min:2|max:120',
        'email'            => 'required|email|max:190',
        'password'         => 'required|min:12|max:200|password',
        'password_confirm' => 'required|same:password',
    ]
);
if ($v->fails()) {
    foreach ($v->errors() as $error) {
        fwrite(STDERR, "  ✗ $error\n");
    }
    exit(1);
}

$existing = Database::one('SELECT id, role FROM users WHERE email = ?', [$email]);
$hash = password_hash($password, PASSWORD_DEFAULT);

if ($existing) {
    echo "An account with this email exists (role: {$existing['role']}). Promote it to admin and reset its password? [y/N] ";
    if (strtolower(trim((string) fgets(STDIN))) !== 'y') {
        echo "Aborted.\n";
        exit(0);
    }
    Database::query("UPDATE users SET role = 'admin', status = 'active', password_hash = ?, name = ? WHERE id = ?", [$hash, $name, $existing['id']]);
    $id = (int) $existing['id'];
    $action = 'user.promoted_admin';
} else {
    $id = Database::insert("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')", [$name, $email, $hash]);
    $action = 'user.created_admin';
}

AuditLogger::log($action, 'user', $id, ['via' => 'cli']);
echo "\n✓ Administrator ready: $email\n  Log in at " . (config('app.url') ?: 'http://localhost/LemboShop') . "/login.php\n";
