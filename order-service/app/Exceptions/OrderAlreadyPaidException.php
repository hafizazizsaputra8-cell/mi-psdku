<?php

namespace App\Exceptions;

use RuntimeException;

class OrderAlreadyPaidException extends RuntimeException
{
    public function __construct(string $status = 'PAID')
    {
        parent::__construct(
            "Pesanan berstatus {$status}, hanya pesanan PENDING yang dapat dibayar"
        );
    }
}
