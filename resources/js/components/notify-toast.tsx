import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type Notify = {
    type: 'success' | 'error';
    message: string;
};

export default function NotifyToast() {
    const { notify } = usePage<{ notify?: Notify | null }>().props;
    const [activeNotify, setActiveNotify] = useState<Notify | null>(null);
    const [toastKey, setToastKey] = useState(0);

    useEffect(() => {
        if (!notify?.message) {
            return;
        }

        // Force a fresh DOM node so the CSS animation always restarts.
        setActiveNotify(notify);
        setToastKey((key) => key + 1);
    }, [notify]);

    useEffect(() => {
        if (!activeNotify) {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            setActiveNotify(null);
        }, 3300);

        return () => window.clearTimeout(timeoutId);
    }, [activeNotify, toastKey]);

    if (!activeNotify?.message) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed top-4 right-4 z-50">
            <div
                key={toastKey}
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
