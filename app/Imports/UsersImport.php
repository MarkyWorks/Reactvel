<?php

namespace App\Imports;

use App\Enums\User\UserRoleEnum;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements SkipsEmptyRows, ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    /**
     * @var array<int, string>
     */
    private array $rowErrors = [];

    private int $readCount = 0;

    private int $savedCount = 0;

    public function __construct(private ?string $userId) {}

    public function collection(Collection $collection): void
    {
        $rows = $collection
            ->map(fn (array $row) => [
                'campus_id' => isset($row['campus_id']) ? trim((string) $row['campus_id']) : null,
                'name' => isset($row['name']) ? trim((string) $row['name']) : null,
                'email' => isset($row['email']) ? strtolower(trim((string) $row['email'])) : null,
                'role' => isset($row['role']) ? trim((string) $row['role']) : null,
                'password' => isset($row['password']) ? (string) $row['password'] : null,
            ])
            ->filter(fn (array $row) => $row['name'] && $row['email'] && $row['role'])
            ->values();

        $this->readCount += $rows->count();

        if ($rows->isEmpty()) {
            return;
        }

        $validRoles = collect(UserRoleEnum::cases())->map(fn (UserRoleEnum $role) => $role->value);

        $normalized = $rows->filter(function (array $row, int $index) use ($validRoles) {
            if (! $validRoles->contains($row['role'])) {
                $this->rowErrors[] = sprintf('Row %d: Invalid role "%s".', $index + 2, (string) $row['role']);

                return false;
            }

            if (in_array($row['role'], [UserRoleEnum::Faculty->value, UserRoleEnum::Student->value], true)) {
                if (! $row['campus_id']) {
                    $this->rowErrors[] = sprintf('Row %d: Campus ID is required.', $index + 2);

                    return false;
                }

                return ctype_digit($row['campus_id']);
            }

            if ($row['campus_id'] !== null && $row['campus_id'] !== '' && ! ctype_digit($row['campus_id'])) {
                $this->rowErrors[] = sprintf('Row %d: Campus ID must be numeric.', $index + 2);

                return false;
            }

            return true;
        })->values();

        if ($normalized->isEmpty()) {
            return;
        }

        $emails = $normalized->pluck('email')->filter()->unique()->values();
        $campusIds = $normalized
            ->pluck('campus_id')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->unique()
            ->values();

        $existingByEmail = User::query()
            ->whereIn('email', $emails)
            ->get(['id', 'email', 'campus_id', 'password'])
            ->keyBy('email');

        $existingByCampus = $campusIds->isEmpty()
            ? collect()
            : User::query()->whereIn('campus_id', $campusIds)->get(['id', 'email', 'campus_id'])->keyBy('campus_id');

        $upsertRows = $normalized->filter(function (array $row) use ($existingByCampus) {
            if (! $row['campus_id']) {
                return true;
            }

            $conflict = $existingByCampus->get($row['campus_id']);

            if (! $conflict || $conflict->email === $row['email']) {
                return true;
            }

            $this->rowErrors[] = sprintf('Row for %s skipped: Campus ID already assigned.', $row['email']);

            return false;
        })->map(function (array $row) use ($existingByEmail) {
            $password = $row['password'] !== null && $row['password'] !== ''
                ? Hash::make($row['password'])
                : null;

            $existingUser = $existingByEmail->get($row['email']);

            if (! $password && $existingUser) {
                $password = $existingUser->password;
            }

            if (! $password && ! $existingUser) {
                $password = Hash::make(Str::random(12));
            }

            return [
                'campus_id' => $row['campus_id'] ?: null,
                'name' => $row['name'],
                'email' => $row['email'],
                'role' => $row['role'],
                'password' => $password,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->values();

        if ($upsertRows->isEmpty()) {
            return;
        }

        User::query()->upsert(
            $upsertRows->all(),
            ['email'],
            ['campus_id', 'name', 'role', 'password', 'updated_at'],
        );

        $this->savedCount += $upsertRows->count();
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function usersRead(): int
    {
        return $this->readCount;
    }

    public function usersSaved(): int
    {
        return $this->savedCount;
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->rowErrors;
    }
}
