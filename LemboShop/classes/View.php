<?php
declare(strict_types=1);

/**
 * PHP template renderer. Templates live in /templates and must escape output with e().
 */
final class View
{
    /**
     * Render a page template inside a layout.
     *
     * @param string $template  path relative to /templates without ".php", e.g. "auth/login"
     * @param array  $data      variables exposed to the template and layout
     * @param string|null $layout layout name in /templates/layouts, or null for none
     */
    public static function render(string $template, array $data = [], ?string $layout = 'store'): void
    {
        $content = self::capture($template, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::capture('layouts/' . $layout, $data + ['content' => $content]);
    }

    /** Render a template to a string. */
    public static function capture(string $template, array $data = []): string
    {
        $file = ROOT_PATH . '/templates/' . $template . '.php';
        if (!preg_match('#^[a-z0-9_/-]+$#i', $template) || !is_file($file)) {
            throw new RuntimeException("Template not found: $template");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    /** Include a partial with its own variables. */
    public static function partial(string $name, array $data = []): void
    {
        echo self::capture('partials/' . $name, $data);
    }
}
