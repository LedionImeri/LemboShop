<?php
/** @var string $next */
?>
<h1>Create your account</h1>
<p class="muted">It takes less than a minute. Your cart comes with you.</p>

<form method="post" action="<?= e(url('register.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">

    <div class="field">
        <label for="name">Full name</label>
        <input class="input" id="name" name="name" type="text" autocomplete="name" required maxlength="120"
               value="<?= e(old('name')) ?>" <?= field_aria('name') ?> autofocus>
        <?= field_error('name') ?>
    </div>

    <div class="field">
        <label for="email">Email address</label>
        <input class="input" id="email" name="email" type="email" autocomplete="email" required maxlength="190"
               value="<?= e(old('email')) ?>" <?= field_aria('email') ?>>
        <?= field_error('email') ?>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <div class="input-group">
            <input class="input" id="password" name="password" type="password" autocomplete="new-password" required minlength="8" maxlength="200"
                   aria-describedby="password-hint<?= error_for('password') ? ' password-error' : '' ?>" <?= error_for('password') ? 'aria-invalid="true"' : '' ?>>
            <button class="input-action" type="button" aria-label="Show password" aria-pressed="false" aria-controls="password" data-toggle-password><?= icon('eye') ?></button>
        </div>
        <div class="strength" data-strength-for="password" data-score="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <p class="hint" id="password-hint">At least 8 characters with a letter and a number. <span data-strength-label aria-live="polite"></span></p>
        <?= field_error('password') ?>
    </div>

    <div class="field">
        <label for="password_confirm">Confirm password</label>
        <input class="input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required maxlength="200" <?= field_aria('password_confirm') ?>>
        <?= field_error('password_confirm') ?>
    </div>

    <div class="field">
        <label class="check">
            <input type="checkbox" name="terms" value="1" required <?= old('terms') ? 'checked' : '' ?> <?= field_aria('terms') ?>>
            <span>I agree to the <a href="<?= e(url('page.php?p=terms')) ?>" target="_blank" rel="noopener">terms of service</a> and <a href="<?= e(url('page.php?p=privacy')) ?>" target="_blank" rel="noopener">privacy policy</a>.</span>
        </label>
        <?= field_error('terms') ?>
        <label class="check">
            <input type="checkbox" name="newsletter" value="1" <?= old('newsletter') ? 'checked' : '' ?>>
            <span>Email me about new arrivals and offers.</span>
        </label>
    </div>

    <button class="btn btn--primary btn--lg btn--block" type="submit">Create account</button>
</form>

<p class="auth__switch">Already have an account? <a href="<?= e(url('login.php' . ($next !== 'index.php' ? '?next=' . urlencode($next) : ''))) ?>">Log in</a></p>
