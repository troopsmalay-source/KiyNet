<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BillingInvoiceRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BillingInvoiceController extends Controller
{
    public function __construct(private readonly BillingInvoiceRepository $repository) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'tipe', 'periode', 'dari', 'sampai']);
        $invoices = $this->repository->filter($filters);
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 25, 50], true) ? $perPage : 10;

        $paginator = new LengthAwarePaginator(
            $invoices->forPage($request->integer('page', 1), $perPage)->values(),
            $invoices->count(),
            $perPage,
            $request->integer('page', 1),
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.billing.index', [
            'invoices' => $paginator->getCollection(),
            'paginator' => $paginator,
            'perPage' => $perPage,
            'filters' => $filters,
            'stats' => $this->repository->stats(),
            'recentPayments' => $this->repository->recentPayments(10),
            'statuses' => BillingInvoiceRepository::LABEL_STATUS,
            'tipeLabels' => BillingInvoiceRepository::TIPE_LABEL,
            'periodes' => $this->repository->all()->pluck('periode')->unique()->sortDesc()->values(),
            'customers' => $this->repository->customers(),
            'routers' => $this->repository->routers(),
            'now' => now(),
        ]);
    }

    public function show(string $noInvoice): View
    {
        $invoice = $this->repository->find($noInvoice);
        abort_if($invoice === null, 404);

        return view('admin.billing.show', [
            'invoice' => $invoice,
            'qr_payload' => $invoice['no_invoice'].'|'.number_format($invoice['total'], 0, ',', '.').'|'.$invoice['no_cst'],
            'statusLabel' => BillingInvoiceRepository::LABEL_STATUS[$this->repository->effectiveStatus($invoice)],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $invoice = $this->repository->createManual($data);

        return redirect()
            ->route('admin.billing.show', $invoice['no_invoice'])
            ->with('success', 'Invoice '.$invoice['no_invoice'].' untuk '.$invoice['nama'].' berhasil dibuat (mode demo).');
    }

    public function recordPayment(Request $request, string $noInvoice): RedirectResponse
    {
        $request->validate(['metode' => ['required', 'in:cash,transfer,midtrans'], 'catatan' => ['nullable', 'string', 'max:255']]);

        $invoice = $this->repository->recordPayment($noInvoice, $request->input('metode'), $request->input('catatan'));
        abort_if($invoice === null, 404);

        return redirect()
            ->route('admin.billing.index')
            ->with('success', 'Pembayaran '.$invoice['nama'].' ('.$invoice['no_invoice'].') dicatat lunas via '.$request->input('metode').'.');
    }

    public function markPending(Request $request, string $noInvoice): RedirectResponse
    {
        $invoice = $this->repository->markPending($noInvoice);
        abort_if($invoice === null, 404);

        return redirect()
            ->route('admin.billing.index')
            ->with('success', 'Invoice '.$invoice['no_invoice'].' ditandai menunggu konfirmasi pembayaran.');
    }

    public function void(Request $request, string $noInvoice): RedirectResponse
    {
        $request->validate(['alasan' => ['nullable', 'string', 'max:255']]);

        $invoice = $this->repository->void($noInvoice, $request->input('alasan'));
        abort_if($invoice === null, 404);

        return redirect()
            ->route('admin.billing.index')
            ->with('success', 'Invoice '.$invoice['no_invoice'].' dibatalkan (void).');
    }

    public function generateBatch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'tanggal_terbit' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['required', 'date', 'after_or_equal:tanggal_terbit'],
            'target' => ['required', 'string', 'max:80'],
            'prorata' => ['sometimes', 'boolean'],
        ]);

        $result = $this->repository->generateBatch(
            $data['periode'],
            $data['tanggal_terbit'],
            $data['tanggal_jatuh_tempo'],
            $data['target'],
            filter_var($data['prorata'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );

        if ($result['created'] === 0) {
            return redirect()
                ->route('admin.billing.index')
                ->with('success', 'Tidak ada pelanggan baru untuk periode '.$data['periode'].' (semua sudah ditagih).');
        }

        return redirect()
            ->route('admin.billing.index')
            ->with('success', "Generate massal selesai: {$result['created']} lembar invoice untuk periode ".$data['periode'].' (total '.$this->rupiah($result['total']).').');
    }

    public function previewBatch(Request $request): \Illuminate\Http\JsonResponse
    {
        $periode = $request->validate(['periode' => ['required', 'date_format:Y-m']])['periode'];
        $targetRouter = (string) $request->input('target', 'all');
        $prorata = filter_var($request->input('prorata', false), FILTER_VALIDATE_BOOLEAN);

        $preview = $this->repository->previewBatch($periode, $targetRouter, $prorata);

        return response()->json([
            'count' => $preview['count'],
            'total' => $preview['total'],
            'total_formatted' => $this->rupiah($preview['total']),
            'targets' => collect($preview['targets'])->map(fn (array $row) => [
                'no_cst' => $row['no_cst'],
                'nama' => $row['nama'],
                'paket' => $row['paket'],
                'total' => $row['total'],
                'total_formatted' => $this->rupiah($row['total']),
                'prorata_note' => $row['prorata_note'],
            ])->values(),
        ]);
    }

    public function sendWaReminder(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $noInvoice = (string) $request->input('no_invoice');
        $template = $request->input('template');
        abort_unless(in_array($template, ['h3', 'hari_h', 'overdue'], true), 422);

        $invoice = $this->repository->find($noInvoice);
        abort_if($invoice === null, 404);

        $message = $this->waMessage($invoice, $template);
        $waLink = 'https://wa.me/'.preg_replace('/\D/', '', $invoice['no_hp']).'?text='.rawurlencode($message);

        $this->repository->markWaSent($noInvoice, $template);

        if ($request->ajax()) {
            return response()->json([
                'no_invoice' => $noInvoice,
                'nama' => $invoice['nama'],
                'wa_link' => $waLink,
            ]);
        }

        return redirect()
            ->route('admin.billing.index')
            ->with('success', 'Pesan WhatsApp untuk '.$invoice['nama'].' siap dikirim.')
            ->with('wa_link', $waLink);
    }

    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $request->only(['q', 'status', 'tipe', 'periode', 'dari', 'sampai']);
        $rows = $this->repository->filter($filters);

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'No Invoice', 'No CST', 'Nama', 'No HP', 'Tipe', 'Paket', 'Periode',
                'Terbit', 'Jatuh Tempo', 'Subtotal', 'Diskon', 'Biaya Admin', 'PPN', 'Total',
                'Status', 'Metode', 'Tanggal Bayar',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['no_invoice'], $row['no_cst'], $row['nama'], $row['no_hp'],
                    BillingInvoiceRepository::TIPE_LABEL[$row['tipe']] ?? $row['tipe'], $row['paket'], $row['periode'],
                    $row['tanggal_terbit'], $row['tanggal_jatuh_tempo'],
                    $row['subtotal'], $row['diskon'], $row['biaya_admin'], $row['ppn'], $row['total'],
                    BillingInvoiceRepository::LABEL_STATUS[$this->repository->effectiveStatus($row)] ?? $row['status'],
                    $row['metode_pembayaran'] ?? '', $row['tanggal_bayar'] ?? '',
                ]);
            }

            fclose($handle);
        };

        $filename = 'rekap-piutang-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload($callback, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Build pesan WhatsApp dari template preset.
     *
     * @param  array<string, mixed>  $invoice
     */
    private function waMessage(array $invoice, string $template): string
    {
        $nama = $invoice['nama'];
        $noInvoice = $invoice['no_invoice'];
        $total = $this->rupiah($invoice['total']);
        $jatuhTempo = Carbon::parse($invoice['tanggal_jatuh_tempo'])->translatedFormat('d F Y');
        $link = 'https://kiynet.app/pay/'.$noInvoice;
        $rekening = "Bank BCA 8820-1001-22 a.n. PT KINET uoi\nQRIS: https://qr.kiynet.app/pay/".$noInvoice;

        return match ($template) {
            'h3' => "Halo *{$nama}*, terima kasih telah berlangganan KiNet Internet.\n\n"
                ."Tagihan Anda sebesar *{$total}* (No. Invoice *{$noInvoice}*) akan jatuh tempo pada *{$jatuhTempo}*.\n\n"
                ."Pembayaran dapat dilakukan melalui:\n{$rekening}\n\n"
                ."Detail tagihan: {$link}\n\n"
                ."Terima kasih 🙏",
            'hari_h' => "Yth. *{$nama}*, hari ini adalah batas akhir pembayaran tagihan internet Anda "
                ."sebesar *{$total}* (Invoice *{$noInvoice}*).\n\n"
                ."Mohon segera lakukan pembayaran sebelum *{$jatuhTempo}* pukul 23.59 untuk menghindari penundaan layanan.\n\n"
                ."Pembayaran: {$rekening}\nBayar online: {$link}\n\n"
                ."Terima kasih.",
            default => "Yth. *{$nama}*, tagihan internet Anda sebesar *{$total}* (Invoice *{$noInvoice}*) "
                ."telah melewati jatuh tempo pada *{$jatuhTempo}*.\n\n"
                ."Mohon segera lunasi tagihan. Apabila tidak ada pembayaran hingga 3 hari ke depan, "
                ."layanan akan *otomatis diisolir* pada MikroTik.\n\n"
                ."Pembayaran: {$rekening}\nBayar online: {$link}\n\n"
                ."Terima kasih.",
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'no_cst' => ['required', 'string'],
            'tipe' => ['required', 'string'],
            'tanggal_terbit' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['required', 'date', 'after_or_equal:tanggal_terbit'],
            'periode' => ['nullable', 'date_format:Y-m'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.nama' => ['required', 'string', 'max:150'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.harga' => ['required', 'numeric', 'min:0'],
            'diskon' => ['nullable', 'numeric', 'min:0'],
            'biaya_admin' => ['nullable', 'numeric', 'min:0'],
            'ppn_enabled' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * @return \Closure(float): string
     */
    public function rupiahFormatter(): \Closure
    {
        return fn (float|int $value): string => $this->rupiah($value);
    }

    private function rupiah(float|int $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
