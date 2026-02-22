<?php

// Eleganced at 2026-02-22 19:30

declare(strict_types=1);

namespace PDPhilip\OmniEvent\Exceptions;

use Exception;

class EventModelException extends Exception
{
    public function __construct(string $message, ?Exception $previous = null)
    {
        $fullMessage = $previous ? $message.': '.$previous->getMessage() : $message;

        parent::__construct($fullMessage, $previous?->getCode() ?? 0, $previous);
    }
}
