<?php
/** @var string $content  Split-screen layout for login / register. */
?>
<!doctype html>
<html lang="en">
<head>
<?php View::partial('head', get_defined_vars()); ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="container header-main">
        <?php View::partial('logo'); ?>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('index.php')) ?>" style="margin-left:auto"><?= icon('chevron-left', 'icon icon--sm') ?>Back to shop</a>
    </div>
</header>
<main class="auth" id="main" tabindex="-1">
    <aside class="auth__aside" aria-hidden="true">
        <div>
            <h2>Everything you love, one checkout away.</h2>
            <p>Join LemboShop to track orders, save favourites and get member-only offers.</p>
            <ul class="auth__perks">
                <li><?= icon('truck') ?>Free standard shipping over <?= e(money(75)) ?></li>
                <li><?= icon('heart') ?>Wishlist synced across devices</li>
                <li><?= icon('package') ?>Live order tracking</li>
                <li><?= icon('shield') ?>Cash on delivery on every order</li>
            </ul>
        </div>
        <p class="auth__quote">Shop smarter with LemboBot, our AI assistant that finds products to match your needs and budget.</p>
    </aside>
    <div class="auth__main">
        <div class="auth__card"><?= $content ?></div>
    </div>
</main>
<?php View::partial('flash'); ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
