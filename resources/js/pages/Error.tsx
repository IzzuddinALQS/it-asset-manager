import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, RotateCw } from 'lucide-react';
import AppLogo from '@/components/AppLogo';
import AppWordmark from '@/components/AppWordmark';
import { Button } from '@/components/ui/button';
import type { PageProps } from '@/types';

type ErrorStatus = 403 | 404 | 419 | 500 | 503;

const messages: Record<ErrorStatus, { title: string; description: string }> = {
    403: {
        title: 'Akses ditolak',
        description: 'Peran Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator bila perlu akses.',
    },
    404: {
        title: 'Halaman tidak ditemukan',
        description: 'Alamat yang Anda buka tidak ada, atau datanya sudah dihapus.',
    },
    419: {
        title: 'Sesi telah berakhir',
        description: 'Halaman terlalu lama dibiarkan terbuka. Muat ulang lalu ulangi tindakan Anda.',
    },
    500: {
        title: 'Terjadi kesalahan',
        description: 'Ada masalah di server kami. Tim IT sudah tercatat; silakan coba beberapa saat lagi.',
    },
    503: {
        title: 'Sedang dalam pemeliharaan',
        description: 'Aplikasi sedang diperbarui dan akan segera kembali.',
    },
};

/** Branded error page rendered by the exception handler when debug mode is off. */
export default function Error({ status }: { status: ErrorStatus }) {
    const signedIn = usePage<PageProps>().props.auth?.user != null;
    const { title, description } = messages[status] ?? messages[500];
    const canRetry = status === 419 || status === 500 || status === 503;

    return (
        <div className="bg-background flex min-h-screen flex-col items-center justify-center px-6 py-16">
            <Head title={title} />

            <Link href={signedIn ? '/dashboard' : '/login'} className="mb-12 flex items-center gap-2.5">
                <AppLogo className="size-8" />
                <AppWordmark className="text-[22px] text-zinc-950" tagClassName="bg-primary" />
            </Link>

            <div className="max-w-md text-center">
                <p className="text-primary font-tag text-sm font-semibold">{status}</p>
                <h1 className="mt-3 text-3xl font-semibold tracking-tight text-zinc-950">{title}</h1>
                <p className="text-muted-foreground mt-3 text-[15px] leading-relaxed">{description}</p>

                <div className="mt-8 flex flex-col-reverse justify-center gap-2 sm:flex-row">
                    <Button variant="outline" onClick={() => window.history.back()}>
                        <ArrowLeft /> Kembali
                    </Button>
                    {canRetry ? (
                        <Button onClick={() => window.location.reload()}>
                            <RotateCw /> Muat ulang
                        </Button>
                    ) : (
                        <Button asChild>
                            <Link href={signedIn ? '/dashboard' : '/login'}>
                                {signedIn ? 'Ke dashboard' : 'Ke halaman masuk'}
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}
