<x-mail::message>
<div style="text-align: center;">
    <svg
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
        aria-label="{{ config('app.name') }} logo"
        role="img"
        style="display: block; margin: 0 auto 12px; width: 48px; height: 48px; fill: #111827;"
    >
        <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M12 12a4 4 0 1 0-4-4a4 4 0 0 0 4 4Zm0 2c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5Z"
        />
    </svg>
    <h1 style="margin: 0 0 6px; font-size: 22px; line-height: 1.3;">
        User Management
    </h1>
    <p style="margin: 0 0 18px; color: #6b7280; font-size: 14px;">
        Users Export Notification
    </p>
</div>

<div style="text-align: center; font-size: 16px; margin-bottom: 18px;">
    Your users export is ready. The file is attached to this email.
</div>

<div style="margin: 0 auto 20px; max-width: 360px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px;">
    <div style="font-size: 14px; margin-bottom: 8px;">
        <strong>Role filter:</strong> {{ $export->role_filter ?? 'All (Student + Faculty)' }}
    </div>
    <div style="font-size: 14px; margin-bottom: 8px;">
        <strong>Format:</strong> {{ strtoupper($export->format) }}
    </div>
    <div style="font-size: 14px;">
        <strong>Users exported:</strong> {{ $export->users_exported ?? '-' }}
    </div>
</div>

<div style="text-align: center;">
    Thanks,<br>
    User Management
</div>
</x-mail::message>
