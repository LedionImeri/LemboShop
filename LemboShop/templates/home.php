<?php
/** @var array $categories */
?>
<section class="hero">
    <div class="container hero__inner">
        <div class="hero__copy">
            <span class="eyebrow"><?= icon('sparkle', 'icon icon--sm') ?>New season, new essentials</span>
            <h1 class="hero__title">Shop smarter.<br><span>Live better.</span></h1>
            <p class="hero__lead">Tech, home and style picked for quality and value. Fast delivery, <?= e(setting('return_days', '30')) ?>-day free returns, and LemboBot to help you choose.</p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="<?= e(url('products.php')) ?>">Start shopping<?= icon('arrow-right', 'icon icon--sm') ?></a>
                <a class="btn btn--outline btn--lg" href="<?= e(url('products.php?on_sale=1')) ?>">View deals</a>
            </div>
        </div>
        <div class="hero__art" aria-hidden="true">
            <div class="hero__blob"></div>
            <div class="hero__card hero__card--a"><?= icon('truck', 'icon icon--lg') ?><div><strong>Free shipping</strong><span>on orders over <?= e(money(75)) ?></span></div></div>
            <div class="hero__card hero__card--b"><?= icon('star', 'icon icon--lg icon--fill') ?><div><strong>4.8 / 5</strong><span>average customer rating</span></div></div>
            <div class="hero__card hero__card--c"><?= icon('chat', 'icon icon--lg') ?><div><strong>Ask LemboBot</strong><span>“Headphones under €150?”</span></div></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <h2>Shop by category</h2>
                <p class="muted">Find exactly what you need, faster.</p>
            </div>
            <a class="link-arrow" href="<?= e(url('products.php')) ?>">All products<?= icon('arrow-right', 'icon icon--sm') ?></a>
        </div>
        <ul class="cat-grid" role="list">
            <?php foreach ($categories as $cat): ?>
                <li>
                    <a class="cat-tile" href="<?= e(url('category.php?slug=' . urlencode($cat['slug']))) ?>">
                        <span class="cat-tile__icon"><?= icon($cat['icon'] ?: 'grid', 'icon icon--lg') ?></span>
                        <strong><?= e($cat['name']) ?></strong>
                        <span class="muted small"><?= count($cat['children']) ?> subcategories</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
