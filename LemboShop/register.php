<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$next = safe_next(input('next'));
if (Auth::check()) {
    redirect($next);
}

if (is_post()) {
    $back = 'register.php' . ($next !== 'index.php' ? '?next=' . urlencode($next) : '');

    if (RateLimiter::tooMany('register', client_ip())) {
        Flash::error('Too many sign-ups from your network. Please try again later.');
        redirect($back);
    }

    $v = Validator::make($_POST, [
        'name'             => 'required|string|min:2|max:120',
        'email'            => 'required|email|max:190',
        'password'         => 'required|string|min:8|max:200|password',
        'password_confirm' => 'required|same:password',
        'terms'            => 'required|bool',
        'newsletter'       => 'bool',
    ], ['password_confirm' => 'Password confirmation', 'terms' => 'Accepting the terms']);

    $data = $v->validated();
    if (!$v->fails() && Database::value('SELECT 1 FROM users WHERE email = ?', [$data['email']])) {
        $v->addError('email', 'An account with this email already exists. Try logging in instead.');
    }
    if ($v->fails()) {
        Flash::withInput($v->errors(), $_POST);
        redirect($back);
    }

    RateLimiter::hit('register', client_ip());

    $userId = Database::insert(
        'INSERT INTO users (name, email, password_hash, newsletter) VALUES (?, ?, ?, ?)',
        [$data['name'], $data['email'], password_hash((string) $_POST['password'], PASSWORD_DEFAULT), (int) $data['newsletter']]
    );
    if ($data['newsletter']) {
        Database::query(
            "INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE status = 'subscribed'",
            [$data['email']]
        );
    }
    Database::query(
        'INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, ?, ?, ?, ?)',
        [$userId, 'welcome', 'Welcome to LemboShop!', 'Use code WELCOME10 for 10% off your first order.', 'products.php']
    );
    Logger::mail($data['email'], 'Welcome to LemboShop', "Hi {$data['name']},\n\nYour account is ready. Use code WELCOME10 for 10% off your first order.");

    Auth::login(Database::one('SELECT * FROM users WHERE id = ?', [$userId]));
    Flash::success('Your account has been created. Welcome to LemboShop!');
    redirect($next);
}

View::render('auth/register', [
    'title' => 'Create account',
    'next'  => $next,
], 'auth');
