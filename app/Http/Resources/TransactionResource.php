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

        $relatedUser = optional($this->relatedWallet)->user;

        $relatedUserName = null;
        if ($relatedUser) {
            $relatedUserName = $relatedUser->name;
        }

        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'related_wallet_id' => $this->related_wallet_id,
            'type' => $this->type->label(),
            'type_key' => $this->type->value,
            'amount' => number_format($this->amount, 2, '.', ''),
            'description' => $this->description,
            'is_reversal' => $this->is_reversal,
            'original_transaction_id' => $this->original_transaction_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'is_effectively_reversed' => $this->is_effectively_reversed ?? false,
            'related_user' => [
                'id' => optional($relatedUser)->id,
                'name' => optional($relatedUser)->name,
                'email' => optional($relatedUser)->email,
            ],
            'involved_party' => $relatedUserName,
        ];
    }
}
