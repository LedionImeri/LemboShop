<?php
declare(strict_types=1);

final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('app.log', 'ERROR', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('app.log', 'INFO', $message, $context);
    }

    /**
     * XAMPP has no mail transport, so outgoing emails are written here instead.
     */
    public static function mail(string $to, string $subject, string $body): void
    {
        $entry = sprintf("To: %s\nSubject: %s\n\n%s\n%s\n", $to, $subject, $body, str_repeat('-', 60));
        self::append('mail.log', '[' . gmdate('Y-m-d H:i:s') . " UTC]\n" . $entry);
    }

    private static function write(string $file, string $level, string $message, array $context): void
    {
        $line = sprintf('[%s UTC] %s: %s', gmdate('Y-m-d H:i:s'), $level, $message);
        if ($context) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        self::append($file, $line . "\n");
    }

    private static function append(string $file, string $text): void
    {
        $dir = ROOT_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/' . $file, $text, FILE_APPEND | LOCK_EX);
    }
}
