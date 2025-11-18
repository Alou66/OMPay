<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource['user']),
            'compte' => $this->resource['compte'] ? new CompteResource($this->resource['compte']) : null,
            'transactions' => TransactionResource::collection($this->resource['transactions']),
        ];
    }
}