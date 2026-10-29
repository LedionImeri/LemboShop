<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

require_post();
Auth::logout();
Session::start();
Flash::success('You have been logged out.');
redirect('index.php');
