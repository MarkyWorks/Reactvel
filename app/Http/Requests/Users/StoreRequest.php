<?php

namespace App\Http\Requests\Users;

use App\Enums\User\UserRoleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255','unique:users,name'],
            'email' => ['required', 'string', 'email', 'max:255', 'lowercase', 'unique:users,email'],
            'role' => ['required', Rule::in(array_map(fn (UserRoleEnum $role) => $role->value, UserRoleEnum::cases()))],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'password.mixed_case' => 'Password must contain both uppercase and lowercase letters.',
        ];
    }
}
