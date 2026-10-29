<?php
/**
 * Used for error pages. Must not depend on the database, which may be the reason for the error.
 * @var string $content
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? 'Error') . ' · ' . config('app.name', 'LemboShop')) ?></title>
<link rel="icon" href="<?= e(asset('images/logo-mark.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container header-main"><?php View::partial('logo'); ?></div>
</header>
<main id="main"><?= $content ?></main>
</body>
</html>
