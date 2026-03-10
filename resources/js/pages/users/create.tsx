import { Head, Link, useForm } from '@inertiajs/react';
import { Lock, Mail, ShieldCheck, User, UserPlus } from 'lucide-react';
import type { FormEvent } from 'react';
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
        title: 'Create',
        href: '/users/create',
    },
];

type CreateUserProps = {
    roleOptions: string[];
};

export default function CreateUser({ roleOptions }: CreateUserProps) {
    const form = useForm({
        name: '',
        email: '',
        role: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post('/users');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create User" />

            <div className="py-8">
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="relative overflow-hidden rounded-box border border-black/10 bg-white/80 shadow-lg backdrop-blur dark:border-white/10 dark:bg-neutral-900/70">
                        <div
                            className="absolute inset-0 bg-gradient-to-br from-emerald-50/60 via-transparent to-sky-50/50 dark:from-emerald-500/10 dark:to-sky-500/10"
                            aria-hidden="true"
                        />

                        <div className="relative p-6 sm:p-8">
                            <div className="flex flex-col gap-2">
                                <div className="flex items-center gap-3">
                                    <div className="inline-flex size-11 items-center justify-center rounded-field bg-emerald-600/10 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">
                                        <UserPlus className="size-5" />
                                    </div>
                                    <div>
                                        <h2 className="text-2xl font-semibold text-neutral-900 dark:text-white">
                                            New user
                                        </h2>
                                    </div>
                                </div>
                            </div>

                            <form className="mt-8" onSubmit={submit}>
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <label
                                            htmlFor="name"
                                            className="text-sm font-medium text-neutral-700 dark:text-neutral-200"
                                        >
                                            Name
                                        </label>
                                        <div className="relative">
                                            <User className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                                            <input
                                                id="name"
                                                name="name"
                                                type="text"
                                                placeholder="Jane Doe"
                                                value={form.data.name}
                                                onChange={(event) => form.setData('name', event.target.value)}
                                                className="w-full rounded-field border border-black/10 bg-white/90 py-2.5 pl-9 pr-3 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 dark:border-white/20 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20"
                                                required
                                                autoFocus
                                            />
                                        </div>
                                        <InputError message={form.errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <label
                                            htmlFor="email"
                                            className="text-sm font-medium text-neutral-700 dark:text-neutral-200"
                                        >
                                            Email
                                        </label>
                                        <div className="relative">
                                            <Mail className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                                            <input
                                                id="email"
                                                name="email"
                                                type="email"
                                                placeholder="jane@company.com"
                                                value={form.data.email}
                                                onChange={(event) => form.setData('email', event.target.value)}
                                                className="w-full rounded-field border border-black/10 bg-white/90 py-2.5 pl-9 pr-3 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 dark:border-white/20 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20"
                                                required
                                                autoComplete="email"
                                            />
                                        </div>
                                        <InputError message={form.errors.email} />
                                    </div>

                                    <div className="grid gap-2 sm:col-span-2">
                                        <label
                                            htmlFor="role"
                                            className="text-sm font-medium text-neutral-700 dark:text-neutral-200"
                                        >
                                            Role
                                        </label>
                                        <select
                                            id="role"
                                            name="role"
                                            value={form.data.role}
                                            onChange={(event) => form.setData('role', event.target.value)}
                                            className="w-full rounded-field border border-black/10 bg-white/90 px-3 py-2.5 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 dark:!border-white/20 dark:!bg-neutral-900 dark:!text-neutral-100 dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20 dark:[color-scheme:dark] [&>option]:bg-white [&>option]:text-neutral-900 dark:[&>option]:!bg-neutral-900 dark:[&>option]:!text-neutral-100"
                                            required
                                        >
                                            <option value="">Select a role</option>
                                            {roleOptions.map((roleOption) => (
                                                <option key={roleOption} value={roleOption}>
                                                    {roleOption}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={form.errors.role} />
                                    </div>

                                    <div className="grid gap-2 sm:col-span-2">
                                        <label
                                            htmlFor="password"
                                            className="text-sm font-medium text-neutral-700 dark:text-neutral-200"
                                        >
                                            Password
                                        </label>
                                        <div className="relative">
                                            <Lock className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                                            <input
                                                id="password"
                                                name="password"
                                                type="password"
                                                value={form.data.password}
                                                onChange={(event) => form.setData('password', event.target.value)}
                                                className="w-full rounded-field border border-black/10 bg-white/90 py-2.5 pl-9 pr-3 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 dark:border-white/20 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20"
                                                required
                                                autoComplete="new-password"
                                            />
                                        </div>
                                        <InputError message={form.errors.password} />
                                    </div>

                                    <div className="grid gap-2 sm:col-span-2">
                                        <label
                                            htmlFor="password_confirmation"
                                            className="text-sm font-medium text-neutral-700 dark:text-neutral-200"
                                        >
                                            Confirm Password
                                        </label>
                                        <div className="relative">
                                            <ShieldCheck className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                                            <input
                                                id="password_confirmation"
                                                name="password_confirmation"
                                                type="password"
                                                value={form.data.password_confirmation}
                                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                                                className="w-full rounded-field border border-black/10 bg-white/90 py-2.5 pl-9 pr-3 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 dark:border-white/20 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20"
                                                required
                                                autoComplete="new-password"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-8 flex flex-wrap items-center gap-3">
                                    <Button type="submit" disabled={form.processing}>
                                        Save user
                                    </Button>
                                    <Link
                                        href="/users"
                                        className="inline-flex items-center rounded-box bg-black/5 px-4 py-2 text-sm font-medium text-neutral-900 transition-colors hover:bg-black/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
                                    >
                                        Cancel
                                    </Link>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
