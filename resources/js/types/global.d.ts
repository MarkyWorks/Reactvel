import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            notify?: {
                type: 'success' | 'error';
                message: string;
            } | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

