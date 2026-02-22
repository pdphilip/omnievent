<?php

use PDPhilip\OmniEvent\Exceptions\EventModelException;

it('creates exception with message only', function () {
    $exception = new EventModelException('Something broke');

    expect($exception->getMessage())->toBe('Something broke');
    expect($exception->getPrevious())->toBeNull();
});

it('creates exception with previous exception', function () {
    $previous = new RuntimeException('Connection refused');
    $exception = new EventModelException('Event model not accessible', $previous);

    expect($exception->getMessage())->toBe('Event model not accessible: Connection refused');
    expect($exception->getPrevious())->toBe($previous);
});

it('inherits code from previous exception', function () {
    $previous = new RuntimeException('Timeout', 504);
    $exception = new EventModelException('Failed', $previous);

    expect($exception->getCode())->toBe(504);
});
