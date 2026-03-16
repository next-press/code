<?php

declare(strict_types=1);

use Auroro\Code\CodeStyle;

test('default style uses 4-space indent', function () {
    $style = new CodeStyle();
    expect($style->indent)->toBe('    ')
        ->and($style->blockOpen)->toBe(' {')
        ->and($style->blockClose)->toBe('}')
        ->and($style->commentPrefix)->toBe('//');
});

test('swift factory returns default style', function () {
    $style = CodeStyle::swift();
    expect($style->indent)->toBe('    ')
        ->and($style->blockOpen)->toBe(' {')
        ->and($style->blockClose)->toBe('}')
        ->and($style->commentPrefix)->toBe('//');
});

test('js factory uses 2-space indent', function () {
    $style = CodeStyle::js();
    expect($style->indent)->toBe('  ')
        ->and($style->blockOpen)->toBe(' {')
        ->and($style->blockClose)->toBe('}');
});

test('tsx factory uses 2-space indent', function () {
    $style = CodeStyle::tsx();
    expect($style->indent)->toBe('  ')
        ->and($style->blockOpen)->toBe(' {')
        ->and($style->blockClose)->toBe('}');
});

test('yaml factory uses 2-space indent and no braces', function () {
    $style = CodeStyle::yaml();
    expect($style->indent)->toBe('  ')
        ->and($style->blockOpen)->toBe('')
        ->and($style->blockClose)->toBe('');
});

test('code style is readonly', function () {
    $style = new CodeStyle();
    expect((new ReflectionClass($style))->isReadOnly())->toBeTrue();
});
