<?php

declare(strict_types=1);

use Auroro\Code\Str;

test('slugify converts text to lowercase hyphenated slug', function () {
    expect(Str::slugify('Hello World'))->toBe('hello-world');
});

test('slugify strips non-word characters', function () {
    expect(Str::slugify('Hello, World!'))->toBe('hello-world');
});

test('slugify converts underscores to hyphens', function () {
    expect(Str::slugify('hello_world'))->toBe('hello-world');
});

test('slugify trims leading and trailing hyphens', function () {
    expect(Str::slugify('-hello-'))->toBe('hello');
});

test('kebab converts PascalCase to kebab-case', function () {
    expect(Str::kebab('MyClassName'))->toBe('my-class-name');
});

test('kebab converts camelCase to kebab-case', function () {
    expect(Str::kebab('camelCase'))->toBe('camel-case');
});

test('kebab handles single word', function () {
    expect(Str::kebab('hello'))->toBe('hello');
});
