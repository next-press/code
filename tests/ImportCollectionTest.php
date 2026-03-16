<?php

declare(strict_types=1);

use Auroro\Code\ImportCollection;

test('add and has track modules', function () {
    $imports = new ImportCollection();
    $imports->add('Foundation');
    expect($imports->has('Foundation'))->toBeTrue()
        ->and($imports->has('UIKit'))->toBeFalse();
});

test('add deduplicates modules', function () {
    $imports = new ImportCollection();
    $imports->add('Foundation');
    $imports->add('Foundation');
    $imports->add('Foundation');
    expect($imports->render())->toBe('import Foundation;');
});

test('render sorts modules alphabetically', function () {
    $imports = new ImportCollection();
    $imports->add('UIKit');
    $imports->add('Foundation');
    $imports->add('SwiftUI');
    expect($imports->render())->toBe("import Foundation;\nimport SwiftUI;\nimport UIKit;");
});

test('render uses custom keyword', function () {
    $imports = new ImportCollection();
    $imports->add('React');
    $imports->add('ReactDOM');
    expect($imports->render('require'))->toBe("require React;\nrequire ReactDOM;");
});

test('render returns empty string when no imports', function () {
    $imports = new ImportCollection();
    expect($imports->render())->toBe('');
});

test('has returns false for empty collection', function () {
    $imports = new ImportCollection();
    expect($imports->has('anything'))->toBeFalse();
});
