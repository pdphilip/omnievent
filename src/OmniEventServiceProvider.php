<?php

// Eleganced at 2026-02-22 19:30

declare(strict_types=1);

namespace PDPhilip\OmniEvent;

use PDPhilip\OmniEvent\Commands\OmniEventMakeCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class OmniEventServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('omnievent')
            ->hasConfigFile()
            ->hasViews('omnievent')
            ->hasCommand(OmniEventMakeCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->setName('omnievent:install')
                    ->publishConfigFile()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('pdphilip/omnievent');
            });
    }
}
