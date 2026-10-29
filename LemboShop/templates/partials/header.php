<?php
/** @var array $categories */
$user = Auth::user();
$counts = store_counts();
$query = is_string($_GET['q'] ?? null) ? $_GET['q'] : '';
$announcement = setting('announcement');
?>
<?php if ($announcement !== ''): ?>
<div class="announce">
    <div class="container"><?= icon('truck', 'icon icon--sm') ?><span><?= e($announcement) ?></span></div>
</div>
<?php endif; ?>

<header class="site-header">
    <div class="container header-main">
        <button class="icon-btn nav-toggle" type="button" aria-label="Open menu" aria-controls="mobile-drawer" aria-expanded="false" data-drawer-open>
            <?= icon('menu') ?>
        </button>

        <?php View::partial('logo'); ?>

        <div class="header-search" role="search">
            <form action="<?= e(url('products.php')) ?>" method="get">
                <label class="visually-hidden" for="header-q">Search products</label>
                <?= icon('search') ?>
                <input class="input" id="header-q" name="q" type="search" value="<?= e($query) ?>"
                       placeholder="Search products, brands and categories" autocomplete="off" maxlength="100">
            </form>
        </div>

        <nav class="header-actions" aria-label="Shortcuts">
            <button class="icon-btn only-mobile-search" type="button" aria-label="Search" data-search-open>
                <?= icon('search') ?>
            </button>

            <?php if ($user): ?>
                <div class="dropdown" data-dropdown>
                    <button class="icon-btn" type="button" aria-label="Account menu" aria-haspopup="true" aria-expanded="false" data-dropdown-toggle>
                        <?= icon('user') ?>
                    </button>
                    <div class="dropdown__menu" role="menu">
                        <div class="dropdown__head">
                            <strong><?= e($user['name']) ?></strong>
                            <span><?= e($user['email']) ?></span>
                        </div>
                        <a class="dropdown__item" role="menuitem" href="<?= e(url('account/index.php')) ?>"><?= icon('user') ?>My account</a>
                        <a class="dropdown__item" role="menuitem" href="<?= e(url('account/orders.php')) ?>"><?= icon('package') ?>Orders</a>
                        <a class="dropdown__item" role="menuitem" href="<?= e(url('wishlist.php')) ?>"><?= icon('heart') ?>Wishlist</a>
                        <?php if (Auth::isAdmin()): ?>
                            <a class="dropdown__item" role="menuitem" href="<?= e(url('admin/index.php')) ?>"><?= icon('dashboard') ?>Admin dashboard</a>
                        <?php endif; ?>
                        <form action="<?= e(url('logout.php')) ?>" method="post">
                            <?= csrf_field() ?>
                            <button class="dropdown__item" role="menuitem" type="submit"><?= icon('log-out') ?>Log out</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a class="icon-btn" href="<?= e(url('login.php')) ?>" aria-label="Log in"><?= icon('user') ?></a>
            <?php endif; ?>

            <a class="icon-btn" href="<?= e(url('wishlist.php')) ?>" aria-label="Wishlist (<?= (int) $counts['wishlist'] ?> items)">
                <?= icon('heart') ?>
                <span class="badge-count" data-wishlist-count data-count="<?= (int) $counts['wishlist'] ?>"><?= (int) $counts['wishlist'] ?></span>
            </a>
            <a class="icon-btn" href="<?= e(url('cart.php')) ?>" aria-label="Cart (<?= (int) $counts['cart'] ?> items)">
                <?= icon('bag') ?>
                <span class="badge-count" data-cart-count data-count="<?= (int) $counts['cart'] ?>"><?= (int) $counts['cart'] ?></span>
            </a>
        </nav>
    </div>

    <nav class="cat-nav" aria-label="Categories">
        <div class="container">
            <ul class="cat-nav__list">
                <li class="cat-nav__item"><a class="cat-nav__link" href="<?= e(url('products.php')) ?>"><?= icon('grid', 'icon icon--sm') ?>All products</a></li>
                <?php foreach (array_slice($categories, 0, 7) as $cat): ?>
                    <li class="cat-nav__item">
                        <a class="cat-nav__link" href="<?= e(url('category.php?slug=' . urlencode($cat['slug']))) ?>"><?= e($cat['name']) ?></a>
                        <?php if ($cat['children']): ?>
                            <div class="mega">
                                <div class="container mega__inner">
                                    <div class="mega__intro">
                                        <h3><?= e($cat['name']) ?></h3>
                                        <p class="muted small"><?= e($cat['description'] ?? '') ?></p>
                                        <a class="btn btn--outline btn--sm" href="<?= e(url('category.php?slug=' . urlencode($cat['slug']))) ?>">Shop all <?= e($cat['name']) ?></a>
                                    </div>
                                    <ul class="mega__links">
                                        <?php foreach ($cat['children'] as $child): ?>
                                            <li><a href="<?= e(url('category.php?slug=' . urlencode($child['slug']))) ?>"><?= e($child['name']) ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <li class="cat-nav__item cat-nav__item--right">
                    <a class="cat-nav__link cat-nav__link--deals" href="<?= e(url('products.php?on_sale=1')) ?>"><?= icon('tag', 'icon icon--sm') ?>Deals</a>
                </li>
            </ul>
        </div>
    </nav>
