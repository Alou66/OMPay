<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'transactions' => TransactionResource::collection($this->resource['transactions']),
            'pagination' => [
                'current_page' => $this->resource['pagination']['current_page'],
                'per_page' => $this->resource['pagination']['per_page'],
                'total' => $this->resource['pagination']['total'],
                'last_page' => $this->resource['pagination']['last_page'],
            ],
        ];
    }
}