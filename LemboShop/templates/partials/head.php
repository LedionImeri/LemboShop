<?php
/** @var string|null $title */
/** @var string|null $description */
$storeName = setting('store_name', config('app.name'));
$fullTitle = isset($title) && $title !== '' ? $title . ' · ' . $storeName : $storeName . ' — ' . setting('store_tagline', 'Online store');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($description ?? setting('store_tagline', 'Shop online at LemboShop.')) ?>">
<meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
<meta name="base-url" content="<?= e(url('')) ?>">
<meta name="icon-sprite" content="<?= e(asset('images/icons.svg')) ?>">
<meta name="theme-color" content="#0d1321">
<link rel="icon" href="<?= e(asset('images/logo-mark.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
<?php foreach ($styles ?? [] as $css): ?>
<link rel="stylesheet" href="<?= e(asset('css/' . $css)) ?>">
<?php endforeach; ?>
