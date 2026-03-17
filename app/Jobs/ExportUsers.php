<?php

namespace App\Jobs;

use App\Enums\User\UserRoleEnum;
use App\Exports\UsersExport;
use App\Mail\UsersExportReady;
use App\Models\User;
use App\Models\UserExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

class ExportUsers implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $exportId,
        /**
         * @var array<int, string>
         */
        private array $roles,
        private string $format
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $export = UserExport::query()->find($this->exportId);

        if (! $export) {
            return;
        }

        $export->forceFill([
            'status' => 'processing',
            'started_at' => now(),
        ])->save();

        $writerType = match ($this->format) {
            'xlsx' => Excel::XLSX,
            'xls' => Excel::XLS,
            default => Excel::CSV,
        };

        $fileName = $export->file_name ?: sprintf('users-export-%s.%s', $export->id, $this->format);
        $filePath = sprintf('exports/users/%s', $fileName);

        try {
            ExcelFacade::store(new UsersExport($this->roles), $filePath, 'local', $writerType);

            $export->load('user');
            $usersExported = User::query()->whereIn('role', $this->roles)->count();

            $export->forceFill([
                'file_path' => $filePath,
                'file_name' => $fileName,
                'users_exported' => $usersExported,
            ])->save();

            if ($export->user?->role === UserRoleEnum::Faculty) {
                try {
                    Mail::to($export->user)->send(new UsersExportReady($export));
                } catch (\Throwable $exception) {
                    $export->forceFill([
                        'status' => 'failed',
                        'finished_at' => now(),
                        'error_message' => Str::limit('Failed to email the export to your account.', 500),
                    ])->save();

                    return;
                }
            }

            $export->forceFill([
                'status' => 'finished',
                'finished_at' => now(),
                'error_message' => null,
            ])->save();
        } catch (\Throwable $exception) {
            $export->forceFill([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => Str::limit($exception->getMessage(), 500),
            ])->save();
        }
    }
}
