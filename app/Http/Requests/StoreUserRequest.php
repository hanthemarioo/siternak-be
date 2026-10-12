<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $auth = $this->user();
        $user = $this->route('user');

        if (!$auth?->isSuperAdmin()) {
            return false;
        }

        if (!$user) {
            return true;
        }

        return $user->created_by === $auth->id
            && in_array($user->role, ['admin', 'user_input']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $auth = $this->user();
        $user = $this->route('user');
        $userId = $user?->id;

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone_number')->ignore($userId),
            ],

            'role' => [
                'required',
                Rule::in(['admin', 'user_input']),
            ],

            'password' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                Password::min(8)->letters()->numbers(),
            ],

            'farm_id' => [
                'nullable',
                Rule::exists('farms', 'id')->where('owner_id', $auth->id),
            ],
        ];
    }
}
