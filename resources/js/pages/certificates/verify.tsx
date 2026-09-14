import { Badge } from '@/components/ui/badge';
import PublicLayout from '@/layouts/public-layout';
import { Head } from '@inertiajs/react';
import { BadgeCheck, CircleSlash } from 'lucide-react';

type Certificate = {
    serial: string;
    issued_at: string;
    holder: string;
    course: string;
};

export default function Verify({
    serial,
    certificate,
}: {
    serial: string;
    certificate: Certificate | null;
}) {
    return (
        <PublicLayout breadcrumbs={[{ title: 'Verify certificate', href: `/verify/${serial}` }]}>
            <Head title={`Verify ${serial}`} />

            <div className="mx-auto max-w-xl">
                <h1 className="mb-4 text-xl font-semibold">Certificate verification</h1>

                {certificate ? (
                    <div className="rounded-xl border p-6">
                        <div className="mb-4 flex items-center gap-2 text-emerald-700 dark:text-emerald-400">
                            <BadgeCheck className="size-5" />
                            <span className="font-medium">This certificate is genuine.</span>
                        </div>

                        <dl className="grid gap-3 text-sm">
                            <div className="flex justify-between gap-4 border-t pt-3">
                                <dt className="text-muted-foreground">Awarded to</dt>
                                <dd className="font-medium">{certificate.holder}</dd>
                            </div>
                            <div className="flex justify-between gap-4 border-t pt-3">
                                <dt className="text-muted-foreground">Course</dt>
                                <dd className="font-medium">{certificate.course}</dd>
                            </div>
                            <div className="flex justify-between gap-4 border-t pt-3">
                                <dt className="text-muted-foreground">Issued</dt>
                                <dd>{new Date(certificate.issued_at).toLocaleDateString()}</dd>
                            </div>
                            <div className="flex justify-between gap-4 border-t pt-3">
                                <dt className="text-muted-foreground">Serial</dt>
                                <dd className="font-mono text-xs">{certificate.serial}</dd>
                            </div>
                        </dl>
                    </div>
                ) : (
                    <div className="rounded-xl border border-dashed p-6">
                        <div className="mb-2 flex items-center gap-2">
                            <CircleSlash className="text-muted-foreground size-5" />
                            <span className="font-medium">No certificate with that serial.</span>
                        </div>
                        <p className="text-muted-foreground text-sm">
                            Checked <Badge variant="outline" className="font-mono">{serial}</Badge>
                        </p>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
