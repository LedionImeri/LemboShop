<?php
/** @var array $categories */
$freeOver = Database::value('SELECT MIN(free_over) FROM shipping_methods WHERE is_active = 1 AND free_over IS NOT NULL');
?>
<footer class="site-footer">
    <div class="footer-perks">
        <div class="container">
            <ul>
                <li><?= icon('truck') ?><div><strong>Fast delivery</strong><?= $freeOver ? 'Free standard shipping over ' . e(money($freeOver)) : 'Tracked delivery across Europe' ?></div></li>
                <li><?= icon('refresh') ?><div><strong><?= e(setting('return_days', '30')) ?>-day returns</strong>Changed your mind? Send it back for free.</div></li>
                <li><?= icon('shield') ?><div><strong>Secure checkout</strong>Cash on delivery available on every order.</div></li>
                <li><?= icon('headset') ?><div><strong>Real support</strong><?= e(setting('support_hours', 'Mon–Fri')) ?></div></li>
            </ul>
        </div>
    </div>

    <div class="container footer-grid">
        <div>
            <?php View::partial('logo'); ?>
            <p style="margin-top:1rem;max-width:34ch"><?= e(setting('store_tagline', 'Everyday essentials, delivered.')) ?></p>
        </div>
        <div>
            <h3>Shop</h3>
            <ul class="footer-links">
                <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
                    <li><a href="<?= e(url('category.php?slug=' . urlencode($cat['slug']))) ?>"><?= e($cat['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h3>Help</h3>
            <ul class="footer-links">
                <li><a href="<?= e(url('contact.php')) ?>">Contact us</a></li>
                <li><a href="<?= e(url('page.php?p=shipping')) ?>">Shipping</a></li>
                <li><a href="<?= e(url('page.php?p=returns')) ?>">Returns</a></li>
                <li><a href="<?= e(url('page.php?p=faq')) ?>">FAQ</a></li>
            </ul>
        </div>
        <div>
            <h3>Company</h3>
            <ul class="footer-links">
                <li><a href="<?= e(url('page.php?p=about')) ?>">About LemboShop</a></li>
                <li><a href="<?= e(url('page.php?p=privacy')) ?>">Privacy policy</a></li>
                <li><a href="<?= e(url('page.php?p=terms')) ?>">Terms of service</a></li>
                <li><a href="<?= e(url('account/index.php')) ?>">My account</a></li>
            </ul>
        </div>
        <div>
            <h3>Stay in the loop</h3>
            <p>New arrivals and member-only deals. No spam, unsubscribe anytime.</p>
            <form class="newsletter-form" action="<?= e(url('api/newsletter.php')) ?>" method="post" data-newsletter>
                <?= csrf_field() ?>
                <label class="visually-hidden" for="nl-email">Email address</label>
                <input class="input" id="nl-email" type="email" name="email" placeholder="you@example.com" required maxlength="190" autocomplete="email">
                <button class="btn btn--primary" type="submit">Subscribe</button>
            </form>
            <ul class="footer-links footer-contact" style="margin-top:1.25rem">
                <li><?= icon('mail', 'icon icon--sm') ?><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li>
                <li><?= icon('phone', 'icon icon--sm') ?><span><?= e(setting('contact_phone')) ?></span></li>
                <li><?= icon('map-pin', 'icon icon--sm') ?><span><?= e(setting('contact_address')) ?></span></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <span>© <?= date('Y') ?> <?= e(setting('store_name', 'LemboShop')) ?>. Portfolio project — no real payments are processed.</span>
            <div class="pay-methods" aria-label="Payment options"><span>Cash on delivery</span><span>Demo payment</span></div>
        </div>
    </div>
</footer>
