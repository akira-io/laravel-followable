<?php

declare(strict_types=1);

use Akira\Followable\FollowableServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('registers the package provider', function (): void {
    // Verifica se o provider foi registrado no container
    $this->assertTrue(in_array(FollowableServiceProvider::class, app()->getLoadedProviders()));
});

it('publishes configuration file', function (): void {
    $configPath = config_path('followable.php');  // Usando o helper config_path()

    // Simula publicação
    Artisan::call('vendor:publish', ['--tag' => 'followable-config', '--force' => true]);

    expect(File::exists($configPath))->toBeTrue();
});

it('has migration file', function (): void {
    $migrationPath = database_path('migrations');

    // Simula publicação
    Artisan::call('vendor:publish', ['--tag' => 'followable-migrations', '--force' => true]);

    $migrations = File::glob($migrationPath.'/*_create_followable_table.php');

    expect($migrations)->not->toBeEmpty();
});

// it('registers followable command', function () {
//    Artisan::call('list');
//
//    expect(Artisan::all())->toHaveKey('followable:command');
// });
