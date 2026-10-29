<section class="error-page">
    <div>
        <div class="error-page__code">404</div>
        <h1>We couldn't find that page</h1>
        <p>The link may be broken, or the product may no longer be available. Try searching instead.</p>
        <form class="header-search" style="display:block;margin:1.5rem auto 0" action="<?= e(url('products.php')) ?>" method="get" role="search">
            <label class="visually-hidden" for="q404">Search products</label>
            <?= icon('search') ?>
            <input class="input" id="q404" type="search" name="q" placeholder="Search products" maxlength="100">
        </form>
        <div class="error-page__actions">
            <a class="btn btn--primary" href="<?= e(url('index.php')) ?>">Go to homepage</a>
            <a class="btn btn--outline" href="<?= e(url('products.php')) ?>">Browse products</a>
        </div>
    </div>
</section>
