<?php
/** @var string $content */
$categories = CategoryRepository::tree();
?>
<!doctype html>
<html lang="en">
<head>
<?php View::partial('head', get_defined_vars()); ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?php View::partial('header', ['categories' => $categories]); ?>

<main class="site-main" id="main" tabindex="-1">
    <?= $content ?>
</main>

<?php View::partial('footer', ['categories' => $categories]); ?>
<?php View::partial('flash'); ?>
<?php View::partial('confirm-dialog'); ?>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach ($scripts ?? [] as $js): ?>
<script src="<?= e(asset('js/' . $js)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
