import { Head, Link, router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { Activity, ClipboardList } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audit Logs',
        href: '/audit-logs',
    },
];

type UserActivityRow = {
    id: string;
    name: string;
    email: string;
    role?: string | null;
    status: 'online' | 'active' | 'inactive' | 'offline';
    last_active_at: string | null;
    last_active_label: string | null;
};

type AuditLogRow = {
    id: number;
    user: string;
    action: string;
    description: string | null;
    ip_address: string | null;
    created_at: string | null;
};

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type AuditLogsPageProps = {
    users: {
        data: UserActivityRow[];
        from: number | null;
        to: number | null;
        total: number;
        links: PaginatorLink[];
    };
    logs: {
        data: AuditLogRow[];
        from: number | null;
        to: number | null;
        total: number;
        links: PaginatorLink[];
    };
    filters: {
        search?: string;
        status?: string;
    };
    statusOptions: string[];
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

const formatRelativeTime = (value: string | null, nowMs: number) => {
    if (!value) {
        return null;
    }

    const isoValue = value.includes('T') ? value : value.replace(' ', 'T');
    const parsedDate = new Date(isoValue);

    if (Number.isNaN(parsedDate.getTime())) {
        return value;
    }

    const diffSeconds = Math.max(0, Math.floor((nowMs - parsedDate.getTime()) / 1000));

    if (diffSeconds < 60) {
        return `${diffSeconds} second${diffSeconds === 1 ? '' : 's'} ago`;
    }

    const diffMinutes = Math.floor(diffSeconds / 60);

    if (diffMinutes < 60) {
        return `${diffMinutes} minute${diffMinutes === 1 ? '' : 's'} ago`;
    }

    const diffHours = Math.floor(diffMinutes / 60);

    if (diffHours < 24) {
        return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
    }

    const diffDays = Math.floor(diffHours / 24);

    return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`;
};

const useNow = (intervalMs: number) => {
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        const interval = window.setInterval(() => {
            setNow(Date.now());
        }, intervalMs);

        return () => window.clearInterval(interval);
    }, [intervalMs]);

    return now;
};

const statusStyles: Record<UserActivityRow['status'], string> = {
    online: 'bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200',
    active: 'bg-amber-500/10 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200',
    inactive: 'bg-rose-500/10 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200',
    offline: 'bg-neutral-500/10 text-neutral-700 dark:bg-neutral-500/20 dark:text-neutral-200',
};

const statusDots: Record<UserActivityRow['status'], string> = {
    online: 'bg-emerald-500',
    active: 'bg-amber-500',
    inactive: 'bg-rose-500',
    offline: 'bg-neutral-400',
};

const statusLabels: Record<UserActivityRow['status'], string> = {
    online: 'Online',
    active: 'Active',
    inactive: 'Inactive',
    offline: 'Offline',
};

export default function AuditLogsIndex({
    users,
    logs,
    filters,
    statusOptions,
}: AuditLogsPageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const nowMs = useNow(1000);

    useEcho(
        'audit-logs',
        ['.AuditLogCreated', '.UserActivityUpdated'],
        () => {
            router.reload({
                only: ['users', 'logs'],
                preserveScroll: true,
            });
        },
        [],
    );

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            '/audit-logs',
            {
                search,
                status,
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
            <Head title="Audit Logs" />

            <div className="py-6">
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-6">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h1 className="text-2xl font-semibold text-neutral-900 dark:text-white">Audit Logs</h1>
                                <p className="text-sm text-neutral-600 dark:text-neutral-300">
                                    Monitor user activity and system actions
                                </p>
                            </div>
                        </div>

                        <div className="rounded-box border border-black/10 bg-white/80 shadow-sm backdrop-blur dark:border-white/10 dark:bg-neutral-950/70">
                            <div className="border-b border-black/5 px-6 py-4 dark:border-white/10">
                                <form method="GET" className="flex flex-wrap items-end gap-3" onSubmit={submitSearch}>
                                    <div className="min-w-[220px] flex-1">
                                        <label
                                            htmlFor="search"
                                            className="text-sm font-medium text-neutral-700 dark:text-neutral-200"
                                        >
                                            Search
                                        </label>
                                        <input
                                            id="search"
                                            name="search"
                                            type="search"
                                            value={search}
                                            onChange={(event) => setSearch(event.target.value)}
                                            placeholder="Search by id , name or email"
                                            className="mt-2 w-full rounded-box border border-black/10 bg-white p-2 text-sm text-neutral-800 shadow-none transition-colors duration-200 focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-300 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                                        />
                                    </div>

                                    <div className="min-w-[180px]">
                                        <label
                                            htmlFor="status"
                                            className="text-sm font-medium text-neutral-700 dark:text-neutral-200"
                                        >
                                            Status
                                        </label>
                                        <select
                                            id="status"
                                            name="status"
                                            value={status}
                                            onChange={(event) => setStatus(event.target.value)}
                                            className="mt-2 inline-block w-full rounded-box border border-black/10 bg-white p-2 text-sm text-neutral-800 shadow-none transition-colors duration-200 focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-300 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                                        >
                                            <option value="">All Statuses</option>
                                            {statusOptions.map((option) => (
                                                <option key={option} value={option}>
                                                    {option.charAt(0).toUpperCase() + option.slice(1)}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2 pb-0.5">
                                        <button
                                            type="submit"
                                            className="inline-flex items-center rounded-box border border-black/10 px-3 py-2 text-sm font-medium text-neutral-900 transition-colors hover:border-black/20 hover:bg-black/5 dark:border-white/15 dark:text-white dark:hover:border-white/25 dark:hover:bg-white/10"
                                        >
                                            Search
                                        </button>

                                        {(search !== '' || status !== '') && (
                                            <Link
                                                href="/audit-logs"
                                                className="inline-flex items-center rounded-box bg-black/5 px-3 py-2 text-sm font-medium text-neutral-900 transition-colors hover:bg-black/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
                                            >
                                                Clear
                                            </Link>
                                        )}
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div className="flex flex-col gap-6">
                            <div className="rounded-box border border-black/10 bg-white/80 shadow-sm backdrop-blur dark:border-white/10 dark:bg-neutral-950/70">
                                <div className="flex items-center justify-between border-b border-black/5 px-6 py-4 dark:border-white/10">
                                    <div className="flex items-center gap-3">
                                        <div className="inline-flex size-9 items-center justify-center rounded-field bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-200">
                                            <Activity className="size-4" />
                                        </div>
                                        <div>
                                            <h2 className="text-lg font-semibold text-neutral-900 dark:text-white">User Activity</h2>
                                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                                {users.total} users tracked
                                            </p>
                                        </div>
                                    </div>
                                    <div className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                        Live updates in real time
                                    </div>
                                </div>

                                <div className="p-6">
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y-2 divide-gray-200 text-left dark:divide-white/10">
                                            <thead className="ltr:text-left rtl:text-right">
                                                <tr className="text-left text-sm font-medium">
                                                    <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                        User
                                                    </th>
                                                    <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                        Status
                                                    </th>
                                                    <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                        Last Activity
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-200 dark:divide-white/10">
                                                {users.data.length === 0 ? (
                                                    <tr>
                                                        <td
                                                            colSpan={3}
                                                            className="px-3 py-4 text-center text-neutral-900 dark:text-neutral-100"
                                                        >
                                                            No users found.
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    users.data.map((user) => (
                                                        <tr key={user.id}>
                                                            <td className="whitespace-nowrap px-3 py-2 text-neutral-900 dark:text-neutral-100">
                                                                <div className="font-medium">{user.name}</div>
                                                                <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                    {user.email}
                                                                </div>
                                                            </td>
                                                            <td className="whitespace-nowrap px-3 py-2">
                                                                <span
                                                                    className={[
                                                                        'inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-semibold',
                                                                        statusStyles[user.status],
                                                                    ].join(' ')}
                                                                >
                                                                    <span className={['size-2 rounded-full', statusDots[user.status]].join(' ')} />
                                                                    {statusLabels[user.status]}
                                                                </span>
                                                            </td>
                                                            <td className="whitespace-nowrap px-3 py-2 text-neutral-900 dark:text-neutral-100">
                                                                <div className="font-medium">
                                                                    {user.status === 'offline'
                                                                        ? (() => {
                                                                              const relativeTime = formatRelativeTime(
                                                                                  user.last_active_at,
                                                                                  nowMs,
                                                                              );

                                                                              return relativeTime
                                                                                  ? `${statusLabels.offline} ${relativeTime}`
                                                                                  : '-';
                                                                          })()
                                                                        : '-'}
                                                                </div>
                                                                <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                    {formatDateTime(user.last_active_at)}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>

                                    <div className="mt-6 flex flex-col gap-3 border-t pt-4 md:flex-row md:items-center md:justify-between">
                                        <p className="text-sm text-neutral-600 dark:text-neutral-300">
                                            Showing {users.from ?? 0} to {users.to ?? 0} of {users.total}
                                        </p>
                                        <div className="flex flex-wrap gap-2 md:justify-end">
                                            {users.links.map((link, index) => (
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

                            <div className="rounded-box border border-black/10 bg-white/80 shadow-sm backdrop-blur dark:border-white/10 dark:bg-neutral-950/70">
                                <div className="flex items-center justify-between border-b border-black/5 px-6 py-4 dark:border-white/10">
                                    <div className="flex items-center gap-3">
                                        <div className="inline-flex size-9 items-center justify-center rounded-field bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-200">
                                            <ClipboardList className="size-4" />
                                        </div>
                                        <div>
                                            <h2 className="text-lg font-semibold text-neutral-900 dark:text-white">Audit Trail</h2>
                                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                                {logs.total} events captured
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="p-6">
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y-2 divide-gray-200 text-left dark:divide-white/10">
                                            <thead className="ltr:text-left rtl:text-right">
                                                <tr className="text-left text-sm font-medium">
                                                    <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                        User
                                                    </th>
                                                    <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                        Action
                                                    </th>
                                                    <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                        Details
                                                    </th>
                                                    <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                        IP Address
                                                    </th>
                                                    <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                        Date
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-200 dark:divide-white/10">
                                                {logs.data.length === 0 ? (
                                                    <tr>
                                                        <td
                                                            colSpan={5}
                                                            className="px-3 py-4 text-center text-neutral-900 dark:text-neutral-100"
                                                        >
                                                            No audit entries yet.
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    logs.data.map((log) => (
                                                        <tr key={log.id}>
                                                            <td className="whitespace-nowrap px-3 py-2 text-neutral-900 dark:text-neutral-100">
                                                                {log.user}
                                                            </td>
                                                            <td className="whitespace-nowrap px-3 py-2 text-neutral-900 dark:text-neutral-100">
                                                                {log.action}
                                                            </td>
                                                            <td className="min-w-[180px] px-3 py-2 text-sm text-neutral-700 dark:text-neutral-200">
                                                                {log.description ?? '-'}
                                                            </td>
                                                            <td className="whitespace-nowrap px-3 py-2 text-neutral-900 dark:text-neutral-100">
                                                                {log.ip_address ?? '-'}
                                                            </td>
                                                            <td className="whitespace-nowrap px-3 py-2 text-neutral-900 dark:text-neutral-100">
                                                                {formatDateTime(log.created_at)}
                                                            </td>
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>

                                    <div className="mt-6 flex flex-col gap-3 border-t pt-4 md:flex-row md:items-center md:justify-between">
                                        <p className="text-sm text-neutral-600 dark:text-neutral-300">
                                            Showing {logs.from ?? 0} to {logs.to ?? 0} of {logs.total}
                                        </p>
                                        <div className="flex flex-wrap gap-2 md:justify-end">
                                            {logs.links.map((link, index) => (
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
            </div>
        </AppLayout>
    );
}
