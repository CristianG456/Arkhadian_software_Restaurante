<?php

namespace App\Domain\TableQr\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableQrCredentialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $baseUrl = rtrim((string) config('restaurant.table_qr_public_base_url'), '/');

        return [
            'id' => $this->id,
            'tableId' => $this->restaurant_table_id,
            'publicToken' => $this->public_token,
            'publicUrl' => $baseUrl === '' ? null : $baseUrl.'/'.$this->public_token,
            'version' => $this->version,
            'status' => $this->status,
            'createdAt' => $this->created_at?->toISOString(),
            'revokedAt' => $this->revoked_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
