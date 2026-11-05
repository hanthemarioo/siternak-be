<?php

namespace App\Http\Requests;

use App\Models\Coop;
use App\Models\DailyFlockRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGrowerPhaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $growerPhase = $this->route('growerPhase') ?? $this->route('grower_phase');

        if (!$user) {
            return false;
        }

        $coopId = $this->input('coop_id');

        if (!$coopId) {
            return false;
        }

        // Ambil farm dari coop → flock → farm
        $coop = Coop::with('flock.farm')->find($coopId);

        if (!$coop || !$coop->flock || !$coop->flock->farm) {
            return false;
        }

        $farm = $coop->flock->farm;

        // farm must grower
        if ($farm->type !== 'grower') {
            return false;
        }

        if (!$growerPhase && $coop->flock->status !== 'active') {
            return false;
        }

        // SUPER ADMIN (OWNER)
        if ($user->isSuperAdmin()) {
            return $farm->owner_id === $user->id;
        }

        // RESTRICT USER INPUT TO UPDATE
        if ($growerPhase && $user->isUserInput()) {
            return false;
        }

        // ADMIN / USER INPUT
        return $farm->id === $user->farm_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'coop_id' => ['required', 'exists:coops,id'],
            // 'date' => ['required', 'date'],

            'feed_kg' => ['required', 'numeric', 'gt:0'],
            'water_l' => ['required', 'numeric', 'gt:0'],
            'avg_body_weight_kg' => ['required', 'numeric', 'gt:0'],

            'mortality_count' => ['required', 'integer', 'min:0'],
            'culling_count' => ['required', 'integer', 'min:0'],

            'temperature_c' => ['required', 'numeric', 'between:10,50'],
            'uniformity_percent' => ['nullable', 'numeric', 'between:0,100'],
            'feed_name' => ['nullable', 'string', 'max:255'],
            'medicine_vitamin' => ['nullable', 'string', 'max:255'],
            'vaccine' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $coopId = $this->input('coop_id');
            $record = $this->route('grower_phase');

            $date = $this->input('date')
                ?? $record?->date
                ?? now()->toDateString();

            $query = DailyFlockRecord::where('coop_id', $coopId)
                ->whereDate('date', $date);

            if ($record?->id) {
                $query->where('id', '!=', $record->id);
            }

            $exists = $query->exists();

            if ($exists) {
                $validator->errors()->add('coop_id', 'Data untuk kandang ini pada tanggal tersebut sudah diinput.');
            }
        });
    }
}
