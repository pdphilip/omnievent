<?php

declare(strict_types=1);

namespace PDPhilip\OmniEvent\Exceptions;

use Exception;

class EventModelException extends Exception
{
    private array $_details;

    public function __construct($message, Exception $previous, $details = [])
    {
        parent::__construct($message.': '.$previous->getMessage(), $previous->getCode(), $previous);

        $this->_details = $details;
    }

    public function getDetails(): array
    {
        return $this->_details;
    }
}
