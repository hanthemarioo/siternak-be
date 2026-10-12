<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFlockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $flock = $this->route('flock'); // null kalau store

        if (!$user || $user->isUserInput()) {
            return false;
        }

        // ===== SUPER ADMIN =====
        if ($user->role === 'super_admin') {
            if ($flock) {
                // UPDATE
                return $user->ownedFarms()
                    ->where('id', $flock->farm_id)
                    ->exists();
            }

            // STORE
            return $user->ownedFarms()
                ->where('id', $this->farm_id)
                ->exists();
        }

        // ===== USER BIASA =====
        if ($flock) {
            // UPDATE
            return (int) $user->farm_id === (int) $flock->farm_id;
        }

        // STORE
        return (int) $user->farm_id === (int) $this->farm_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $flock = $this->route('flock');

        if ($flock) {
            // ===== UPDATE =====
            return [
                'name' => 'nullable|string|max:255',
            ];
        }

        // ===== STORE =====
        return [
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'house_code' => 'required|string|max:255',
            'age_in_day' => 'required|integer|min:0',
            'strain' => 'required|string|max:255',
            'start_date' => 'required|date',

            'coops' => 'required|array|min:1',
            'coops.*.name' => 'required|string|max:255',
            'coops.*.initial_population' => 'required|integer|min:0',
        ];
    }
}
