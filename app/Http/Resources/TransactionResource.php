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
            "id" => $this->id,
            "time" => $this->time,
            "amount" => $this->amount,
            "account" => [
                'order_id' => $this->order_id
            ],
            "create_time" => intval($this->create_time),
            "perform_time" => intval($this->perform_time),
            "cancel_time" => intval($this->cancel_time),
            "transaction" => $this->transaction,
            "state" => $this->state,
            "reason" => $this->reason,
        ];
    }
}
