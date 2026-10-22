<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('todos los enums en App\\Enums exponen label()', function (): void {
    $files = File::files(app_path('Enums'));

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        $class = 'App\\Enums\\'.$file->getFilenameWithoutExtension();

        expect(enum_exists($class))->toBeTrue();

        /** @var class-string<BackedEnum> $class */
        $cases = $class::cases();
        expect($cases)->not->toBeEmpty();

        foreach ($cases as $case) {
            expect(method_exists($case, 'label'))->toBeTrue();
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    }
});
