<?php

declare(strict_types=1);

namespace Auroro\Code;

use Stringable;

final class CodeTemplate
{
    public function __construct(
        private readonly string $template,
    ) {}

    public static function fromFile(string $path): self
    {
        $contents = file_get_contents($path);

        if (false === $contents) {
            throw new \RuntimeException(\sprintf('Failed to read template file: %s', $path));
        }

        return new self($contents);
    }

    /**
     * Render the template with the given variables.
     *
     * @param array<string, mixed> $vars
     */
    public function render(array $vars): string
    {
        $lines = explode("\n", $this->template);
        $output = $this->process($lines, $vars);

        return implode("\n", $output);
    }

    /**
     * Process control blocks and interpolate variables in a single pass.
     *
     * @param list<string> $lines
     * @param array<string, mixed> $vars
     * @return list<string>
     */
    private function process(array $lines, array $vars): array
    {
        $result = [];
        $i = 0;

        while ($i < \count($lines)) {
            $line = $lines[$i];
            $trimmed = trim($line);

            // {{% for item in items %}}
            if (preg_match('/^\{{% for (\w+) in (\w+) %\}\}$/', $trimmed, $matches)) {
                $itemVar = $matches[1];
                $collectionVar = $matches[2];
                $i++;

                $bodyLines = $this->collectForBlock($lines, $i);

                $collection = $vars[$collectionVar] ?? [];
                /** @var iterable<mixed> $collection */

                foreach ($collection as $item) {
                    $itemVars = array_merge($vars, [$itemVar => $item]);
                    $expanded = $this->process($bodyLines, $itemVars);

                    foreach ($expanded as $expandedLine) {
                        $result[] = $expandedLine;
                    }
                }

                continue;
            }

            // {{% if [not] condition %}}
            if (preg_match('/^\{{% if (not )?(\w+) %\}\}$/', $trimmed, $matches)) {
                $negated = '' !== $matches[1];
                $condVar = $matches[2];
                $i++;

                [$ifBody, $elseBody] = $this->collectIfBlock($lines, $i);

                $value = $vars[$condVar] ?? false;
                $truthy = $this->isTruthy($value);

                if ($negated) {
                    $truthy = !$truthy;
                }

                $selectedBody = $truthy ? $ifBody : $elseBody;
                $expanded = $this->process($selectedBody, $vars);

                foreach ($expanded as $expandedLine) {
                    $result[] = $expandedLine;
                }

                continue;
            }

            // Safety: skip orphan closing tags
            if (preg_match('/^\{{% (endfor|endif|else) %\}\}$/', $trimmed)) {
                $i++;

                continue;
            }

            // Regular line: interpolate variables
            $interpolated = $this->interpolateLine($line, $vars);

            // Interpolation may produce multiple lines (multiline values)
            foreach (explode("\n", $interpolated) as $part) {
                $result[] = $part;
            }

            $i++;
        }

        return $result;
    }

    /**
     * Collect body lines for a for block, advancing $i past {{% endfor %}}.
     *
     * @param list<string> $lines
     * @return list<string>
     */
    private function collectForBlock(array $lines, int &$i): array
    {
        $endTag = '{{% endfor %}}';
        $startPattern = '/^\{{% for \w+ in \w+ %\}\}$/';

        $bodyLines = [];
        $depth = 1;

        while ($i < \count($lines)) {
            $inner = trim($lines[$i]);

            if (preg_match($startPattern, $inner)) {
                $depth++;
                $bodyLines[] = $lines[$i];
                $i++;
            } elseif ($endTag === $inner) {
                $depth--;

                if (0 === $depth) {
                    $i++;

                    break;
                }

                $bodyLines[] = $lines[$i];
                $i++;
            } else {
                $bodyLines[] = $lines[$i];
                $i++;
            }
        }

        return $bodyLines;
    }

    /**
     * Collect if-body and else-body lines, advancing $i past {{% endif %}}.
     *
     * @param list<string> $lines
     * @return array{list<string>, list<string>}
     */
    private function collectIfBlock(array $lines, int &$i): array
    {
        $ifBody = [];
        $elseBody = [];
        $inElse = false;
        $depth = 1;

        while ($i < \count($lines)) {
            $inner = trim($lines[$i]);

            if (preg_match('/^\{{% if (not )?\w+ %\}\}$/', $inner)) {
                $depth++;
                if ($inElse) {
                    $elseBody[] = $lines[$i];
                } else {
                    $ifBody[] = $lines[$i];
                }
                $i++;
            } elseif ('{{% endif %}}' === $inner) {
                $depth--;

                if (0 === $depth) {
                    $i++;

                    break;
                }

                if ($inElse) {
                    $elseBody[] = $lines[$i];
                } else {
                    $ifBody[] = $lines[$i];
                }
                $i++;
            } elseif ('{{% else %}}' === $inner && 1 === $depth) {
                $inElse = true;
                $i++;
            } else {
                if ($inElse) {
                    $elseBody[] = $lines[$i];
                } else {
                    $ifBody[] = $lines[$i];
                }
                $i++;
            }
        }

        return [$ifBody, $elseBody];
    }

    /**
     * Interpolate {{ var }} placeholders in a single line with indent-aware multiline expansion.
     *
     * @param array<string, mixed> $vars
     */
    private function interpolateLine(string $line, array $vars): string
    {
        return preg_replace_callback(
            '/\{\{ (\w+) \}\}/',
            function (array $matches) use ($line, $vars): string {
                $key = $matches[1];

                if (!array_key_exists($key, $vars)) {
                    return $matches[0];
                }

                $value = $vars[$key];

                $stringValue = match (true) {
                    $value instanceof Stringable => (string) $value,
                    \is_string($value) => $value,
                    \is_int($value), \is_float($value) => (string) $value,
                    \is_bool($value) => $value ? 'true' : 'false',
                    null === $value => '',
                    default => (string) $value,
                };

                // Indent-aware multiline: find the column position of this placeholder
                if (str_contains($stringValue, "\n")) {
                    $pos = strpos($line, $matches[0]);
                    $indent = str_repeat(' ', false !== $pos ? $pos : 0);
                    $valueLines = explode("\n", $stringValue);

                    return $valueLines[0] . "\n" . implode("\n", array_map(
                        static fn(string $l): string => '' === $l ? '' : $indent . $l,
                        \array_slice($valueLines, 1),
                    ));
                }

                return $stringValue;
            },
            $line,
        ) ?? $line;
    }

    private function isTruthy(mixed $value): bool
    {
        return match (true) {
            \is_bool($value) => $value,
            \is_string($value) => '' !== $value,
            \is_array($value) => [] !== $value,
            \is_int($value), \is_float($value) => 0 !== $value && 0.0 !== $value,
            null === $value => false,
            default => (bool) $value,
        };
    }
}
