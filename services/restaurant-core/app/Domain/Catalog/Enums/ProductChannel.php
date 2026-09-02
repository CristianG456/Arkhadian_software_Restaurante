<?php

namespace App\Domain\Catalog\Enums;

enum ProductChannel: string
{
    case ADMIN_POS = 'ADMIN_POS';
    case WAITER = 'WAITER';
    case CUSTOMER_QR = 'CUSTOMER_QR';
    case PUBLIC_WEB = 'PUBLIC_WEB';
}
