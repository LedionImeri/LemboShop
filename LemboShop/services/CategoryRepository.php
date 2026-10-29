<?php
declare(strict_types=1);

final class CategoryRepository
{
    private static ?array $tree = null;

    /**
     * Active top-level categories, each with a 'children' list. Cached per request.
     */
    public static function tree(): array
    {
        if (self::$tree !== null) {
            return self::$tree;
        }
        $rows = Database::all(
            'SELECT id, parent_id, name, slug, description, icon, image
               FROM categories WHERE is_active = 1 ORDER BY sort_order, name'
        );
        $top = [];
        foreach ($rows as $row) {
            if ($row['parent_id'] === null) {
                $top[$row['id']] = $row + ['children' => []];
            }
        }
        foreach ($rows as $row) {
            if ($row['parent_id'] !== null && isset($top[$row['parent_id']])) {
                $top[$row['parent_id']]['children'][] = $row;
            }
        }
        return self::$tree = array_values($top);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM categories WHERE slug = ? AND is_active = 1', [$slug]);
    }
}
