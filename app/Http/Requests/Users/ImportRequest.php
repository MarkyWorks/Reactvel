<?php

namespace App\Http\Requests\Users;

use App\Enums\User\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            [UserRoleEnum::SuperAdmin, UserRoleEnum::Admin],
            true
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'users_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ];
    }
}
