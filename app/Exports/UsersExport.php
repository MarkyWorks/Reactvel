<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(private array $roles) {}

    public function query(): Builder
    {
        return User::query()
            ->select(['campus_id', 'name', 'email', 'role', 'created_at'])
            ->whereIn('role', $this->roles)
            ->orderBy('created_at');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['campus_id', 'name', 'email', 'role', 'created_at'];
    }

    /**
     * @return array<int, string|null>
     */
    public function map($user): array
    {
        return [
            $user->campus_id,
            $user->name,
            $user->email,
            $user->role?->value ?? $user->role,
            $user->created_at?->toDateTimeString(),
        ];
    }
}
