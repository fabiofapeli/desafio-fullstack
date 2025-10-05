<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray($request): array
    {
        // suporta tanto array quanto objeto
        $data = (object) $this->resource;

        return [
            'id' => $data->id ?? null,
            'description' => $data->description ?? null,
            'numberOfClients' => $data->numberOfClients ?? null,
            'gigabytesStorage' => $data->gigabytesStorage ?? null,
            'price' => (float) ($data->price ?? 0),
            'active' => (bool) ($data->active ?? false),
            'created_at' => $data->created_at ?? null,
            'updated_at' => $data->updated_at ?? null,
        ];
    }
}
