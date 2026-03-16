import { Head, Link } from '@inertiajs/react';
import {
    ArrowUpRight,
    ShieldCheck,
    TrendingUp,
    UserCheck,
    UserPlus,
    Users,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import { index as usersIndex } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

type ActivityItem = {
    id: string;
    user: string;
    action: string;
    description: string | null;
    created_at: string | null;
};

type RecentUser = {
    id: string;
    name: string;
    email: string;
    role: string | null;
    created_at: string | null;
};

type DashboardProps = {
    kpis: {
        total_users: number;
        active_today: number;
        new_users: number;
        admins: number;
    };
    recentActivity: ActivityItem[];
    recentUsers: RecentUser[];
    roleDistribution: {
        role: string;
        count: number;
    }[];
    securitySnapshot: {
        logins_last_24h: number;
        logouts_last_24h: number;
    };
};

const formatNumber = (value: number) => new Intl.NumberFormat('en-US').format(value);

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

export default function Dashboard({
    kpis,
    recentActivity,
    recentUsers,
    roleDistribution,
    securitySnapshot,
}: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <section className="rounded-3xl border border-black/10 bg-[radial-gradient(circle_at_top,_rgba(15,23,42,0.08),_transparent_55%)] p-6 shadow-sm dark:border-white/10 dark:bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.18),_transparent_55%)]">
                    <div className="flex flex-wrap items-center justify-between gap-6">
                        <div>
                            <p className="text-xs uppercase tracking-[0.3em] text-neutral-500 dark:text-neutral-400">
                                User Intelligence
                            </p>
                            <h1 className="mt-3 text-3xl font-semibold text-neutral-900 dark:text-white">
                                User Management Command Center
                            </h1>
                            <p className="mt-2 max-w-2xl text-sm text-neutral-600 dark:text-neutral-300">
                                Operational oversight for access, activity, and governance signals across your organization.
                            </p>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div className="rounded-2xl border border-black/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-neutral-950">
                        <div className="flex items-center justify-between">
                            <span className="text-xs uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                                Total Users
                            </span>
                            <span className="rounded-full bg-emerald-500/10 p-2 text-emerald-600 dark:text-emerald-300">
                                <Users className="size-4" />
                            </span>
                        </div>
                        <div className="mt-4 text-2xl font-semibold text-neutral-900 dark:text-white">
                            {formatNumber(kpis.total_users)}
                        </div>
                        <div className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                            Active base across all roles
                        </div>
                    </div>
                    <div className="rounded-2xl border border-black/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-neutral-950">
                        <div className="flex items-center justify-between">
                            <span className="text-xs uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                                Active Today
                            </span>
                            <span className="rounded-full bg-sky-500/10 p-2 text-sky-600 dark:text-sky-300">
                                <UserCheck className="size-4" />
                            </span>
                        </div>
                        <div className="mt-4 text-2xl font-semibold text-neutral-900 dark:text-white">
                            {formatNumber(kpis.active_today)}
                        </div>
                        <div className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                            Seen in the last 24 hours
                        </div>
                    </div>
                    <div className="rounded-2xl border border-black/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-neutral-950">
                        <div className="flex items-center justify-between">
                            <span className="text-xs uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                                New Users
                            </span>
                            <span className="rounded-full bg-amber-500/10 p-2 text-amber-600 dark:text-amber-300">
                                <UserPlus className="size-4" />
                            </span>
                        </div>
                        <div className="mt-4 text-2xl font-semibold text-neutral-900 dark:text-white">
                            {formatNumber(kpis.new_users)}
                        </div>
                        <div className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                            Joined in the last 7 days
                        </div>
                    </div>
                    <div className="rounded-2xl border border-black/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-neutral-950">
                        <div className="flex items-center justify-between">
                            <span className="text-xs uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                                Admins
                            </span>
                            <span className="rounded-full bg-neutral-900/10 p-2 text-neutral-700 dark:bg-white/10 dark:text-neutral-200">
                                <ShieldCheck className="size-4" />
                            </span>
                        </div>
                        <div className="mt-4 text-2xl font-semibold text-neutral-900 dark:text-white">
                            {formatNumber(kpis.admins)}
                        </div>
                        <div className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                            Privileged accounts
                        </div>
                    </div>
                </section>

                <section className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                    <div className="flex flex-col gap-6">
                        <div className="rounded-3xl border border-black/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-neutral-950">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs uppercase tracking-[0.3em] text-neutral-500 dark:text-neutral-400">
                                        Activity Feed
                                    </p>
                                    <h2 className="mt-2 text-xl font-semibold text-neutral-900 dark:text-white">
                                        Recent Audit Events
                                    </h2>
                                </div>
                                <Link
                                    href={auditLogsIndex()}
                                    className="inline-flex items-center gap-2 rounded-full border border-black/10 px-3 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-black/5 dark:border-white/10 dark:text-neutral-200 dark:hover:bg-white/10"
                                >
                                    View Audit Logs
                                    <ArrowUpRight className="size-3" />
                                </Link>
                            </div>
                            <div className="mt-6 space-y-4">
                                {recentActivity.length === 0 ? (
                                    <div className="rounded-2xl border border-dashed border-black/10 px-4 py-6 text-center text-sm text-neutral-500 dark:border-white/10 dark:text-neutral-400">
                                        No recent audit activity yet.
                                    </div>
                                ) : (
                                    recentActivity.map((activity) => (
                                        <div
                                            key={activity.id}
                                            className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-black/5 px-4 py-3 dark:border-white/10"
                                        >
                                            <div>
                                                <div className="font-semibold text-neutral-900 dark:text-white">
                                                    {activity.user}
                                                </div>
                                                <div className="text-sm text-neutral-600 dark:text-neutral-300">
                                                    {activity.action}
                                                    {activity.description ? ` � ${activity.description}` : ''}
                                                </div>
                                            </div>
                                            <div className="text-right text-xs text-neutral-500 dark:text-neutral-400">
                                                {formatDateTime(activity.created_at)}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>

                        <div className="rounded-3xl border border-black/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-neutral-950">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs uppercase tracking-[0.3em] text-neutral-500 dark:text-neutral-400">
                                        Recent Users
                                    </p>
                                    <h2 className="mt-2 text-xl font-semibold text-neutral-900 dark:text-white">
                                        Latest Onboarded Accounts
                                    </h2>
                                </div>
                                <Link
                                    href={usersIndex()}
                                    className="inline-flex items-center gap-2 rounded-full border border-black/10 px-3 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-black/5 dark:border-white/10 dark:text-neutral-200 dark:hover:bg-white/10"
                                >
                                    View Users
                                    <ArrowUpRight className="size-3" />
                                </Link>
                            </div>
                            <div className="mt-6 overflow-hidden rounded-2xl border border-black/5 dark:border-white/10">
                                <div className="grid grid-cols-4 gap-4 bg-neutral-50 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-neutral-500 dark:bg-neutral-900/70 dark:text-neutral-400">
                                    <span>User</span>
                                    <span>Role</span>
                                    <span>Joined</span>
                                    <span>Email</span>
                                </div>
                                {recentUsers.length === 0 ? (
                                    <div className="px-4 py-6 text-center text-sm text-neutral-500 dark:text-neutral-400">
                                        No users have been added yet.
                                    </div>
                                ) : (
                                    recentUsers.map((user) => (
                                        <div
                                            key={user.id}
                                            className="grid grid-cols-4 items-center gap-4 border-t border-black/5 px-4 py-3 text-sm text-neutral-700 dark:border-white/10 dark:text-neutral-200"
                                        >
                                            <span className="font-semibold text-neutral-900 dark:text-white">
                                                {user.name}
                                            </span>
                                            <span>{user.role ?? 'User'}</span>
                                            <span>{formatDateTime(user.created_at)}</span>
                                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                {user.email}
                                            </span>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>

                    <aside className="flex flex-col gap-6">
                        <div className="rounded-3xl border border-black/10 bg-neutral-900 p-6 text-white shadow-sm">
                            <div className="flex items-center gap-3 text-xs uppercase tracking-widest text-neutral-300">
                                <TrendingUp className="size-4" />
                                Access Momentum
                            </div>
                            <div className="mt-4 text-2xl font-semibold">{formatNumber(securitySnapshot.logins_last_24h)}</div>
                            <p className="mt-2 text-sm text-neutral-300">
                                Logins recorded in the last 24 hours.
                            </p>
                            <div className="mt-6 flex items-center justify-between text-xs text-neutral-400">
                                <span>{formatNumber(securitySnapshot.logouts_last_24h)} logouts</span>
                                <span>Audit coverage active</span>
                            </div>
                        </div>

                        <div className="rounded-3xl border border-black/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-neutral-950">
                            <div className="flex items-center gap-3 text-xs uppercase tracking-[0.3em] text-neutral-500 dark:text-neutral-400">
                                <Users className="size-4" />
                                Role Distribution
                            </div>
                            <div className="mt-6 space-y-3">
                                {roleDistribution.map((role) => (
                                    <div key={role.role} className="flex items-center justify-between">
                                        <span className="text-sm text-neutral-700 dark:text-neutral-200">
                                            {role.role}
                                        </span>
                                        <span className="rounded-full bg-black/5 px-3 py-1 text-xs font-semibold text-neutral-700 dark:bg-white/10 dark:text-neutral-200">
                                            {formatNumber(role.count)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-3xl border border-black/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-neutral-950">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs uppercase tracking-[0.3em] text-neutral-500 dark:text-neutral-400">
                                        Governance Actions
                                    </p>
                                    <h3 className="mt-2 text-lg font-semibold text-neutral-900 dark:text-white">
                                        Key Shortcuts
                                    </h3>
                                </div>
                                <span className="rounded-full bg-sky-500/10 px-3 py-1 text-xs font-semibold text-sky-700 dark:text-sky-200">
                                    Live
                                </span>
                            </div>
                            <div className="mt-6 grid gap-3">
                                <Link
                                    href={usersIndex()}
                                    className="flex items-center justify-between rounded-2xl border border-black/5 px-4 py-3 text-sm font-semibold text-neutral-700 transition hover:bg-black/5 dark:border-white/10 dark:text-neutral-200 dark:hover:bg-white/10"
                                >
                                    Manage Users
                                    <ArrowUpRight className="size-4" />
                                </Link>
                                <Link
                                    href={auditLogsIndex()}
                                    className="flex items-center justify-between rounded-2xl border border-black/5 px-4 py-3 text-sm font-semibold text-neutral-700 transition hover:bg-black/5 dark:border-white/10 dark:text-neutral-200 dark:hover:bg-white/10"
                                >
                                    Review Audit Logs
                                    <ArrowUpRight className="size-4" />
                                </Link>
                            </div>
                        </div>
                    </aside>
                </section>
            </div>
        </AppLayout>
    );
}
