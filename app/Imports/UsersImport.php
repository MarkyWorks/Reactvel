<?php

namespace App\Imports;

use App\Enums\User\UserRoleEnum;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /**
     * @var array<int, array<string, string|null>>
     */
    private array $savedRows = [];

    public function __construct(private ?string $userId) {}

    public function collection(Collection $collection): void
    {
        $rows = $collection->map(function ($row) {
            $rowData = $row instanceof Collection ? $row->toArray() : (array) $row;
            $hasHeadings = array_key_exists('campus_id', $rowData)
                || array_key_exists('name', $rowData)
                || array_key_exists('email', $rowData)
                || array_key_exists('role', $rowData);

            if (! $hasHeadings) {
                $values = array_values($rowData);
                $rowData = [
                    'campus_id' => $values[0] ?? null,
                    'name' => $values[1] ?? null,
                    'email' => $values[2] ?? null,
                    'role' => $values[3] ?? null,
                    'password' => $values[4] ?? null,
                ];
            }

            return [
                'campus_id' => isset($rowData['campus_id']) ? trim((string) $rowData['campus_id']) : null,
                'name' => isset($rowData['name']) ? trim((string) $rowData['name']) : null,
                'email' => isset($rowData['email']) ? strtolower(trim((string) $rowData['email'])) : null,
                'role' => isset($rowData['role']) ? trim((string) $rowData['role']) : null,
                'password' => isset($rowData['password']) ? (string) $rowData['password'] : null,
            ];
        })->values();

        $this->readCount += $rows->count();

        if ($rows->isEmpty()) {
            return;
        }

        $validRoles = collect(UserRoleEnum::cases())->map(fn (UserRoleEnum $role) => $role->value);

        $seenEmails = [];
        $seenNames = [];
        $seenCampusIds = [];
        $normalized = collect();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $issues = [];

            if (! $row['campus_id'] || ! $row['name'] || ! $row['email'] || ! $row['role']) {
                $issues[] = sprintf('Row %d: campus_id, name, email, and role are required.', $rowNumber);
            }

            if ($row['campus_id'] && ! ctype_digit($row['campus_id'])) {
                $issues[] = sprintf('Row %d: Campus ID must be numeric.', $rowNumber);
            }

            if ($row['role'] && ! $validRoles->contains($row['role'])) {
                $issues[] = sprintf('Row %d: Invalid role "%s".', $rowNumber, (string) $row['role']);
            }

            $emailKey = $row['email'];
            $nameKey = strtolower((string) $row['name']);
            $campusKey = $row['campus_id'];

            if ($emailKey && array_key_exists($emailKey, $seenEmails)) {
                $issues[] = sprintf('Row %d: Email already exists in this file.', $rowNumber);
            }

            if ($nameKey !== '' && array_key_exists($nameKey, $seenNames)) {
                $issues[] = sprintf('Row %d: Name already exists in this file.', $rowNumber);
            }

            if ($campusKey && array_key_exists($campusKey, $seenCampusIds)) {
                $issues[] = sprintf('Row %d: Campus ID already exists in this file.', $rowNumber);
            }

            if ($emailKey) {
                $seenEmails[$emailKey] = true;
            }

            if ($nameKey !== '') {
                $seenNames[$nameKey] = true;
            }

            if ($campusKey) {
                $seenCampusIds[$campusKey] = true;
            }

            if ($issues !== []) {
                array_push($this->rowErrors, ...$issues);

                continue;
            }

            $normalized->push([
                'row_number' => $rowNumber,
                ...$row,
            ]);
        }

        if ($normalized->isEmpty()) {
            return;
        }

        $emails = $normalized->pluck('email')->unique()->values();
        $campusIds = $normalized->pluck('campus_id')->unique()->values();
        $names = $normalized->pluck('name')->unique()->values();

        $existingByEmail = $emails->isEmpty()
            ? collect()
            : User::query()->whereIn('email', $emails)->get(['email'])->keyBy('email');
        $existingByCampus = $campusIds->isEmpty()
            ? collect()
            : User::query()->whereIn('campus_id', $campusIds)->get(['campus_id'])->keyBy('campus_id');
        $existingByName = $names->isEmpty()
            ? collect()
            : User::query()->whereIn('name', $names)->get(['name'])->keyBy('name');

        $insertRows = $normalized->filter(function (array $row) use ($existingByEmail, $existingByCampus, $existingByName) {
            $rowNumber = $row['row_number'];
            $issues = [];

            if ($existingByEmail->has($row['email'])) {
                $issues[] = sprintf('Row %d: Email already exists.', $rowNumber);
            }

            if ($existingByCampus->has($row['campus_id'])) {
                $issues[] = sprintf('Row %d: Campus ID already exists.', $rowNumber);
            }

            if ($existingByName->has($row['name'])) {
                $issues[] = sprintf('Row %d: Name already exists.', $rowNumber);
            }

            if ($issues !== []) {
                array_push($this->rowErrors, ...$issues);

                return false;
            }

            return true;
        })->map(function (array $row) {
            $password = $row['password'] !== null && $row['password'] !== ''
                ? $row['password']
                : null;

            if (! $password) {
                $baseName = trim((string) Str::of($row['name'])->replaceMatches('/[^A-Za-z0-9\\s]/', ''));
                $firstName = Str::of($baseName)->squish()->explode(' ')->first();
                $password = $firstName !== '' ? $firstName.now()->year : 'User'.now()->year;
            }

            return [

                'campus_id' => $row['campus_id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'role' => $row['role'],
                'password' => $password,
            ];
        })->values();

        if ($insertRows->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($insertRows) {
            $now = now();

            $insertRows
                ->chunk(500)
                ->each(function (Collection $chunk) use ($now) {
                    $payload = $chunk->map(function (array $row) use ($now) {
                        return [
                            'id' => (string) Str::uuid(),
                            'campus_id' => $row['campus_id'],
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'role' => $row['role'],
                            'password' => Hash::make($row['password']),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })->values()->all();

                    if ($payload === []) {
                        return;
                    }

                    User::query()->insert($payload);

                    $this->savedCount += count($payload);
                    $this->savedRows = array_merge($this->savedRows, $chunk->map(fn (array $row) => [
                        'campus_id' => $row['campus_id'],
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'role' => $row['role'],
                    ])->all());
                });
        });
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
     * @return array<int, array<string, string|null>>
     */
    public function savedRows(): array
    {
        return $this->savedRows;
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->rowErrors;
    }
}
