<?php

declare(strict_types=1);

use Auroro\Code\Exporter;

test('exports null', function () {
    expect((new Exporter())->export(null))->toBe('null');
});

test('exports booleans', function () {
    $exporter = new Exporter();
    expect($exporter->export(true))->toBe('true')
        ->and($exporter->export(false))->toBe('false');
});

test('exports integers', function () {
    $exporter = new Exporter();
    expect($exporter->export(0))->toBe('0')
        ->and($exporter->export(42))->toBe('42')
        ->and($exporter->export(-1))->toBe('-1');
});

test('exports floats', function () {
    $exporter = new Exporter();
    expect($exporter->export(3.14))->toBe('3.14')
        ->and($exporter->export(1.0))->toBe('1.0')
        ->and($exporter->export(0.0))->toBe('0.0');
});

test('exports strings with escaping', function () {
    $exporter = new Exporter();
    expect($exporter->export('hello'))->toBe("'hello'")
        ->and($exporter->export("it's"))->toBe("'it\\'s'")
        ->and($exporter->export('back\\slash'))->toBe("'back\\\\slash'")
        ->and($exporter->export(''))->toBe("''");
});

test('exports empty array', function () {
    expect((new Exporter())->export([]))->toBe('[]');
});

test('exports list array', function () {
    $result = (new Exporter())->export([1, 2, 3]);
    expect($result)->toBe("[\n    1,\n    2,\n    3,\n]");
});

test('exports associative array', function () {
    $result = (new Exporter())->export(['key' => 'value', 'num' => 42]);
    expect($result)->toContain("'key' => 'value'")
        ->and($result)->toContain("'num' => 42");
});

test('exports nested arrays with indentation', function () {
    $result = (new Exporter())->export(['a' => ['b' => 'c']]);
    expect($result)->toContain("'a' => [\n        'b' => 'c',\n    ]");
});

test('exports backed enum', function () {
    $enum = ExporterTestStatus::Active;
    $result = (new Exporter())->export($enum);
    expect($result)->toBe('\\ExporterTestStatus::Active');
});

test('exports readonly object via constructor params', function () {
    $obj = new ExporterTestPoint(x: 10, y: 20);
    $result = (new Exporter())->export($obj);

    expect($result)->toContain('new \\ExporterTestPoint(')
        ->and($result)->toContain('x: 10')
        ->and($result)->toContain('y: 20');

    // Verify the generated code is valid PHP
    $recreated = eval('return ' . $result . ';');
    expect($recreated)->toBeInstanceOf(ExporterTestPoint::class)
        ->and($recreated->x)->toBe(10)
        ->and($recreated->y)->toBe(20);
});

test('exports object with nested objects', function () {
    $obj = new ExporterTestLine(
        start: new ExporterTestPoint(1, 2),
        end: new ExporterTestPoint(3, 4),
    );
    $result = (new Exporter())->export($obj);

    expect($result)->toContain('new \\ExporterTestLine(')
        ->and($result)->toContain('new \\ExporterTestPoint(');

    $recreated = eval('return ' . $result . ';');
    expect($recreated->start->x)->toBe(1)
        ->and($recreated->end->y)->toBe(4);
});

test('exports object with null and array properties', function () {
    $obj = new ExporterTestConfig(name: 'test', tags: ['a', 'b'], extra: null);
    $result = (new Exporter())->export($obj);

    $recreated = eval('return ' . $result . ';');
    expect($recreated->name)->toBe('test')
        ->and($recreated->tags)->toBe(['a', 'b'])
        ->and($recreated->extra)->toBeNull();
});

test('exports class-string as ::class symbol', function () {
    $exporter = new Exporter();

    expect($exporter->export(ExporterTestPoint::class))->toBe('\\ExporterTestPoint::class')
        ->and($exporter->export(ExporterTestStatus::class))->toBe('\\ExporterTestStatus::class');
});

test('exports class-string property as ::class symbol', function () {
    $obj = new ExporterTestClassRef(target: ExporterTestPoint::class);
    $result = (new Exporter())->export($obj);

    expect($result)->toContain('target: \\ExporterTestPoint::class');

    $recreated = eval('return ' . $result . ';');
    expect($recreated->target)->toBe(ExporterTestPoint::class);
});

test('uses short names for imported classes', function () {
    $exporter = new Exporter();
    $exporter->import(ExporterTestPoint::class, ExporterTestStatus::class);

    // class-string uses short name
    expect($exporter->export(ExporterTestPoint::class))->toBe('ExporterTestPoint::class');

    // enum uses short name
    expect($exporter->export(ExporterTestStatus::Active))->toBe('ExporterTestStatus::Active');

    // object uses short name
    $obj = new ExporterTestPoint(x: 1, y: 2);
    $result = $exporter->export($obj);
    expect($result)->toContain('new ExporterTestPoint(')
        ->and($result)->not->toContain('\\ExporterTestPoint');
});

test('non-imported classes still use FQN', function () {
    $exporter = new Exporter();
    $exporter->import(ExporterTestPoint::class);

    // Imported: short name
    expect($exporter->export(ExporterTestPoint::class))->toBe('ExporterTestPoint::class');

    // Not imported: FQN with backslash
    $line = new ExporterTestLine(
        start: new ExporterTestPoint(1, 2),
        end: new ExporterTestPoint(3, 4),
    );
    $result = $exporter->export($line);
    expect($result)->toContain('new \\ExporterTestLine(')
        ->and($result)->toContain('new ExporterTestPoint(');
});

test('exports object without constructor', function () {
    $obj = new ExporterTestNoConstructor();
    $result = (new Exporter())->export($obj);
    expect($result)->toBe('new \\ExporterTestNoConstructor()');
});

test('exports object with parameterless constructor', function () {
    $obj = new ExporterTestEmptyConstructor();
    $result = (new Exporter())->export($obj);
    expect($result)->toBe('new \\ExporterTestEmptyConstructor()');
});

test('exports object with non-property constructor param', function () {
    $obj = new ExporterTestNonPropertyParam('hello');
    $result = (new Exporter())->export($obj);
    expect($result)->toBe('new \\ExporterTestNonPropertyParam()');
});

test('throws on unsupported type', function () {
    (new Exporter())->export(fopen('php://memory', 'r'));
})->throws(\InvalidArgumentException::class);

// Test fixtures
enum ExporterTestStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

final readonly class ExporterTestPoint
{
    public function __construct(
        public int $x,
        public int $y,
    ) {}
}

final readonly class ExporterTestLine
{
    public function __construct(
        public ExporterTestPoint $start,
        public ExporterTestPoint $end,
    ) {}
}

final readonly class ExporterTestConfig
{
    public function __construct(
        public string $name,
        public array $tags,
        public ?string $extra,
    ) {}
}

final readonly class ExporterTestClassRef
{
    public function __construct(
        public string $target,
    ) {}
}

final class ExporterTestNoConstructor {}

final class ExporterTestEmptyConstructor
{
    public function __construct() {}
}

final class ExporterTestNonPropertyParam
{
    public function __construct(string $value)
    {
        // $value is not stored as a property
    }
}
