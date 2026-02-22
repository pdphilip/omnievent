<?php

it('runs the install command', function () {
    $this->artisan('omnievent:install')
        ->expectsConfirmation('Would you like to star our repo on GitHub?')
        ->assertExitCode(0);
});
