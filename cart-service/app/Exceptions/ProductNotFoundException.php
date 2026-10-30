<?php

namespace App\Exceptions;

use RuntimeException;


class ProductNotFoundException extends RuntimeException
{
    public function __construct(int $productId)
    {
        parent::__construct("Product dengabn id {$productId} tidak ditemukan.");
    }
}