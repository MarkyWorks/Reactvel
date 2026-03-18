import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
    {
        title: 'Export',
        href: '/users/export',
    },
];

type ExportRow = {
    id: string;
    requested_at: string | null;
    user_email: string | null;
    file_name: string;
    format: string;
    role_filter: string | null;
    started_at: string | null;
    finished_at: string | null;
    users_exported: number;
    status: string;
    error_message: string | null;
    status_url: string;
    download_url: string | null;
    delivery_method: 'download' | 'email';
};

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type UsersExportProps = {
    exports: {
        data: ExportRow[];
        from: number | null;
        to: number | null;
        total: number;
        links: PaginatorLink[];
    };
    requestedDate: string;
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

const formatRoleFilter = (role: string | null) => {
    if (!role) {
        return 'All (Student + Faculty)';
    }

    return role;
};

export default function UsersExport({ exports, requestedDate }: UsersExportProps) {
    const [filterDate, setFilterDate] = useState(requestedDate ?? '');
    const [rows, setRows] = useState(exports.data);
    const rowsRef = useRef(rows);
    const notifiedIds = useRef(new Set<string>());

    useEffect(() => {
        // eslint-disable-next-line react-hooks/set-state-in-effect
        setRows(exports.data);
    }, [exports.data]);

    useEffect(() => {
        rowsRef.current = rows;
    }, [rows]);

    useEffect(() => {
        const notify = (type: 'success' | 'error', message: string) => {
            window.dispatchEvent(
                new CustomEvent('notify', {
                    detail: {
                        type,
                        message,
                    },
                }),
            );
        };

        const poll = async () => {
            const pendingRows = rowsRef.current.filter((row) =>
                ['queued', 'processing'].includes(row.status),
            );

            if (pendingRows.length === 0) {
                return;
            }

            await Promise.all(
                pendingRows.map(async (row) => {
                    try {
                        const response = await fetch(row.status_url, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const data = await response.json();

                        setRows((currentRows) =>
                            currentRows.map((currentRow) => {
                                if (currentRow.id !== row.id) {
                                    return currentRow;
                                }

                                return {
                                    ...currentRow,
                                    status: data.status ?? currentRow.status,
                                    started_at: data.started_at ?? currentRow.started_at,
                                    finished_at: data.finished_at ?? currentRow.finished_at,
                                    users_exported:
                                        typeof data.users_exported === 'number'
                                            ? data.users_exported
                                            : currentRow.users_exported,
                                    error_message: data.error_message ?? currentRow.error_message,
                                    download_url: data.download_url ?? currentRow.download_url,
                                    delivery_method: data.delivery_method ?? currentRow.delivery_method,
                                };
                            }),
                        );

                        if (notifiedIds.current.has(row.id)) {
                            return;
                        }

                        if (data.status === 'finished') {
                            notifiedIds.current.add(row.id);
                            if (data.delivery_method === 'email') {
                                notify('success', 'User export has been emailed to your account.');
                            } else {
                                notify('success', 'User export is ready for download.');
                            }
                        }

                        if (data.status === 'failed') {
                            notifiedIds.current.add(row.id);
                            notify('error', data.error_message || 'User export failed.');
                        }
                    } catch (error) {
                        console.error('Export status polling failed:', error);
                    }
                }),
            );

        };

        const intervalId = window.setInterval(() => {
            void poll();
        }, 2000);

        void poll();

        return () => window.clearInterval(intervalId);
    }, []);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Export Users" />

            <div className="py-8">
                <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-6">
                        <div className="flex flex-col gap-2">
                            <Link
                                href="/users"
                                className="inline-flex items-center gap-2 text-sm font-medium text-neutral-600 underline underline-offset-4 hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white"
                            >
                                <ArrowLeft className="size-4" />
                                Back to Users
                            </Link>
                            <div>
                                <h1 className="text-2xl font-semibold text-neutral-900 dark:text-white">Export Users</h1>
                                <p className="text-sm text-neutral-600 dark:text-neutral-300">
                                    Track queued exports and download files when ready.
                                </p>
                            </div>
                        </div>

                        <div className="rounded-box border border-black/10 bg-white/40 shadow-sm backdrop-blur dark:border-white/10 dark:bg-neutral-950/40">
                            <div className="border-b border-black/5 px-6 py-4 dark:border-white/10">
                                <div className="flex flex-wrap items-center justify-between gap-3 md:flex-nowrap">
                                    <h2 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                        Export History
                                    </h2>

                                    <form className="flex items-center gap-2">
                                        <label
                                            htmlFor="requested_date"
                                            className="text-sm font-medium whitespace-nowrap text-neutral-700 dark:text-neutral-200"
                                        >
                                            Requested Date
                                        </label>
                                        <input
                                            id="requested_date"
                                            name="requested_date"
                                            type="date"
                                            value={filterDate}
                                            onChange={(event) => {
                                                const value = event.target.value;
                                                setFilterDate(value);
                                                router.get(
                                                    '/users/export',
                                                    { requested_date: value },
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
                                                href="/users/export"
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
                                            <th className="px-3 py-2 whitespace-nowrap">Requested Time</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Requested By</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Role Filter</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Format</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Users Exported</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Start At</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Finish At</th>
                                            <th className="px-3 py-2 whitespace-nowrap">Status</th>
                                            <th className="px-3 py-2">Download</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 text-sm dark:divide-white/10">
                                        {rows.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={9}
                                                    className="px-3 py-6 text-center text-neutral-700 dark:text-neutral-200"
                                                >
                                                    No export history yet.
                                                </td>
                                            </tr>
                                        ) : (
                                            rows.map((exportRow) => (
                                                <tr key={exportRow.id}>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {formatDateTime(exportRow.requested_at)}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {exportRow.user_email ?? '-'}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {formatRoleFilter(exportRow.role_filter)}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {exportRow.format.toUpperCase()}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {exportRow.users_exported}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {formatDateTime(exportRow.started_at)}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                        {formatDateTime(exportRow.finished_at)}
                                                    </td>
                                                    <td className="px-3 py-3 whitespace-nowrap">
                                                        <span
                                                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${statusClass(exportRow.status)}`}
                                                        >
                                                            {exportRow.status.charAt(0).toUpperCase() +
                                                                exportRow.status.slice(1)}
                                                        </span>
                                                    </td>
                                                    <td className="px-3 py-3">
                                                        {exportRow.download_url ? (
                                                            <a
                                                                href={exportRow.download_url}
                                                                className="inline-flex items-center gap-2 text-sm font-medium text-emerald-600 underline underline-offset-4 hover:text-emerald-700 dark:text-emerald-300 dark:hover:text-emerald-200"
                                                                target="_blank"
                                                                rel="noreferrer"
                                                            >
                                                                <Download className="size-4" />
                                                                Download
                                                            </a>
                                                        ) : exportRow.status === 'finished' &&
                                                          exportRow.delivery_method === 'email' ? (
                                                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                Emailed
                                                            </span>
                                                        ) : (
                                                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                {exportRow.error_message ?? 'Pending'}
                                                            </span>
                                                        )}
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
                                        Showing {exports.from ?? 0} to {exports.to ?? 0} of {exports.total}
                                    </p>
                                    <div className="flex flex-wrap gap-2 md:justify-end">
                                        {exports.links.map((link, index) => (
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
