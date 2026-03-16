<?php

namespace App\Http\Requests\Users;

use App\Enums\User\UserRoleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRoleEnum::SuperAdmin, UserRoleEnum::Admin], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'name')->ignore($userId),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'lowercase',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'campus_id' => [
                Rule::requiredIf(in_array($this->input('role'), [UserRoleEnum::Faculty->value, UserRoleEnum::Students->value], true)),
                'nullable',
                'string',
                'max:255',
                'regex:/^\\d+$/',
                Rule::unique('users', 'campus_id')->ignore($userId),
            ],
            'role' => ['required', Rule::in(array_map(fn (UserRoleEnum $role) => $role->value, UserRoleEnum::cases()))],
            'password' => [
                Rule::excludeIf(fn () => blank($this->input('password'))),
                'nullable',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('password')) {
            $this->request->remove('password');
            $this->request->remove('password_confirmation');
        }
    }
}
