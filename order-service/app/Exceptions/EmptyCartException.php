<?php

namespace App\Exceptions;

use RuntimeException;

class EmptyCartException extends RuntimeException
{
    public function __construct(string $message = 'Keranjang belanja masih kosong')
    {
        parent::__construct($message);
    }
}
