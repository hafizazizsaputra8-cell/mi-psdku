<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ServiceUnavailableException extends RuntimeException
{
    private string $serviceName;

    public function __construct(string $serviceName, string $message = '', ?Throwable $previous = null)
    {
        $this->serviceName = $serviceName;
        if (empty($message)) {
            $message = "{$serviceName} tidak dapat dihubungi, coba lagi nanti";
        }
        parent::__construct($message, 503, $previous);
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}
