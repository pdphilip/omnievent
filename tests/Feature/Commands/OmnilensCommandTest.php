<?php

it('should run the command', function () {
    $this->artisan('omnievent:install')->expectsConfirmation('Would you like to star our repo on GitHub?')->assertExitCode(0);
});
