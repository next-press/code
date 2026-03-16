<?php

declare(strict_types=1);

namespace Auroro\Code;

use Stringable;

final class CodeWriter implements Stringable
{
    private int $level = 0;

    /** @var list<string> */
    private array $lines = [];

    public function __construct(
        private readonly CodeStyle $style = new CodeStyle(),
    ) {}

    /**
     * Emit a line at the current indent level.
     */
    public function line(string $text): self
    {
        $this->lines[] = $this->currentIndent() . $text;

        return $this;
    }

    /**
     * Emit an empty line (no trailing whitespace).
     */
    public function blank(): self
    {
        $this->lines[] = '';

        return $this;
    }

    /**
     * Emit a comment at the current indent level.
     */
    public function comment(string $text): self
    {
        $this->lines[] = $this->currentIndent() . $this->style->commentPrefix . ' ' . $text;

        return $this;
    }

    /**
     * Emit raw text without auto-indent (pre-formatted content).
     */
    public function raw(string $text): self
    {
        $this->lines[] = $text;

        return $this;
    }

    /**
     * Increase indent level.
     */
    public function indent(): self
    {
        $this->level++;

        return $this;
    }

    /**
     * Decrease indent level (minimum 0).
     */
    public function dedent(): self
    {
        $this->level = max(0, $this->level - 1);

        return $this;
    }

    /**
     * Emit a block: header + blockOpen, indent, body, dedent, blockClose.
     *
     * @param callable(self): void $body
     */
    public function block(string $header, callable $body): self
    {
        $this->lines[] = $this->currentIndent() . $header . $this->style->blockOpen;
        $this->level++;
        $body($this);
        $this->level--;

        if ('' !== $this->style->blockClose) {
            $this->lines[] = $this->currentIndent() . $this->style->blockClose;
        }

        return $this;
    }

    /**
     * Indent, run body, dedent — no braces.
     *
     * @param callable(self): void $body
     */
    public function indented(callable $body): self
    {
        $this->level++;
        $body($this);
        $this->level--;

        return $this;
    }

    /**
     * Re-indent another writer's lines at the current level.
     */
    public function append(self $other): self
    {
        $indent = $this->currentIndent();

        foreach ($other->lines as $line) {
            $this->lines[] = '' === $line ? '' : $indent . $line;
        }

        return $this;
    }

    public function __toString(): string
    {
        return implode("\n", $this->lines);
    }

    public function isEmpty(): bool
    {
        return [] === $this->lines;
    }

    private function currentIndent(): string
    {
        return str_repeat($this->style->indent, $this->level);
    }
}
