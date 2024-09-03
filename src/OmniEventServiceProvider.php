<?php

namespace PDPhilip\OmniEvent;

use PDPhilip\OmniEvent\Commands\OmniEventMakeCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class OmniEventServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
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
