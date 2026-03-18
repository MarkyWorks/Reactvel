<?php

namespace App\Http\Requests\Users;

use App\Enums\User\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            [UserRoleEnum::SuperAdmin, UserRoleEnum::Admin, UserRoleEnum::Faculty],
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
            'role' => [
                'nullable',
                'string',
            ],
            'format' => [
                'required',
                'string',
                Rule::in(['csv', 'xlsx', 'xls']),
            ],
        ];
    }
}
