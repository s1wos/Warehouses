<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'customer' => $this->customer,
            'warehouse' => $this->warehouse->name,
            'status' => $this->status,
            'items' => $this->items->map(function ($item) {
                return [
                    'product' => $item->product->name,
                    'count' => $item->count,
                ];
            }),
        ];
    }
}
