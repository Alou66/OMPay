<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type,
            'montant' => $this->montant,
            'statut' => $this->statut,
            'date_operation' => $this->date_operation->toISOString(),
            'description' => $this->description,
            'reference' => $this->reference,
        ];
    }
}