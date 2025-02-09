<?php

declare(strict_types=1);

namespace Akira\Followable\Tests;

use Akira\Followable\FollowableServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    //    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Akira\\Followable\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    final public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        $migrations = [
            __DIR__.'/../database/migrations',
            __DIR__.'/Fixtures/Migrations',
        ];

        foreach ($migrations as $migration) {
            foreach (File::files($migration) as $file) {
                (include $file->getRealPath())->up();
            }
        }

    }

    protected function getPackageProviders($app)
    {
        return [
            FollowableServiceProvider::class,
        ];
    }
}
