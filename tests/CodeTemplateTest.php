<?php

declare(strict_types=1);

use Auroro\Code\CodeStyle;
use Auroro\Code\CodeTemplate;
use Auroro\Code\CodeWriter;

test('interpolates variables', function () {
    $tpl = new CodeTemplate('Hello {{ name }}!');
    expect($tpl->render(['name' => 'World']))->toBe('Hello World!');
});

test('interpolates multiple variables on same line', function () {
    $tpl = new CodeTemplate('{{ a }} + {{ b }} = {{ c }}');
    expect($tpl->render(['a' => '1', 'b' => '2', 'c' => '3']))->toBe('1 + 2 = 3');
});

test('interpolates integer values', function () {
    $tpl = new CodeTemplate('count: {{ n }}');
    expect($tpl->render(['n' => 42]))->toBe('count: 42');
});

test('interpolates Stringable values', function () {
    $w = new CodeWriter();
    $w->line('hello');
    $tpl = new CodeTemplate('body: {{ content }}');
    expect($tpl->render(['content' => $w]))->toBe('body: hello');
});

test('indents multiline interpolations to placeholder column', function () {
    $body = new CodeWriter(CodeStyle::swift());
    $body->line('case "a": AView()');
    $body->line('case "b": BView()');

    $tpl = new CodeTemplate("switch x {\n    {{ cases }}\n}");
    $result = $tpl->render(['cases' => $body]);
    expect($result)->toBe("switch x {\n    case \"a\": AView()\n    case \"b\": BView()\n}");
});

test('indents multiline string interpolation', function () {
    $multiline = "line1\nline2\nline3";
    $tpl = new CodeTemplate("start:\n    {{ body }}\nend");
    $result = $tpl->render(['body' => $multiline]);
    expect($result)->toBe("start:\n    line1\n    line2\n    line3\nend");
});

test('handles for loops', function () {
    $tpl = new CodeTemplate("{{% for item in items %}}\nItem: {{ item }}\n{{% endfor %}}");
    expect($tpl->render(['items' => ['a', 'b']]))->toBe("Item: a\nItem: b");
});

test('handles for loops with empty collection', function () {
    $tpl = new CodeTemplate("{{% for item in items %}}\nItem: {{ item }}\n{{% endfor %}}");
    expect($tpl->render(['items' => []]))->toBe('');
});

test('handles conditionals', function () {
    $tpl = new CodeTemplate("{{% if show %}}\nVisible\n{{% endif %}}");
    expect($tpl->render(['show' => true]))->toBe('Visible');
    expect($tpl->render(['show' => false]))->toBe('');
});

test('handles if-else', function () {
    $tpl = new CodeTemplate("{{% if show %}}\nYes\n{{% else %}}\nNo\n{{% endif %}}");
    expect($tpl->render(['show' => true]))->toBe('Yes');
    expect($tpl->render(['show' => false]))->toBe('No');
});

test('handles negated conditionals', function () {
    $tpl = new CodeTemplate("{{% if not hidden %}}\nVisible\n{{% endif %}}");
    expect($tpl->render(['hidden' => false]))->toBe('Visible');
    expect($tpl->render(['hidden' => true]))->toBe('');
});

test('handles nested control blocks', function () {
    $tpl = new CodeTemplate(implode("\n", [
        '{{% for item in items %}}',
        '{{% if show %}}',
        '{{ item }}',
        '{{% endif %}}',
        '{{% endfor %}}',
    ]));
    expect($tpl->render(['items' => ['a', 'b'], 'show' => true]))->toBe("a\nb");
    expect($tpl->render(['items' => ['a', 'b'], 'show' => false]))->toBe('');
});

test('preserves blank lines in template content', function () {
    $tpl = new CodeTemplate("line1\n\nline3");
    expect($tpl->render([]))->toBe("line1\n\nline3");
});

test('control-only lines do not leave blank lines', function () {
    $tpl = new CodeTemplate("before\n{{% if show %}}\ncontent\n{{% endif %}}\nafter");
    expect($tpl->render(['show' => true]))->toBe("before\ncontent\nafter");
    expect($tpl->render(['show' => false]))->toBe("before\nafter");
});

test('truthy check: non-empty string is truthy', function () {
    $tpl = new CodeTemplate("{{% if val %}}\nYes\n{{% endif %}}");
    expect($tpl->render(['val' => 'hello']))->toBe('Yes');
    expect($tpl->render(['val' => '']))->toBe('');
});

test('truthy check: non-empty array is truthy', function () {
    $tpl = new CodeTemplate("{{% if val %}}\nYes\n{{% endif %}}");
    expect($tpl->render(['val' => [1]]))->toBe('Yes');
    expect($tpl->render(['val' => []]))->toBe('');
});

test('truthy check: non-zero integer is truthy', function () {
    $tpl = new CodeTemplate("{{% if val %}}\nYes\n{{% endif %}}");
    expect($tpl->render(['val' => 1]))->toBe('Yes');
    expect($tpl->render(['val' => 0]))->toBe('');
});

