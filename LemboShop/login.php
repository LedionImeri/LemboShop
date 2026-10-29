<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$next = safe_next(input('next'));
if (Auth::check()) {
    redirect($next);
}

$formError = null;

if (is_post()) {
    $v = Validator::make($_POST, [
        'email'    => 'required|email|max:190',
        'password' => 'required|string|max:200',
    ]);

    if ($v->fails()) {
        Flash::withInput($v->errors(), $_POST);
        redirect('login.php?next=' . urlencode($next));
    }

    $email = $v->validated()['email'];
    // Two buckets: per IP+email (targeted guessing) and per IP (spraying many accounts)
    $keys = ['login' => client_ip() . '|' . $email, 'login_ip' => client_ip()];

    if (RateLimiter::tooMany('login', $keys['login']) || RateLimiter::tooMany('login_ip', $keys['login_ip'])) {
        $minutes = max(1, (int) ceil(RateLimiter::availableIn('login', $keys['login']) / 60));
        $formError = "Too many login attempts. Please try again in $minutes minute" . ($minutes === 1 ? '' : 's') . '.';
    } else {
        $user = Auth::attempt($email, (string) $_POST['password']);
        if ($user === null) {
            RateLimiter::hit('login', $keys['login']);
            RateLimiter::hit('login_ip', $keys['login_ip']);
            $formError = 'The email or password is incorrect.';
        } elseif ($user['status'] !== 'active') {
            $formError = 'This account has been suspended. Please contact support.';
        } else {
            RateLimiter::clear('login', $keys['login']);
            Auth::login($user);
            if (in_array($user['role'], Auth::ADMIN_ROLES, true)) {
                AuditLogger::log('auth.login', 'user', (int) $user['id']);
            }
            Flash::success('Welcome back, ' . explode(' ', $user['name'])[0] . '!');
            $default = in_array($user['role'], Auth::ADMIN_ROLES, true) ? 'admin/index.php' : 'index.php';
            redirect($next !== 'index.php' ? $next : $default);
        }
    }
    // Re-render with the error (keeps the email filled in)
    Flash::withInput([], ['email' => $email]);
}

View::render('auth/login', [
    'title'     => 'Log in',
    'next'      => $next,
    'formError' => $formError,
], 'auth');
