<?php

declare(strict_types=1);

namespace Auroro\Code;

final readonly class CodeStyle
{
    public function __construct(
        public string $indent = '    ',
        public string $blockOpen = ' {',
        public string $blockClose = '}',
        public string $commentPrefix = '//',
    ) {}

    public static function swift(): self
    {
        return new self();
    }

    public static function js(): self
    {
        return new self(indent: '  ');
    }

    public static function tsx(): self
    {
        return new self(indent: '  ');
    }

    public static function yaml(): self
    {
        return new self(indent: '  ', blockOpen: '', blockClose: '');
    }
}