test('fromFile loads template from disk', function () {
    $path = sys_get_temp_dir() . '/code_template_test_' . uniqid() . '.txt';
    file_put_contents($path, 'Hello {{ name }}!');
    $tpl = CodeTemplate::fromFile($path);
    expect($tpl->render(['name' => 'World']))->toBe('Hello World!');
    unlink($path);
});

test('for loop with interpolation on same line', function () {
    $tpl = new CodeTemplate("{{% for name in names %}}\nimport {{ name }};\n{{% endfor %}}");
    $result = $tpl->render(['names' => ['Foundation', 'UIKit']]);
    expect($result)->toBe("import Foundation;\nimport UIKit;");
});

test('complex template with all features', function () {
    $tpl = new CodeTemplate(implode("\n", [
        '// {{ header }}',
        '',
        '{{% for import in imports %}}',
        'import {{ import }}',
        '{{% endfor %}}',
        '',
        '{{% if hasBody %}}',
        '{{ body }}',
        '{{% endif %}}',
    ]));

    $body = new CodeWriter(CodeStyle::swift());
    $body->block('struct App', function (CodeWriter $w) {
        $w->line('var name: String');
    });

    $result = $tpl->render([
        'header' => 'Generated',
        'imports' => ['Foundation', 'SwiftUI'],
        'hasBody' => true,
        'body' => $body,
    ]);

    expect($result)->toBe(implode("\n", [
        '// Generated',
        '',
        'import Foundation',
        'import SwiftUI',
        '',
        'struct App {',
        '    var name: String',
        '}',
    ]));
});

test('undefined variable leaves placeholder unchanged', function () {
    $tpl = new CodeTemplate('Hello {{ name }}!');
    expect($tpl->render([]))->toBe('Hello {{ name }}!');
});

test('fromFile throws on invalid path', function () {
    CodeTemplate::fromFile('/nonexistent/path/template.txt');
})->throws(\RuntimeException::class, 'Failed to read template file');

test('orphan closing tags are skipped', function () {
    $tpl = new CodeTemplate("before\n{{% endfor %}}\n{{% endif %}}\n{{% else %}}\nafter");
    expect($tpl->render([]))->toBe("before\nafter");
});

test('nested for loops', function () {
    $tpl = new CodeTemplate(implode("\n", [
        '{{% for row in rows %}}',
        '{{% for col in cols %}}',
        '{{ row }}-{{ col }}',
        '{{% endfor %}}',
        '{{% endfor %}}',
    ]));
    $result = $tpl->render(['rows' => ['a', 'b'], 'cols' => ['1', '2']]);
    expect($result)->toBe("a-1\na-2\nb-1\nb-2");
});

test('nested if inside if branch', function () {
    $tpl = new CodeTemplate(implode("\n", [
        '{{% if outer %}}',
        '{{% if inner %}}',
        'both',
        '{{% endif %}}',
        '{{% endif %}}',
    ]));
    expect($tpl->render(['outer' => true, 'inner' => true]))->toBe('both');
    expect($tpl->render(['outer' => true, 'inner' => false]))->toBe('');
    expect($tpl->render(['outer' => false, 'inner' => true]))->toBe('');
});

test('nested if inside else branch', function () {
    $tpl = new CodeTemplate(implode("\n", [
        '{{% if outer %}}',
        'outer-yes',
        '{{% else %}}',
        '{{% if inner %}}',
        'inner-yes',
        '{{% endif %}}',
        '{{% endif %}}',
    ]));
    expect($tpl->render(['outer' => false, 'inner' => true]))->toBe('inner-yes');
    expect($tpl->render(['outer' => false, 'inner' => false]))->toBe('');
    expect($tpl->render(['outer' => true, 'inner' => true]))->toBe('outer-yes');
});

test('interpolates boolean values', function () {
    $tpl = new CodeTemplate('{{ val }}');
    expect($tpl->render(['val' => true]))->toBe('true');
    expect($tpl->render(['val' => false]))->toBe('false');
});

test('interpolates null as empty string', function () {
    $tpl = new CodeTemplate('before{{ val }}after');
    expect($tpl->render(['val' => null]))->toBe('beforeafter');
});

test('truthy check: null is falsy', function () {
    $tpl = new CodeTemplate("{{% if val %}}\nYes\n{{% else %}}\nNo\n{{% endif %}}");
    expect($tpl->render(['val' => null]))->toBe('No');
});

test('truthy check: float zero is falsy', function () {
    $tpl = new CodeTemplate("{{% if val %}}\nYes\n{{% else %}}\nNo\n{{% endif %}}");
    expect($tpl->render(['val' => 0.0]))->toBe('No');
    expect($tpl->render(['val' => 1.5]))->toBe('Yes');
});

test('truthy check: object is truthy via default', function () {
    $tpl = new CodeTemplate("{{% if val %}}\nYes\n{{% endif %}}");
    expect($tpl->render(['val' => new \stdClass()]))->toBe('Yes');
});

test('interpolates value via default string cast', function () {
    $tpl = new CodeTemplate('{{ val }}');
    $resource = fopen('php://memory', 'r');
    $result = $tpl->render(['val' => $resource]);
    expect($result)->toContain('Resource id');
    fclose($resource);
});
