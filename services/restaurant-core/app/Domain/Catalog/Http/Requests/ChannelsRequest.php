<?php

namespace App\Domain\Catalog\Http\Requests;

class ChannelsRequest extends CatalogRequest
{
    public function rules(): array
    {
        return ['channels' => ['required', 'array:ADMIN_POS,WAITER,CUSTOMER_QR,PUBLIC_WEB'], 'channels.ADMIN_POS' => ['required', 'boolean'], 'channels.WAITER' => ['required', 'boolean'], 'channels.CUSTOMER_QR' => ['required', 'boolean'], 'channels.PUBLIC_WEB' => ['required', 'boolean']];
    }
}
