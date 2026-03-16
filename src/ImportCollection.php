<?php

declare(strict_types=1);

namespace Auroro\Code;

final class ImportCollection
{
    /** @var array<string, true> */
    private array $modules = [];

    /**
     * Add a module to the collection (deduplicates).
     */
    public function add(string $module): void
    {
        $this->modules[$module] = true;
    }

    /**
     * Check if a module is in the collection.
     */
    public function has(string $module): bool
    {
        return isset($this->modules[$module]);
    }

    /**
     * Render all imports sorted alphabetically, one per line.
     */
    public function render(string $keyword = 'import'): string
    {
        if ([] === $this->modules) {
            return '';
        }

        $names = array_keys($this->modules);
        sort($names);

        return implode("\n", array_map(
            static fn(string $name): string => $keyword . ' ' . $name . ';',
            $names,
        ));
    }
}
