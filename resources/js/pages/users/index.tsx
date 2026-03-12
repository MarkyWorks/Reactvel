import { Head, Link, router, usePage } from '@inertiajs/react';
import { EllipsisVertical } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role?: string | null;
    created_at: string | null;
};

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type UsersPageProps = {
    users: {
        data: UserRow[];
        from: number | null;
        to: number | null;
        total: number;
        links: PaginatorLink[];
    };
    filters: {
        search?: string;
        role?: string;
    };
    roleOptions?: string[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

const formatLabel = (value?: string | null) => {
    if (!value) {
        return '-';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
};

const formatDate = (value: string | null) => {
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
    }).format(parsedDate);
};

export default function UsersIndex({
    users,
    filters,
    roleOptions = [],
}: UsersPageProps) {
    const { auth } = usePage().props;
    const canManageUsers = ['Super Admin', 'Admin'].includes(auth.user?.role ?? '');
    const [search, setSearch] = useState(filters.search ?? '');
    const [selectedRole, setSelectedRole] = useState(filters.role ?? '');
    const [deletingUser, setDeletingUser] = useState<UserRow | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            '/users',
            {
                search,
                role: selectedRole,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const confirmDelete = () => {
        if (!deletingUser) {
            return;
        }

        setIsDeleting(true);

        router.delete(`/users/${deletingUser.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setIsDeleting(false);
                setDeletingUser(null);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User Management" />

            <div className="py-6">
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-6">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h1 className="text-2xl font-semibold text-neutral-900 dark:text-white">Users</h1>
                                <p className="text-sm text-neutral-600 dark:text-neutral-300">
                                    {users.total} {users.total === 1 ? 'user' : 'users'} found
                                </p>
                            </div>
                            {canManageUsers && (
                                <Link
                                    href="/users/create"
                                    className="inline-flex items-center gap-2 py-2 text-sm font-medium text-neutral-900 underline underline-offset-4 dark:text-white"
                                >
                                    Add New User
                                </Link>
                            )}
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
                                            htmlFor="role"
                                            className="text-sm font-medium text-neutral-700 dark:text-neutral-200"
                                        >
                                            Role
                                        </label>
                                        <select
                                            id="role"
                                            name="role"
                                            value={selectedRole}
                                            onChange={(event) => setSelectedRole(event.target.value)}
                                            className="mt-2 inline-block w-full rounded-box border border-black/10 bg-white p-2 text-sm text-neutral-800 shadow-none transition-colors duration-200 focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-300 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                                        >
                                            <option value="">All Roles</option>
                                            {roleOptions.map((roleOption) => (
                                                <option key={roleOption} value={roleOption}>
                                                    {formatLabel(roleOption)}
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

                                        {(search !== '' || selectedRole !== '') && (
                                            <Link
                                                href="/users"
                                                className="inline-flex items-center rounded-box bg-black/5 px-3 py-2 text-sm font-medium text-neutral-900 transition-colors hover:bg-black/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
                                            >
                                                Clear
                                            </Link>
                                        )}
                                    </div>
                                </form>
                            </div>

                            <div className="p-6 text-gray-900 dark:text-gray-100">
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y-2 divide-gray-200 text-left dark:divide-white/10">
                                        <thead className="ltr:text-left rtl:text-right">
                                            <tr className="text-left text-sm font-medium">
                                                <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                    Name
                                                </th>
                                                <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                    Email
                                                </th>
                                                <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                    Role
                                                </th>
                                                <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                    Created
                                                </th>
                                                {canManageUsers && (
                                                    <th className="whitespace-nowrap px-3 py-2 text-neutral-700 dark:text-neutral-200">
                                                        Actions
                                                    </th>
                                                )}
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-gray-200 dark:divide-white/10">
                                            {users.data.length === 0 ? (
                                                <tr>
                                                    <td
                                                        colSpan={canManageUsers ? 7 : 6}
                                                        className="px-3 py-4 text-center text-neutral-900 dark:text-neutral-100"
                                                    >
                                                        No users found.
                                                    </td>
                                                </tr>
                                            ) : (
                                                users.data.map((user) => {
                                                    const canEditUser =
                                                        canManageUsers &&
                                                        (auth.user?.role === 'Super Admin' ||
                                                            user.role !== 'Super Admin');
                                                    const canDeleteUser = canEditUser;

                                                    return (
                                                    <tr key={user.id}>
                                                        <td className="whitespace-nowrap px-3 py-2 text-neutral-900 dark:text-neutral-100">
                                                            {user.name}
                                                        </td>
                                                        <td className="whitespace-nowrap px-3 py-2 text-neutral-900 dark:text-neutral-100">
                                                            {user.email}
                                                        </td>
                                                        <td className="whitespace-nowrap px-3 py-2 text-neutral-900 dark:text-neutral-100">
                                                            {formatLabel(user.role)}
                                                        </td>
                                                        <td className="whitespace-nowrap px-3 py-2 text-neutral-900 dark:text-neutral-100">
                                                            {formatDate(user.created_at)}
                                                        </td>
                                                        {canManageUsers && (
                                                            <td className="whitespace-nowrap px-3 py-2">
                                                                <DropdownMenu>
                                                                    <DropdownMenuTrigger asChild>
                                                                        <Button
                                                                            type="button"
                                                                            variant="outline"
                                                                            size="sm"
                                                                            className="h-8 w-8 p-0"
                                                                        >
                                                                            <EllipsisVertical className="size-4" />
                                                                        </Button>
                                                                    </DropdownMenuTrigger>
                                                                    <DropdownMenuContent side="bottom" align="end" className="w-40">
                                                                        {canEditUser && (
                                                                            <DropdownMenuItem asChild>
                                                                                <Link href={`/users/${user.id}/edit`}>Edit</Link>
                                                                            </DropdownMenuItem>
                                                                        )}
                                                                        {canDeleteUser && (
                                                                            <>
                                                                                <DropdownMenuSeparator />
                                                                                <DropdownMenuItem
                                                                                    variant="destructive"
                                                                                    onSelect={(event) => {
                                                                                        event.preventDefault();
                                                                                        setDeletingUser(user);
                                                                                    }}
                                                                                >
                                                                                    Delete
                                                                                </DropdownMenuItem>
                                                                            </>
                                                                        )}
                                                                    </DropdownMenuContent>
                                                                </DropdownMenu>
                                                            </td>
                                                        )}
                                                    </tr>
                                                    );
                                                })
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
                    </div>
                </div>
            </div>

            <Dialog open={Boolean(deletingUser)} onOpenChange={(open) => !open && setDeletingUser(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete User</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete {deletingUser?.name ?? 'this user'}?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button variant="destructive" onClick={confirmDelete} disabled={isDeleting}>
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
