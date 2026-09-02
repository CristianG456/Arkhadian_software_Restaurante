<?php

return [
    'business_name' => env('RESTAURANT_NAME', 'Restaurant Admin'),
    /*
    | The future customer-facing table URL. Keeping it in configuration
    | prevents names, IDs or deployment domains from becoming QR identity.
    */
    'table_qr_public_base_url' => env(
        'TABLE_QR_PUBLIC_BASE_URL',
        'http://127.0.0.1:8000/t',
    ),
];
