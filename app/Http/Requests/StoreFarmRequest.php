<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFarmRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $farm = $this->route('farm'); // null kalau store

        // ===== STORE =====
        if (!$farm) {
            return $user->role === 'super_admin';
        }

        // ===== UPDATE =====
        // Super admin (owner)
        if ($user->isSuperAdmin()) {
            $ownerId = $farm instanceof \App\Models\Farm ? $farm->owner_id : \App\Models\Farm::find($farm)?->owner_id;
            return (int) $ownerId === (int) $user->id;
        }

        // Admin update farm owner nya
        // if ($user->isAdmin()) {
        //     return $farm->owner_id === $user->created_by;
        // }

        // User input tidak boleh
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $farm = $this->route('farm');

        if ($farm) {
            return [
                'name'     => 'sometimes|string|max:255',
                'type'     => 'sometimes|in:layer,grower',
                'location' => 'sometimes|string|max:500',
            ];
        }

        return [
            'name'     => 'required|string|max:255',
            'type'     => 'required|in:layer,grower',
            'location' => 'required|string|max:500',
            // 'owner_id' => 'required|exists:users,id',
        ];
    }
}
