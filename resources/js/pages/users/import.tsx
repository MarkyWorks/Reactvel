import { Head, Link, router, useForm } from '@inertiajs/react';
import { FileUp, Upload } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
    {
        title: 'Import',
        href: '/users/import',
    },
];

type ImportRow = {
    id: string;
    uploaded_at: string | null;
    user_email: string | null;
    file_name: string;
    started_at: string | null;
    finished_at: string | null;
    users_read: number;
    users_saved: number;
    status: string;
    error_message: string | null;
    errors: string[];
};

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type UsersImportProps = {
    imports: {
        data: ImportRow[];
        from: number | null;
        to: number | null;
        total: number;
        links: PaginatorLink[];
    };
    uploadedDate: string;
};

const formatDateTime = (value: string | null) => {
    if (!value) {
        return '-';
    }

    const isoValue = value.includes('T') ? value : value.replace(' ', 'T');
    const parsedDate = new Date(isoValue);

    if (Number.isNaN(parsedDate.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(parsedDate);
};

const statusClass = (status: string) => {
    if (status === 'finished') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
    }

    if (status === 'failed') {
        return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
    }

    return 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300';
};

export default function UsersImport({ imports, uploadedDate }: UsersImportProps) {
    const form = useForm({
        users_file: null as File | null,
    });
    const [filterDate, setFilterDate] = useState(uploadedDate ?? '');

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post('/users/import', {
            forceFormData: true,
        });
    };

    const submitFilter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            '/users/import',
            {
                uploaded_date: filterDate,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Import Users" />

            <div className="py-8">
                <div className="mx-auto w-full max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-6">
                        <div>
                            <h1 className="text-2xl font-semibold text-neutral-900 dark:text-white">Import Users</h1>
                            <p className="text-sm text-neutral-600 dark:text-neutral-300">
                                Upload CSV/XLSX/XLS files to bulk create or update users.
                            </p>
                        </div>

                        <div className="rounded-box border border-black/10 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-white/10 dark:bg-neutral-950/70">
                            <form className="space-y-4" onSubmit={submit}>
                                <label
                                    htmlFor="users_file"
                                    className="block cursor-pointer rounded-xl border-2 border-dashed border-emerald-200 bg-emerald-50/60 p-8 text-center transition hover:border-emerald-300 hover:bg-emerald-50 dark:border-emerald-900/60 dark:bg-emerald-900/20 dark:hover:border-emerald-700"
                                >
                                    <input
                                        id="users_file"
                                        name="users_file"
                                        type="file"
                                        accept=".csv,.xlsx,.xls"
                                        className="hidden"
                                        onChange={(event) =>
                                            form.setData('users_file', event.target.files?.[0] ?? null)
                                        }
                                    />
                                    <div className="flex flex-col items-center gap-3">
                                        <FileUp className="size-6 text-emerald-600 dark:text-emerald-300" />
                                        <div>
                                            <p className="text-base font-semibold text-neutral-900 dark:text-white">
                                                Drag and drop file here
                                            </p>
                                            <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                                or click to select from your computer
                                            </p>
                                        </div>
                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                            Required columns: campus_id, name, email, role, password
                                        </p>
                                    </div>
                                </label>

                                <InputError message={form.errors.users_file} />

                                <div className="flex items-center justify-between gap-3">
                                    <Link
                                        href="/users"
                                        className="text-sm font-medium text-neutral-600 underline underline-offset-4 hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white"
                                    >
                                        Back to Users
                                    </Link>
                                    <Button type="submit" disabled={form.processing || !form.data.users_file}
                                    >
                                        <span className="inline-flex items-center gap-2">
                                            Upload
                                            <Upload className="size-4" />
                                        </span>
                                    </Button>
                                </div>
                            </form>
                        </div>

                        <div className="rounded-box border border-black/10 bg-white/40 shadow-sm backdrop-blur dark:border-white/10 dark:bg-neutral-950/40">
                            <div className="border-b border-black/5 px-6 py-4 dark:border-white/10">
                                <div className="flex flex-wrap items-center justify-between gap-3 md:flex-nowrap">
                                    <h2 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Imported Users Results
                                    </h2>

                                    <form className="flex items-center gap-2" onSubmit={submitFilter}>
                                        <label
                                            htmlFor="uploaded_date"
                                            className="text-sm font-medium whitespace-nowrap text-neutral-700 dark:text-neutral-200"
                                        >
                                            Uploaded Date
                                        </label>
                                        <input
                                            id="uploaded_date"
                                            name="uploaded_date"
                                            type="date"
                                            value={filterDate}
                                            onChange={(event) => {
                                                const value = event.target.value;
                                                setFilterDate(value);
                                                router.get(
                                                    '/users/import',
                                                    { uploaded_date: value },
                                                    {
                                                        preserveState: true,
                                                        preserveScroll: true,
                                                        replace: true,
                                                    },
                                                );
                                            }}
                                            className="rounded-box border border-black/10 bg-white p-2 text-sm text-neutral-800 shadow-none transition-colors duration-200 focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-300 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                                        />

                                        {filterDate !== '' && (
                                            <Link
                                                href="/users/import"
                                                className="inline-flex items-center rounded-box bg-black/5 px-3 py-2 text-xs font-medium text-neutral-900 transition-colors hover:bg-black/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
                                            >
                                                Clear
                                            </Link>
                                        )}
                                    </form>
                                </div>
                            </div>

                            <div className="overflow-x-auto p-6">
                                <table className="min-w-full divide-y-2 divide-gray-200 text-left dark:divide-white/10">
                                    <thead className="text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                        <tr>
                                            <th className="px-3 py-2 whitespace-nowrap">Uploaded Time</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Email</th>
                                            <th className="px-3 py-2 whitespace-nowrap">File Name</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Start At</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Finish At</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Users Read</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Users Saved</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Status</th>
                                            <th className="px-3 py-2">Error</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 text-sm dark:divide-white/10">
                                        {imports.data.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={9}
                                                    className="px-3 py-6 text-center text-neutral-700 dark:text-neutral-200"
                                                >
                                                    No import history yet.
                                                </td>
                                            </tr>
                                        ) : (
                                            imports.data.map((importRow) => (
                                                <tr key={importRow.id}>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {formatDateTime(importRow.uploaded_at)}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {importRow.user_email ?? '-'}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {importRow.file_name}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {formatDateTime(importRow.started_at)}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {formatDateTime(importRow.finished_at)}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {importRow.users_read}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {importRow.users_saved}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap">
                                                        <span
                                                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${statusClass(importRow.status)}`}
                                                        >
                                                            {importRow.status.charAt(0).toUpperCase() +
                                                                importRow.status.slice(1)}
                                                        </span>
                                                    </td>
                                                    <td className="px-3 py-3 text-neutral-900 dark:text-neutral-100">
                                                        <div className="space-y-2">
                                                            <p
                                                                className={`${importRow.errors.length > 0 ? 'hidden ' : ''}break-words`}
                                                            >
                                                                {importRow.error_message ?? '-'}
                                                            </p>
                                                            {importRow.errors.length > 0 && (
                                                                <details className="rounded-lg border border-red-200/70 bg-red-50/60 p-2 dark:border-red-900/60 dark:bg-red-900/20">
                                                                    <summary className="cursor-pointer text-xs font-semibold text-red-700 dark:text-red-300">
                                                                        Row failures ({importRow.errors.length})
                                                                    </summary>
                                                                    <ul className="mt-2 list-disc space-y-1 pl-5 text-xs text-red-700 dark:text-red-200">
                                                                        {importRow.errors.map((rowError, index) => (
                                                                            <li key={`${importRow.id}-error-${index}`} className="break-words">
                                                                                {rowError}
                                                                            </li>
                                                                        ))}
                                                                    </ul>
                                                                </details>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="border-t border-black/5 px-6 py-4 dark:border-white/10">
                                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <p className="text-sm text-neutral-600 dark:text-neutral-300">
                                        Showing {imports.from ?? 0} to {imports.to ?? 0} of {imports.total}
                                    </p>
                                    <div className="flex flex-wrap gap-2 md:justify-end">
                                        {imports.links.map((link, index) => (
                                            <Button
                                                key={`${link.label}-${index}`}
                                                variant={link.active ? 'default' : 'outline'}
                                                disabled={!link.url}
                                                asChild={Boolean(link.url)}
                                            >
                                                {link.url ? (
                                                    <Link href={link.url} preserveScroll preserveState>
                                                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                                    </Link>
                                                ) : (
                                                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                                )}
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
