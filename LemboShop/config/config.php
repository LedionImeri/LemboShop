<?php
declare(strict_types=1);

/**
 * Application configuration. Values come from .env (see .env.example);
 * nothing secret is hard-coded here.
 */
return [
    'app' => [
        'name'            => Env::get('APP_NAME', 'LemboShop'),
        'env'             => Env::get('APP_ENV', 'production'),
        'url'             => rtrim((string) Env::get('APP_URL', ''), '/'),
        'timezone'        => Env::get('APP_TIMEZONE', 'UTC'),
        'currency'        => Env::get('APP_CURRENCY', 'EUR'),
        'currency_symbol' => Env::get('APP_CURRENCY_SYMBOL', '€'),
    ],
    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => (int) Env::get('DB_PORT', '3306'),
        'name' => Env::get('DB_NAME', 'lemboshop'),
        'user' => Env::get('DB_USER', 'root'),
        'pass' => Env::get('DB_PASS', ''),
    ],
    'session' => [
        'name'         => Env::get('SESSION_NAME', 'lemboshop_sid'),
        'idle_minutes' => (int) Env::get('SESSION_IDLE_MINUTES', '120'),
    ],
    'groq' => [
        'api_key' => Env::get('GROQ_API_KEY', ''),
        'model'   => Env::get('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'timeout' => (int) Env::get('GROQ_TIMEOUT', '20'),
    ],
    'uploads' => [
        'max_bytes' => 4 * 1024 * 1024,
        'mimes'     => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
    ],
    'rate_limits' => [
        // scope => [max attempts, window seconds]
        'login'    => [5, 900],   // per IP + email
        'login_ip' => [20, 900],  // per IP across all emails
        'register' => [5, 3600],
        'contact'  => [5, 3600],
        'chatbot'  => [20, 600],
    ],
];
