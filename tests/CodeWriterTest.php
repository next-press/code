<?php

declare(strict_types=1);

use Auroro\Code\CodeStyle;
use Auroro\Code\CodeWriter;

test('writes a single line', function () {
    $w = new CodeWriter();
    $w->line('hello');
    expect((string) $w)->toBe('hello');
});

test('writes multiple lines', function () {
    $w = new CodeWriter();
    $w->line('line 1');
    $w->line('line 2');
    expect((string) $w)->toBe("line 1\nline 2");
});

test('writes lines with indentation', function () {
    $w = new CodeWriter();
    $w->line('class Foo');
    $w->indent();
    $w->line('public function bar()');
    $w->dedent();
    expect((string) $w)->toBe("class Foo\n    public function bar()");
});

test('writes blank lines without trailing whitespace', function () {
    $w = new CodeWriter();
    $w->line('before');
    $w->indent();
    $w->blank();
    $w->line('after');
    expect((string) $w)->toBe("before\n\n    after");
});

test('writes comments with prefix', function () {
    $w = new CodeWriter();
    $w->comment('this is a comment');
    expect((string) $w)->toBe('// this is a comment');
});

test('writes comments at current indent level', function () {
    $w = new CodeWriter();
    $w->indent();
    $w->comment('indented comment');
    expect((string) $w)->toBe('    // indented comment');
});

test('writes raw text without indentation', function () {
    $w = new CodeWriter();
    $w->indent();
    $w->raw('#pragma once');
    expect((string) $w)->toBe('#pragma once');
});

test('dedent does not go below zero', function () {
    $w = new CodeWriter();
    $w->dedent();
    $w->dedent();
    $w->line('still at level 0');
    expect((string) $w)->toBe('still at level 0');
});

test('writes blocks', function () {
    $w = new CodeWriter();
    $w->block('func hello()', function (CodeWriter $w) {
        $w->line('print("hi")');
    });
    expect((string) $w)->toBe("func hello() {\n    print(\"hi\")\n}");
});

test('writes nested blocks', function () {
    $w = new CodeWriter();
    $w->block('class Foo', function (CodeWriter $w) {
        $w->block('func bar()', function (CodeWriter $w) {
            $w->line('return 1');
        });
    });
    expect((string) $w)->toBe("class Foo {\n    func bar() {\n        return 1\n    }\n}");
});

test('writes indented block without braces', function () {
    $w = new CodeWriter();
    $w->line('items:');
    $w->indented(function (CodeWriter $w) {
        $w->line('- item 1');
        $w->line('- item 2');
    });
    expect((string) $w)->toBe("items:\n    - item 1\n    - item 2");
});

test('appends another writer re-indented', function () {
    $inner = new CodeWriter();
    $inner->line('x = 1');
    $inner->line('y = 2');

    $outer = new CodeWriter();
    $outer->block('func foo()', function (CodeWriter $w) use ($inner) {
        $w->append($inner);
    });
    expect((string) $outer)->toBe("func foo() {\n    x = 1\n    y = 2\n}");
});

test('appends preserves empty lines from other writer', function () {
    $inner = new CodeWriter();
    $inner->line('a');
    $inner->blank();
    $inner->line('b');

    $outer = new CodeWriter();
    $outer->indent();
    $outer->append($inner);
    expect((string) $outer)->toBe("    a\n\n    b");
});

test('isEmpty returns true for new writer', function () {
    $w = new CodeWriter();
    expect($w->isEmpty())->toBeTrue();
});

test('isEmpty returns false after writing a line', function () {
    $w = new CodeWriter();
    $w->line('x');
    expect($w->isEmpty())->toBeFalse();
});

test('uses custom code style for indentation', function () {
    $w = new CodeWriter(CodeStyle::js());
    $w->block('function hello()', function (CodeWriter $w) {
        $w->line('console.log("hi")');
    });
    expect((string) $w)->toBe("function hello() {\n  console.log(\"hi\")\n}");
});

test('uses yaml style without braces', function () {
    $w = new CodeWriter(CodeStyle::yaml());
    $w->block('server', function (CodeWriter $w) {
        $w->line('port: 8080');
        $w->line('host: localhost');
    });
    expect((string) $w)->toBe("server\n  port: 8080\n  host: localhost");
});

test('line returns self for chaining', function () {
    $w = new CodeWriter();
    $result = $w->line('test');
    expect($result)->toBe($w);
});

test('all mutator methods return self', function () {
    $w = new CodeWriter();
    expect($w->line('x'))->toBe($w)
        ->and($w->blank())->toBe($w)
        ->and($w->comment('c'))->toBe($w)
        ->and($w->raw('r'))->toBe($w)
        ->and($w->indent())->toBe($w)
        ->and($w->dedent())->toBe($w)
        ->and($w->block('b', fn (CodeWriter $w) => $w))->toBe($w)
        ->and($w->indented(fn (CodeWriter $w) => $w))->toBe($w)
        ->and($w->append(new CodeWriter()))->toBe($w);
});

test('implements Stringable', function () {
    $w = new CodeWriter();
    expect($w)->toBeInstanceOf(Stringable::class);
});
