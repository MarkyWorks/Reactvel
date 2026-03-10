import { usePage } from '@inertiajs/react';
import { useRef } from 'react';

type Notify = {
    type: 'success' | 'error';
    message: string;
};

export default function NotifyToast() {
    const { notify } = usePage<{ notify?: Notify | null }>().props;
    const lastNotifyRef = useRef<Notify | null>(null);
    const toastKeyRef = useRef(0);

    if (!notify?.message) {
        return null;
    }

    if (notify !== lastNotifyRef.current) {
        lastNotifyRef.current = notify;
        toastKeyRef.current += 1;
    }

    return (
        <div className="pointer-events-none fixed top-4 right-4 z-50">
            <div
                key={toastKeyRef.current}
                className={`notify-toast pointer-events-auto inline-flex w-auto max-w-[calc(100vw-2rem)] rounded-md border bg-background px-3 py-2 text-xs shadow-md ${
                    notify.type === 'success' ? 'border-emerald-200' : 'border-red-200'
                }`}
            >
                <p className="text-foreground whitespace-pre-wrap break-words">
                    {notify.message}
                </p>
            </div>
        </div>
    );
}
