<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseFlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $flock = $this->route('flock');

        if (!$user || !$flock || $user->isUserInput()) {
            return false;
        }

        $flock->loadMissing('farm');

        if ($user->isSuperAdmin()) {
            return $flock->farm->owner_id === $user->id;
        }

        return $flock->farm_id === $user->farm_id;
    }

    public function rules(): array
    {
        return [
            'closed_at' => ['required', 'date', 'before_or_equal:today'],
            'closing_reason' => [
                'required',
                Rule::in(['harvested', 'depopulated', 'disease', 'sold', 'terminated', 'other']),
            ],
            'closing_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
