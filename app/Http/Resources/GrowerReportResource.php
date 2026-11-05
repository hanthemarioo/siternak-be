<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrowerReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this['date'],
            'age_week' => $this['age_week'],
            'population' => $this['population'],
            'mortality' => $this['mortality'],
            'culling' => $this['culling'],
            'mortality_percent' => $this['mortality_percent'],
            'feed_total_kg' => $this['feed_total_kg'],
            'feed_gram_per_bird' => $this['feed_gram_per_bird'],
            'feed_standard_gram_per_bird' => $this['feed_standard_gram_per_bird'],
            'water_total_l' => $this['water_total_l'],
            'water_ml_per_bird' => $this['water_ml_per_bird'],
            'actual_body_weight_g' => $this['actual_body_weight_g'],
            'standard_body_weight_g' => $this['standard_body_weight_g'],
            'temperature_c' => $this['temperature_c'],
            'actual_uniformity_percent' => $this['actual_uniformity_percent'],
            'standard_uniformity_percent' => $this['standard_uniformity_percent'],
            'feed_name' => $this['feed_name'],
            'medicine_vitamin' => $this['medicine_vitamin'],
            'vaccine' => $this['vaccine'],
            'notes' => $this['notes'],
        ];
    }
}
