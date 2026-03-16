<?php

declare(strict_types=1);

namespace Auroro\Code;

final class Exporter
{
    /** @var array<string, string> FQCN => short name */
    private array $imports = [];

    /**
     * Register class imports so exported code uses short names.
     */
    public function import(string ...$fqcns): self
    {
        foreach ($fqcns as $fqcn) {
            $fqcn = ltrim($fqcn, '\\');
            $parts = explode('\\', $fqcn);
            $this->imports[$fqcn] = end($parts);
        }

        return $this;
    }

    /**
     * Export a PHP value as a valid PHP code string.
     */
    public function export(mixed $value, int $indent = 0): string
    {
        return match (true) {
            null === $value => 'null',
            \is_bool($value) => $value ? 'true' : 'false',
            \is_int($value) => (string) $value,
            \is_float($value) => $this->exportFloat($value),
            \is_string($value) => $this->exportString($value),
            \is_array($value) => $this->exportArray($value, $indent),
            $value instanceof \BackedEnum => $this->exportEnum($value),
            \is_object($value) => $this->exportObject($value, $indent),
            default => throw new \InvalidArgumentException(\sprintf('Cannot export value of type "%s".', get_debug_type($value))),
        };
    }

    private function exportFloat(float $value): string
    {
        $str = (string) $value;

        // Ensure it looks like a float (has decimal point)
        if (!str_contains($str, '.') && !str_contains($str, 'E') && !str_contains($str, 'e')) {
            $str .= '.0';
        }

        return $str;
    }

    private function exportString(string $value): string
    {
        if ('' !== $value && (class_exists($value) || interface_exists($value) || enum_exists($value))) {
            return $this->formatClassName($value) . '::class';
        }

        return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'";
    }

    /**
     * @param array<mixed> $array
     */
    private function exportArray(array $array, int $indent): string
    {
        if ([] === $array) {
            return '[]';
        }

        $isList = array_is_list($array);
        $pad = str_repeat('    ', $indent + 1);
        $closePad = str_repeat('    ', $indent);
        $entries = [];

        foreach ($array as $key => $val) {
            $exported = $this->export($val, $indent + 1);

            if ($isList) {
                $entries[] = $pad . $exported;
            } else {
                $exportedKey = $this->export($key, $indent + 1);
                $entries[] = $pad . $exportedKey . ' => ' . $exported;
            }
        }

        return "[\n" . implode(",\n", $entries) . ",\n" . $closePad . ']';
    }

    private function exportEnum(\BackedEnum $enum): string
    {
        return $this->formatClassName($enum::class) . '::' . $enum->name;
    }

    private function exportObject(object $object, int $indent): string
    {
        $class = new \ReflectionClass($object);
        $constructor = $class->getConstructor();
        $name = 'new ' . $this->formatClassName($object::class);

        if (null === $constructor) {
            return $name . '()';
        }

        $params = $constructor->getParameters();

        if ([] === $params) {
            return $name . '()';
        }

        $pad = str_repeat('    ', $indent + 1);
        $closePad = str_repeat('    ', $indent);
        $args = [];

        foreach ($params as $param) {
            $paramName = $param->getName();

            if (!$class->hasProperty($paramName)) {
                continue;
            }

            $property = $class->getProperty($paramName);
            $value = $property->getValue($object);

            $args[] = $pad . $paramName . ': ' . $this->export($value, $indent + 1);
        }

        if ([] === $args) {
            return $name . '()';
        }

        return $name . "(\n" . implode(",\n", $args) . ",\n" . $closePad . ')';
    }

    private function formatClassName(string $fqcn): string
    {
        if (isset($this->imports[$fqcn])) {
            return $this->imports[$fqcn];
        }

        return '\\' . $fqcn;
    }
}
