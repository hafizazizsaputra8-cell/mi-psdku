<?php

namespace App\Exceptions;

use RuntimeException;

class ProductNotFoundException extends RuntimeException
{
    public function __construct(int|string $productId, string $message = '')
    {
        if (empty($message)) {
            $message = "Produk dengan ID {$productId} tidak ditemukan di katalog";
        }
        parent::__construct($message);
    }
}
