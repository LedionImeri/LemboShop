<?php
/** @var string $next */
/** @var string|null $formError */
?>
<h1>Welcome back</h1>
<p class="muted">Log in to view orders, your wishlist and saved addresses.</p>

<?php if ($formError): ?>
    <div class="alert alert--error" role="alert"><?= icon('alert') ?><span><?= e($formError) ?></span></div>
<?php endif; ?>

<form method="post" action="<?= e(url('login.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">

    <div class="field">
        <label for="email">Email address</label>
        <input class="input" id="email" name="email" type="email" autocomplete="email" required maxlength="190"
               value="<?= e(old('email')) ?>" <?= field_aria('email') ?> autofocus>
        <?= field_error('email') ?>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <div class="input-group">
            <input class="input" id="password" name="password" type="password" autocomplete="current-password" required maxlength="200" <?= field_aria('password') ?>>
            <button class="input-action" type="button" aria-label="Show password" aria-pressed="false" aria-controls="password" data-toggle-password><?= icon('eye') ?></button>
        </div>
        <?= field_error('password') ?>
    </div>

    <button class="btn btn--primary btn--lg btn--block" type="submit">Log in</button>
</form>

<p class="auth__switch">New to LemboShop? <a href="<?= e(url('register.php' . ($next !== 'index.php' ? '?next=' . urlencode($next) : ''))) ?>">Create an account</a></p>
