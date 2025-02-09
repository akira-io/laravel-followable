<?php

declare(strict_types=1);

namespace Akira\Followable;

use Akira\Followable\Commands\FollowableCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class FollowableServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('followable')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_followable_table')
            ->hasCommand(FollowableCommand::class);
    }
}
