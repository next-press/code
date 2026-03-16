<?php

declare(strict_types=1);

namespace Auroro\Code;

use Stringable;

final class CodeFile implements Stringable
{
    public readonly ImportCollection $imports;

    private ?string $header = null;

    private readonly CodeWriter $writer;

    public function __construct(
        private readonly CodeStyle $style = new CodeStyle(),
    ) {
        $this->imports = new ImportCollection();
        $this->writer = new CodeWriter($this->style);
    }

    /**
     * Set the file header comment/text.
     */
    public function header(string $text): self
    {
        $this->header = $text;

        return $this;
    }

    /**
     * Get the body writer for building file content.
     */
    public function body(): CodeWriter
    {
        return $this->writer;
    }

    public function __toString(): string
    {
        $sections = [];

        if (null !== $this->header) {
            $sections[] = $this->header;
        }

        $rendered = $this->imports->render();

        if ('' !== $rendered) {
            $sections[] = $rendered;
        }

        $body = (string) $this->writer;

        if ('' !== $body) {
            $sections[] = $body;
        }

        return rtrim(implode("\n\n", $sections));
    }
}
