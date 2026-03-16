import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type Notify = {
    type: 'success' | 'error';
    message: string;
};

export default function NotifyToast() {
    const { notify } = usePage<{ notify?: Notify | null }>().props;
    const [localNotify, setLocalNotify] = useState<Notify | null>(null);

    useEffect(() => {
        const handler = (event: Event) => {
            const customEvent = event as CustomEvent<Notify>;
            if (!customEvent.detail?.message) {
                return;
            }

            setLocalNotify(customEvent.detail);
            window.setTimeout(() => setLocalNotify(null), 3500);
        };

        window.addEventListener('notify', handler);

        return () => window.removeEventListener('notify', handler);
    }, []);

    const activeNotify = notify?.message ? notify : localNotify;

    if (!activeNotify?.message) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed top-4 right-4 z-50">
            <div
                key={`${activeNotify.type}-${activeNotify.message}`}
                className={`notify-toast pointer-events-auto inline-flex w-auto max-w-[calc(100vw-2rem)] rounded-md border bg-background px-3 py-2 text-xs shadow-md ${
                    activeNotify.type === 'success' ? 'border-emerald-200' : 'border-red-200'
                }`}
            >
                <p className="text-foreground whitespace-pre-wrap break-words">
                    {activeNotify.message}
                </p>
            </div>
        </div>
    );
}
