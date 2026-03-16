# Auroro Code

PHP code generation utilities for exporting values and building PHP files. Part of the Auroro framework ecosystem.

## Installation

```bash
composer require auroro/code
```

## Usage

### Exporting PHP values

`Exporter` converts PHP values into valid PHP code strings, handling arrays, objects, enums, and class references with optional short-name imports.

```php
use Auroro\Code\Exporter;

$exporter = new Exporter();

$exporter->export(42);          // '42'
$exporter->export('hello');     // "'hello'"
$exporter->export([1, 2, 3]);  // "[\n    1,\n    2,\n    3,\n]"
$exporter->export(true);       // 'true'

// Class references are exported as ::class
$exporter->export(DateTime::class); // '\DateTime::class'

// Register imports for short names
$exporter->import(DateTime::class);
$exporter->export(DateTime::class); // 'DateTime::class'
```

### Generating PHP files

`PhpFile` builds complete PHP files with declare, namespace, use statements, and a return value or body.

```php
use Auroro\Code\PhpFile;

$file = (new PhpFile())
    ->namespace('App\Config')
    ->use(DateTime::class)
    ->return([
        'created' => new DateTime('2024-01-01'),
    ]);

file_put_contents('config.php', $file->generate());
```

### Code writer

`CodeWriter` is an indent-aware line builder for generating structured code in any language.

```php
use Auroro\Code\CodeWriter;
use Auroro\Code\CodeStyle;

$writer = new CodeWriter(CodeStyle::js());

$writer
    ->line('function greet(name) {')
    ->indent()
    ->line('console.log(`Hello, ${name}!`);')
    ->dedent()
    ->line('}');

echo $writer; // properly indented JS code
```

Use `block()` for automatic brace handling:

```php
$writer->block('function greet(name)', function (CodeWriter $w) {
    $w->line('console.log(`Hello, ${name}!`);');
});
```

### Code files

`CodeFile` combines imports, headers, and a `CodeWriter` body into a single output.

```php
use Auroro\Code\CodeFile;

$file = new CodeFile();
$file->header('// Auto-generated');
$file->imports->add('Foundation');
$file->body()->block('struct Config', function (CodeWriter $w) {
    $w->line('let name: String');
});

echo $file;
```

### Templates

`CodeTemplate` provides a simple template engine with `{{ var }}` interpolation, `{{% for %}}` loops, and `{{% if %}}` conditionals.

```php
use Auroro\Code\CodeTemplate;

$template = new CodeTemplate('Hello, {{ name }}!');
echo $template->render(['name' => 'World']); // 'Hello, World!'

// From file
$template = CodeTemplate::fromFile('template.txt');
echo $template->render(['items' => ['a', 'b', 'c']]);
```

### String utilities

`Str` provides common string transformations for code generation.

```php
use Auroro\Code\Str;

Str::slugify('Hello World!'); // 'hello-world'
Str::kebab('MyClassName');    // 'my-class-name'
```

## License

MIT — see [LICENSE](LICENSE) for details.
