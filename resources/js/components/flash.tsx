import { usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { type SharedData } from '@/types';

export function Flash() {
    const { flash } = usePage<SharedData>().props;

    if (!flash?.success) return null;

    return (
        <div
            role="status"
            className="flex items-center gap-2 rounded-lg border border-emerald-600/20 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-400"
        >
            <CheckCircle2 className="size-4 shrink-0" />
            {flash.success}
        </div>
    );
}