</header>

<!-- Mobile navigation drawer -->
<div class="drawer" id="mobile-drawer" data-drawer aria-hidden="true">
    <div class="drawer__backdrop" data-drawer-close></div>
    <div class="drawer__panel" role="dialog" aria-modal="true" aria-label="Menu">
        <div class="drawer__head">
            <?php View::partial('logo'); ?>
            <button class="icon-btn" type="button" aria-label="Close menu" data-drawer-close><?= icon('x') ?></button>
        </div>
        <div class="drawer__section">
            <?php if ($user): ?>
                <p class="drawer__title">Hi, <?= e(explode(' ', $user['name'])[0]) ?></p>
                <a class="drawer__link" href="<?= e(url('account/index.php')) ?>"><?= icon('user') ?>My account</a>
                <a class="drawer__link" href="<?= e(url('account/orders.php')) ?>"><?= icon('package') ?>Orders</a>
                <?php if (Auth::isAdmin()): ?>
                    <a class="drawer__link" href="<?= e(url('admin/index.php')) ?>"><?= icon('dashboard') ?>Admin dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <a class="btn btn--primary btn--block" href="<?= e(url('login.php')) ?>">Log in</a>
                <p class="small muted text-center" style="margin:.75rem 0 0">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
            <?php endif; ?>
        </div>
        <div class="drawer__section">
            <p class="drawer__title">Shop</p>
            <a class="drawer__link" href="<?= e(url('products.php')) ?>"><?= icon('grid') ?>All products</a>
            <a class="drawer__link" href="<?= e(url('products.php?on_sale=1')) ?>"><?= icon('tag') ?>Deals</a>
            <?php foreach ($categories as $cat): ?>
                <details>
                    <summary><?= e($cat['name']) ?><?= icon('chevron-down', 'icon icon--sm') ?></summary>
                    <ul class="drawer__links">
                        <li><a href="<?= e(url('category.php?slug=' . urlencode($cat['slug']))) ?>">All <?= e($cat['name']) ?></a></li>
                        <?php foreach ($cat['children'] as $child): ?>
                            <li><a href="<?= e(url('category.php?slug=' . urlencode($child['slug']))) ?>"><?= e($child['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endforeach; ?>
        </div>
        <div class="drawer__section">
            <a class="drawer__link" href="<?= e(url('contact.php')) ?>"><?= icon('headset') ?>Help &amp; contact</a>
            <?php if ($user): ?>
                <form action="<?= e(url('logout.php')) ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="drawer__link btn--ghost" type="submit" style="border:0;background:none;width:100%"><?= icon('log-out') ?>Log out</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Search overlay (mobile) -->
<div class="search-overlay" data-search-overlay aria-hidden="true">
    <div class="search-overlay__backdrop" data-search-close></div>
    <div class="search-overlay__panel" role="dialog" aria-modal="true" aria-label="Search">
        <div class="container">
            <form action="<?= e(url('products.php')) ?>" method="get" role="search">
                <label class="visually-hidden" for="overlay-q">Search products</label>
                <input class="input" id="overlay-q" name="q" type="search" placeholder="What are you looking for?" autocomplete="off" maxlength="100" value="<?= e($query) ?>">
                <button class="icon-btn" type="button" aria-label="Close search" data-search-close><?= icon('x') ?></button>
            </form>
            <div class="search-suggest" data-search-suggest aria-live="polite"></div>
        </div>
    </div>
</div>
