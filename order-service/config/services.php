<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials and connection URLs for
    | third party microservices that order-service communicates with.
    |
    */

    'cart' => [
        'url' => env('CART_SERVICE_URL', 'http://cart-service:3001'),
        'timeout' => (float) env('CART_SERVICE_TIMEOUT', 5.0),
    ],

    'product' => [
        'url' => env('PRODUCT_SERVICE_URL', 'http://product-service:3000'),
        'timeout' => (float) env('PRODUCT_SERVICE_TIMEOUT', 5.0),
    ],

];
