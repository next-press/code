<?php

declare(strict_types=1);

use Auroro\Code\CodeFile;
use Auroro\Code\CodeStyle;
use Auroro\Code\CodeWriter;

test('generates empty file', function () {
    $file = new CodeFile();
    expect((string) $file)->toBe('');
});

test('generates file with header only', function () {
    $file = new CodeFile();
    $file->header('// Auto-generated');
    expect((string) $file)->toBe('// Auto-generated');
});

test('generates file with body only', function () {
    $file = new CodeFile();
    $file->body()->line('let x = 1');
    expect((string) $file)->toBe('let x = 1');
});

test('generates file with header and body', function () {
    $file = new CodeFile();
    $file->header('// Generated');
    $file->body()->line('let x = 1');
    expect((string) $file)->toBe("// Generated\n\nlet x = 1");
});

test('generates file with imports', function () {
    $file = new CodeFile();
    $file->imports->add('Foundation');
    $file->imports->add('SwiftUI');
    $file->body()->line('struct App {}');
    expect((string) $file)->toBe("import Foundation;\nimport SwiftUI;\n\nstruct App {}");
});

test('generates file with header, imports, and body', function () {
    $file = new CodeFile();
    $file->header('// Generated');
    $file->imports->add('UIKit');
    $file->body()->line('class VC {}');
    expect((string) $file)->toBe("// Generated\n\nimport UIKit;\n\nclass VC {}");
});

test('body returns the same CodeWriter instance', function () {
    $file = new CodeFile();
    $w1 = $file->body();
    $w2 = $file->body();
    expect($w1)->toBe($w2);
});

test('uses custom code style', function () {
    $file = new CodeFile(CodeStyle::js());
    $file->body()->block('function main()', function (CodeWriter $w) {
        $w->line('console.log("hi")');
    });
    expect((string) $file)->toBe("function main() {\n  console.log(\"hi\")\n}");
});

test('implements Stringable', function () {
    $file = new CodeFile();
    expect($file)->toBeInstanceOf(Stringable::class);
});

test('header returns self for chaining', function () {
    $file = new CodeFile();
    expect($file->header('x'))->toBe($file);
});

test('trims trailing whitespace and newlines', function () {
    $file = new CodeFile();
    $file->body()->line('x = 1');
    $file->body()->blank();
    $file->body()->blank();
    $result = (string) $file;
    expect($result)->toBe("x = 1");
});
