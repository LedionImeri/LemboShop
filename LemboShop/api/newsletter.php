<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

require_post();

$v = Validator::make($_POST, ['email' => 'required|email|max:190'], ['email' => 'Email']);
$ok = !$v->fails();
$message = $ok ? "You're subscribed! Watch your inbox for new arrivals and offers." : $v->errors()['email'];

if ($ok) {
    if (RateLimiter::tooMany('contact', client_ip())) {
        $ok = false;
        $message = 'Too many requests. Please try again later.';
    } else {
        RateLimiter::hit('contact', client_ip());
        // Same response whether or not the email was already subscribed (no enumeration)
        Database::query(
            "INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE status = 'subscribed'",
            [$v->validated()['email']]
        );
    }
}

if (is_ajax()) {
    json_response(['ok' => $ok, $ok ? 'message' : 'error' => $message], $ok ? 200 : 422);
}
$ok ? Flash::success($message) : Flash::error($message);
redirect(back_url());
