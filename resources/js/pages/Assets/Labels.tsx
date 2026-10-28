import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Label {
    id: number;
    asset_tag: string;
    model: string | null;
    serial_number: string | null;
    /** Server-rendered SVG markup from bacon/bacon-qr-code. */
    qr_svg: string;
}

export default function AssetLabels({ labels, skipped }: { labels: Label[]; skipped: number }) {
    return (
        <div className="min-h-screen bg-slate-100 print:bg-white">
            <Head title="Label QR aset" />
            <style>{'@page { size: A4; margin: 10mm; }'}</style>

            <div className="border-border flex flex-wrap items-center gap-3 border-b bg-white px-6 py-4 print:hidden">
                <Button variant="outline" asChild>
                    <Link href="/assets">
                        <ArrowLeft /> Kembali ke aset
                    </Link>
                </Button>
                <p className="text-muted-foreground flex-1 text-sm">
                    {labels.length} label siap dicetak di kertas A4 (3 × 8 per lembar).
                    {skipped > 0 && ` ${skipped} aset dilewati karena belum ditandai siap pakai.`}
                </p>
                <Button onClick={() => window.print()} disabled={labels.length === 0}>
                    <Printer /> Cetak
                </Button>
            </div>

            <div className="mx-auto my-6 grid w-[190mm] grid-cols-3 gap-[2mm] bg-white p-[2mm] shadow-sm print:my-0 print:p-0 print:shadow-none">
                {labels.map((label) => (
                    <div
                        key={label.id}
                        className="flex h-[35mm] break-inside-avoid items-center gap-[3mm] overflow-hidden rounded border border-dashed border-slate-300 p-[2.5mm]"
                    >
                        <div
                            className="size-[28mm] shrink-0 [&>svg]:size-full"
                            aria-label={`QR ${label.asset_tag}`}
                            dangerouslySetInnerHTML={{ __html: label.qr_svg }}
                        />
                        <div className="min-w-0 leading-tight">
                            <p className="font-tag text-[13pt] font-bold text-slate-900">{label.asset_tag}</p>
                            <p className="mt-[1mm] line-clamp-2 text-[7pt] text-slate-700">{label.model}</p>
                            {label.serial_number && (
                                <p className="font-tag mt-[1mm] truncate text-[6.5pt] text-slate-500">
                                    S/N {label.serial_number}
                                </p>
                            )}
                            <p className="mt-[1mm] text-[6pt] tracking-wide text-slate-400 uppercase">Aset IT</p>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
