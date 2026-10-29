<?php
/** @var Throwable|null $exception  only set in development */
/** @var bool $dbDown */
?>
<section class="error-page">
    <div>
        <div class="error-page__code">500</div>
        <h1>Something went wrong</h1>
        <?php if ($dbDown): ?>
            <p>We can't reach the database right now. If you are running this locally, start MySQL in the XAMPP Control Panel and check that <code>database/lemboshop.sql</code> has been imported.</p>
        <?php else: ?>
            <p>An unexpected error occurred. It has been logged and we'll look into it. Please try again in a moment.</p>
        <?php endif; ?>
        <div class="error-page__actions">
            <a class="btn btn--primary" href="<?= e(url('index.php')) ?>">Go to homepage</a>
        </div>
        <?php if ($exception): ?>
            <pre class="error-debug"><?= e(get_class($exception) . ': ' . $exception->getMessage() . "\n" . $exception->getFile() . ':' . $exception->getLine() . "\n\n" . $exception->getTraceAsString()) ?></pre>
        <?php endif; ?>
    </div>
</section>
