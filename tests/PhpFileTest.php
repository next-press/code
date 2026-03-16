<?php

declare(strict_types=1);

use Auroro\Code\PhpFile;

test('generates minimal PHP file', function () {
    $file = (new PhpFile())->body('// empty');

    $output = $file->generate();
    expect($output)->toStartWith("<?php\n")
        ->and($output)->toContain("declare(strict_types=1);")
        ->and($output)->toContain('// empty');
});

test('generates file with namespace', function () {
    $output = (new PhpFile())
        ->namespace('App\\Generated')
        ->body('// body')
        ->generate();

    expect($output)->toContain("namespace App\\Generated;");
});

test('generates file with use statements', function () {
    $output = (new PhpFile())
        ->use('App\\Foo', 'App\\Bar')
        ->body('// body')
        ->generate();

    expect($output)->toContain("use App\\Bar;")
        ->and($output)->toContain("use App\\Foo;");
});

test('deduplicates and sorts use statements', function () {
    $output = (new PhpFile())
        ->use('App\\Zebra', 'App\\Alpha', 'App\\Zebra')
        ->body('// body')
        ->generate();

    $alphaPos = strpos($output, 'App\\Alpha');
    $zebraPos = strpos($output, 'App\\Zebra');
    expect($alphaPos)->toBeLessThan($zebraPos)
        ->and(substr_count($output, 'App\\Zebra'))->toBe(1);
});

test('generates file with return value', function () {
    $output = (new PhpFile())
        ->return(['key' => 'value'])
        ->generate();

    expect($output)->toContain("return [")
        ->and($output)->toContain("'key' => 'value'");
});

test('generates file with namespace, use, and return', function () {
    $output = (new PhpFile())
        ->namespace('App\\Cache')
        ->use('App\\Model\\Foo')
        ->return(42)
        ->generate();

    expect($output)->toContain("namespace App\\Cache;")
        ->and($output)->toContain("use App\\Model\\Foo;")
        ->and($output)->toContain("return 42;");
});

test('generates file with single-line docblock', function () {
    $output = (new PhpFile())
        ->docblock('This file is auto-generated.')
        ->body('// body')
        ->generate();

    expect($output)->toContain('/** This file is auto-generated. */')
        ->and(strpos($output, '/**'))->toBeLessThan(strpos($output, 'declare(strict_types=1)'));
});

test('generates file with multi-line docblock', function () {
    $output = (new PhpFile())
        ->docblock("Auto-generated file.\n\n@see SchemaCompiler")
        ->body('// body')
        ->generate();

    expect($output)->toContain("/**\n * Auto-generated file.\n *\n * @see SchemaCompiler\n */");
});

test('strips leading backslash from use statements', function () {
    $output = (new PhpFile())
        ->use('\\App\\Foo')
        ->body('// body')
        ->generate();

    expect($output)->toContain('use App\\Foo;')
        ->and($output)->not->toContain('use \\App');
});
