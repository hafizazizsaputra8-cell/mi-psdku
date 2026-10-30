<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $productName, int $requested, int $available)
    {
        parent::__construct(
            "Stok {$productName} tidak mencukupi: diminta {$requested}, tersedia {$available}"
        );
    }
}
