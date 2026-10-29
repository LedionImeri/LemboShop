<?php
// Flash messages render as toasts. Without JavaScript they stay visible as static notices.
$messages = Flash::messages();
$icons = ['success' => 'check-circle', 'error' => 'alert', 'info' => 'info'];
?>
<div class="toast-region" aria-live="polite" aria-atomic="false" data-toast-region>
    <?php foreach ($messages as $m): ?>
        <div class="toast toast--<?= e($m['type']) ?>" role="<?= $m['type'] === 'error' ? 'alert' : 'status' ?>">
            <?= icon($icons[$m['type']] ?? 'info') ?>
            <p><?= e($m['message']) ?></p>
            <button type="button" aria-label="Dismiss" data-toast-dismiss><?= icon('x', 'icon icon--sm') ?></button>
        </div>
    <?php endforeach; ?>
</div>
