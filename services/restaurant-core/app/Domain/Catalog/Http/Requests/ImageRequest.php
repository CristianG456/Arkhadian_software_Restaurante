<?php

namespace App\Domain\Catalog\Http\Requests;

class ImageRequest extends CatalogRequest
{
    public function rules(): array
    {
        return ['image' => ['required', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/webp']];
    }
}
