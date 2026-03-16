<?php

declare(strict_types=1);

namespace Auroro\Code;

final class Str
{
    /** General-purpose slugify: lowercase, strip non-word, spaces→hyphens. */
    public static function slugify(string $text): string
    {
        $slug = strtolower($text);
        $slug = (string) preg_replace('/[^\w\s-]/u', '', $slug);
        $slug = (string) preg_replace('/[\s_]+/', '-', $slug);

        return trim($slug, '-');
    }

    /** PascalCase / camelCase → kebab-case. */
    public static function kebab(string $name): string
    {
        $slug = preg_replace('/([a-z])([A-Z])/', '$1-$2', $name);

        return strtolower((string) $slug);
    }
}
