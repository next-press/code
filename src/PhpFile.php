<?php

declare(strict_types=1);

namespace Auroro\Code;

final class PhpFile
{
    private ?string $docblock = null;
    private ?string $namespace = null;

    /** @var list<string> */
    private array $uses = [];

    private ?string $body = null;

    private mixed $returnValue = null;
    private bool $hasReturn = false;

    public function __construct(
        private readonly Exporter $exporter = new Exporter(),
    ) {}

    public function docblock(string $docblock): self
    {
        $this->docblock = $docblock;

        return $this;
    }

    public function namespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function use(string ...$classes): self
    {
        foreach ($classes as $class) {
            $this->uses[] = ltrim($class, '\\');
        }

        return $this;
    }

    /**
     * Set the file to return a value (generates `return <exported>;`).
     */
    public function return(mixed $value): self
    {
        $this->returnValue = $value;
        $this->hasReturn = true;

        return $this;
    }

    /**
     * Set raw PHP code as the file body.
     */
    public function body(string $code): self
    {
        $this->body = $code;

        return $this;
    }

    public function generate(): string
    {
        $lines = ["<?php\n"];

        if (null !== $this->docblock) {
            $lines[] = $this->formatDocblock($this->docblock) . "\n";
        }

        $lines[] = "declare(strict_types=1);\n";

        if (null !== $this->namespace) {
            $lines[] = 'namespace ' . $this->namespace . ";\n";
        }

        if ([] !== $this->uses) {
            $uses = array_unique($this->uses);
            sort($uses);

            foreach ($uses as $use) {
                $lines[] = 'use ' . $use . ';';
            }

            $lines[] = '';
        }

        if (null !== $this->body) {
            $lines[] = $this->body;
        }

        if ($this->hasReturn) {
            $this->exporter->import(...$this->uses);
            $lines[] = 'return ' . $this->exporter->export($this->returnValue) . ';';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    private function formatDocblock(string $text): string
    {
        $lines = explode("\n", $text);

        if (1 === \count($lines)) {
            return '/** ' . $lines[0] . ' */';
        }

        $result = "/**\n";

        foreach ($lines as $line) {
            $result .= '' === $line ? " *\n" : ' * ' . $line . "\n";
        }

        return $result . ' */';
    }
}
