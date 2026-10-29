<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

View::render('home', [
    'categories' => CategoryRepository::tree(),
    'styles'     => ['store.css'],
]);
