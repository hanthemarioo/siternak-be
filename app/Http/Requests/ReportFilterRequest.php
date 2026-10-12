<?php

namespace App\Http\Requests;

use App\Models\Coop;
use App\Models\Farm;
use App\Models\Flock;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $farmId = $this->input('farm_id');

        if (!$user || !$farmId) {
            return false;
        }

        $farm = Farm::find($farmId);

        if (!$farm) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return $farm->owner_id === $user->id;
        }

        return $farm->id === $user->farm_id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'flock_id' => ['nullable', 'integer', 'exists:flocks,id'],
            'coop_id' => ['nullable', 'integer', 'exists:coops,id'],
            'period_type' => ['required', Rule::in(['7_days', '14_days', '30_days', 'custom'])],
            'start_date' => ['required_if:period_type,custom', 'nullable', 'date'],
            'end_date' => ['required_if:period_type,custom', 'nullable', 'date', 'after_or_equal:start_date'],
            'format' => ['nullable', Rule::in(['xlsx', 'pdf', 'XLSX', 'PDF'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $farmId = (int) $this->input('farm_id');
            $flockId = $this->input('flock_id');
            $coopId = $this->input('coop_id');

            if ($flockId && ! Flock::query()->whereKey($flockId)->where('farm_id', $farmId)->exists()) {
                $validator->errors()->add('flock_id', 'Flock yang dipilih tidak berada di farm ini.');
            }

            if (! $coopId) {
                return;
            }

            $coopBelongsToFilter = Coop::query()
                ->whereKey($coopId)
                ->whereHas('flock', function ($query) use ($farmId, $flockId) {
                    $query->where('farm_id', $farmId);

                    if ($flockId) {
                        $query->whereKey($flockId);
                    }
                })
                ->exists();

            if (! $coopBelongsToFilter) {
                $validator->errors()->add('coop_id', 'Coop yang dipilih tidak sesuai dengan farm/flock filter.');
            }
        });
    }

    public function filters(): array
    {
        $validated = $this->validated();
        $periodType = $validated['period_type'];
        $endDate = today();
        $startDate = match ($periodType) {
            '7_days' => today()->subDays(6),
            '14_days' => today()->subDays(13),
            '30_days' => today()->subDays(29),
            default => null,
        };

        if ($periodType === 'custom') {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
        }

        return [
            'farm_id' => (int) $validated['farm_id'],
            'flock_id' => isset($validated['flock_id']) ? (int) $validated['flock_id'] : null,
            'coop_id' => isset($validated['coop_id']) ? (int) $validated['coop_id'] : null,
            'period_type' => $periodType,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }

    public function exportFormat(): string
    {
        return strtolower($this->input('format', 'xlsx'));
    }
}
