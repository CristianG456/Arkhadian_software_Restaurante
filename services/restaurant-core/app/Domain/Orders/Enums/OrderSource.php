<?php

namespace App\Domain\Orders\Enums;

enum OrderSource: string
{
    case WAITER = 'WAITER';
    case ADMIN_POS = 'ADMIN_POS';
    case CUSTOMER_QR = 'CUSTOMER_QR';
}
