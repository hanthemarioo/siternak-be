<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayerReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this['date'],
            'age_week' => $this['age_week'],
            'age_day' => $this['age_day'],
            'population' => $this['population'],
            'mortality' => $this['mortality'],
            'current_population' => $this['current_population'],
            'feed_total_kg' => $this['feed_total_kg'],
            'feed_gram_per_bird' => $this['feed_gram_per_bird'],
            'water_total_l' => $this['water_total_l'],
            'water_ml_per_bird' => $this['water_ml_per_bird'],
            'normal_egg_count' => $this['normal_egg_count'],
            'normal_egg_weight_kg' => $this['normal_egg_weight_kg'],
            'sorted_egg_count' => $this['sorted_egg_count'],
            'sorted_egg_weight_kg' => $this['sorted_egg_weight_kg'],
            'broken_egg_count' => $this['broken_egg_count'],
            'broken_egg_weight_kg' => $this['broken_egg_weight_kg'],
            'spoiled_egg_count' => $this['spoiled_egg_count'],
            'spoiled_egg_weight_kg' => $this['spoiled_egg_weight_kg'],
            'avg_egg_weight_g' => $this['avg_egg_weight_g'],
            'egg_weight_per_1000_birds' => $this['egg_weight_per_1000_birds'],
            'hen_day_production' => $this['hen_day_production'],
            'fcr' => $this['fcr'],
            'feed_name' => $this['feed_name'],
            'medicine_vitamin' => $this['medicine_vitamin'],
            'vaccine' => $this['vaccine'],
            'notes' => $this['notes'],
        ];
    }
}
