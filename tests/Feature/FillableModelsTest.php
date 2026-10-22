<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

it('los modelos en App\\Models no usan $guarded = []', function (): void {
    $files = File::files(app_path('Models'));

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        $class = 'App\\Models\\'.$file->getFilenameWithoutExtension();

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
            continue;
        }

        $guardedProperty = $reflection->getProperty('guarded');
        $guardedProperty->setAccessible(true);

        /** @var Model $instance */
        $instance = $reflection->newInstanceWithoutConstructor();
        $guarded = $guardedProperty->getValue($instance);

        expect($guarded)->not->toBe(
            [],
            "{$class} no debe usar \$guarded = []. Usá \$fillable explícito."
        );
    }
});
