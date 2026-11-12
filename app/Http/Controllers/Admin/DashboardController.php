<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Router;
use App\Models\Tagihan;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const CHART_RANGES = ['7d', '30d', '3m', '12m'];

    public function index(Request $request): View
    {
        $mock = $this->mockData();
        $hasRecords = Pelanggan::exists() || Tagihan::exists() || Pembayaran::exists();

        return view('admin.dashboard', [
            'demoMode' => ! $hasRecords,
            'customerStats' => $hasRecords ? $this->customerStats() : $mock['customerStats'],
            'partnerStats' => $this->partnerStats($mock['partnerStats']),
            'billing' => $hasRecords ? $this->billingStats() : $mock['billing'],
            'revenue' => $hasRecords ? $this->revenueSeries($mock['revenue']) : $mock['revenue'],
            'network' => $this->networkStats($mock['network']),
            'routers' => $this->routerRows($mock['routers']),
            'incidents' => $mock['incidents'],
            'today' => CarbonImmutable::today(),
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'range' => ['required', 'in:'.implode(',', self::CHART_RANGES)],
        ]);

        $demoMode = ! Pelanggan::exists() && ! Tagihan::exists() && ! Pembayaran::exists();

        return response()->json($this->buildChartData($validated['range'], $demoMode));
    }

    /**
     * Static sample data for widgets whose backend endpoints do not exist yet.
     *
     * TODO(api): replace every value sourced from here with its real endpoint.
     */
    private function mockData(): array
    {
        $path = resource_path('data/dashboard-mock.json');

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function customerStats(): array
    {
        return [
            'total' => Pelanggan::count(),
            'aktif' => Pelanggan::where('status', 'aktif')->count(),
            'isolir' => Pelanggan::where('status', 'isolir')->count(),
            'menunggak' => Pelanggan::whereIn(
                'id',
                Tagihan::query()
                    ->whereIn('status', ['belum_bayar', 'sebagian'])
                    ->select('pelanggan_id'),
            )->count(),
            'pending' => Pelanggan::where('status', 'calon')->count(),
            'churn' => Pelanggan::where('status', 'nonaktif')->count(),
        ];
    }

    private function partnerStats(array $mock): array
    {
        // TODO(api): verifikasi dokumen, masa kontrak, dan tagihan bandwidth mitra
        // belum punya tabel/endpoint backend; nilai berikut masih data contoh.
        return [
            'aktif' => Mitra::where('status', 'aktif')->count(),
            'menungguVerifikasi' => $mock['menungguVerifikasi'],
            'kontrakSegeraBerakhir' => $mock['kontrakSegeraBerakhir'],
            'tagihanBandwidthTertunggak' => $mock['tagihanBandwidthTertunggak'],
            'tagihanBandwidthNominal' => $mock['tagihanBandwidthNominal'],
        ];
    }

    private function billingStats(): array
    {
        $unpaid = ['belum_bayar', 'sebagian'];

        return [
            'invoiceTerbit' => Tagihan::whereIn('status', ['belum_bayar', 'sebagian', 'lunas'])->count(),
            'lunas' => Tagihan::where('status', 'lunas')->count(),
            'belumLunas' => Tagihan::whereIn('status', $unpaid)->count(),
            'jatuhTempoHariIni' => Tagihan::whereIn('status', $unpaid)
                ->whereDate('jatuh_tempo', CarbonImmutable::today()->toDateString())
                ->count(),
            'piutangAktif' => (float) Tagihan::whereIn('status', $unpaid)
                ->selectRaw('COALESCE(SUM(total_tagihan - total_dibayar), 0) AS total')
                ->value('total'),
        ];
    }

    /**
     * Build the revenue comparison series from real payments plus sample wholesale data.
     *
     * TODO(api): wholesale/bandwidth revenue needs its own ledger endpoint.
     */
    private function revenueSeries(array $mock): array
    {
        $today = CarbonImmutable::today();
        $monthlyLabels = [];
        $retail = [];

        for ($offset = 11; $offset >= 0; $offset--) {
            $month = $today->startOfMonth()->subMonths($offset);
            $monthlyLabels[] = $month->locale('id')->isoFormat('MMM YY');
            $retail[] = (int) Pembayaran::where('status', 'berhasil')
                ->whereBetween('dibayar_at', [$month->startOfMonth(), $month->endOfMonth()])
                ->sum('nominal');
        }

        $wholesaleSource = $mock['monthly']['wholesale'];
        $wholesale = [];
        foreach (array_keys($monthlyLabels) as $index) {
            $wholesale[] = (int) ($wholesaleSource[$index % count($wholesaleSource)] ?? 0);
        }

        $dailyLabels = [];
        $dailyRetail = [];
        foreach (range(1, $today->day) as $day) {
            $date = $today->startOfMonth()->addDays($day - 1);
            $dailyLabels[] = $date->locale('id')->isoFormat('D MMM');
            $dailyRetail[] = (int) Pembayaran::where('status', 'berhasil')
                ->whereDate('dibayar_at', $date->toDateString())
                ->sum('nominal');
        }

        return [
            'monthly' => [
                'labels' => $monthlyLabels,
                'retail' => $retail,
                'wholesale' => $wholesale,
            ],
            'daily' => [
                'labels' => $dailyLabels,
                'retail' => $dailyRetail,
                'wholesale' => array_slice(
                    $mock['daily']['wholesale'],
                    0,
                    count($dailyLabels),
                ),
            ],
        ];
    }

    private function networkStats(array $mock): array
    {
        // TODO(api): CPU load dan latency harus diambil dari endpoint health-check MikroTik.
        return [
            'totalRouter' => max(Router::count(), $mock['totalRouter']),
            'online' => $mock['online'],
            'offline' => $mock['offline'],
            'avgCpuLoad' => $mock['avgCpuLoad'],
            'avgLatencyMs' => $mock['avgLatencyMs'],
        ];
    }

    private function routerRows(array $mockRouters): array
    {
        $routers = Router::query()->orderBy('nama_router')->get();

        if ($routers->isEmpty()) {
            // TODO(api): ganti dengan respons MikroTik API per router.
            return $mockRouters;
        }

        return $routers->map(fn (Router $router) => [
            'name' => $router->nama_router,
            'ip' => $router->ip_address,
            'routeros' => '—',
            'uptime' => '—',
            'status' => $router->status === 'aktif' ? 'unknown' : 'offline',
            'cpu' => null,
            'latency' => null,
            'api' => 'unknown',
            'ssh' => 'unknown',
            'location' => $router->lokasi,
        ])->all();
    }

    private function buildChartData(string $range, bool $demoMode = false): array
    {
        $today = CarbonImmutable::today();
        $monthly = in_array($range, ['3m', '12m'], true);

        if ($monthly) {
            $periodCount = $range === '3m' ? 3 : 12;
            $firstPeriod = $today->startOfMonth()->subMonths($periodCount - 1);
            $start = $firstPeriod;
            $end = $today->endOfDay();
            $periods = collect(range(0, $periodCount - 1))
                ->map(fn (int $offset) => $firstPeriod->addMonths($offset));
            $groupExpression = "TO_CHAR(DATE_TRUNC('month', %s), 'YYYY-MM')";
        } else {
            $periodCount = $range === '7d' ? 7 : 30;
            $start = $today->subDays($periodCount - 1)->startOfDay();
            $end = $today->endOfDay();
            $periods = collect(range(0, $periodCount - 1))
                ->map(fn (int $offset) => $start->addDays($offset));
            $groupExpression = "TO_CHAR(DATE_TRUNC('day', %s), 'YYYY-MM-DD')";
        }

        $keyFor = fn (CarbonImmutable $date): string => $monthly
            ? $date->format('Y-m')
            : $date->format('Y-m-d');
        $labels = $periods->map(fn (CarbonImmutable $date) => $date->locale('id')->isoFormat($monthly ? 'MMM YY' : 'D MMM'))->all();
        $keys = $periods->map($keyFor);

        if ($demoMode) {
            $demoShape = $monthly
                ? [0.62, 0.78, 0.7, 0.88, 0.76, 0.94, 0.82, 1, 0.91, 1.08, 0.97, 1.15]
                : [0.2, 0.3, 0.18, 0.45, 0.34, 0.58, 0.42, 0.68, 0.55, 0.8, 0.62, 0.9, 0.72, 1];
            $shape = collect(range(0, $periodCount - 1))
                ->map(fn (int $index): float => $demoShape[$index % count($demoShape)])
                ->all();
            $invoices = array_map(fn (float $weight) => round(950000 * $weight), $shape);
            $payments = array_map(fn (float $weight) => round(720000 * $weight), $shape);
            $newCustomers = array_map(fn (float $weight) => (int) round(5 * $weight), $shape);
            $activeCustomers = array_map(fn (int $new) => $new * 4, $newCustomers);
            $isolatedCustomers = array_map(fn (int $new) => $new, $newCustomers);

            return [
                'labels' => $labels,
                'tagihan' => $invoices,
                'pembayaran' => $payments,
                'pelangganBaru' => $newCustomers,
                'pelangganAktif' => $activeCustomers,
                'pelangganTerisolir' => $isolatedCustomers,
                'demo' => true,
            ];
        }

        $invoiceExpression = sprintf($groupExpression, 'tanggal_tagihan');
        $invoices = Tagihan::query()
            ->whereBetween('tanggal_tagihan', [$start->toDateString(), $end->toDateString()])
            ->selectRaw($invoiceExpression.' AS period, SUM(total_tagihan) AS total')
            ->groupByRaw($invoiceExpression)
            ->pluck('total', 'period');

        $paymentExpression = sprintf($groupExpression, 'dibayar_at');
        $payments = Pembayaran::query()
            ->where('status', 'berhasil')
            ->whereBetween('dibayar_at', [$start, $end])
            ->selectRaw($paymentExpression.' AS period, SUM(nominal) AS total')
            ->groupByRaw($paymentExpression)
            ->pluck('total', 'period');

        $customerExpression = sprintf($groupExpression, 'created_at');
        $customerRows = Pelanggan::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw($customerExpression.' AS period, status, COUNT(*) AS total')
            ->groupByRaw($customerExpression.', status')
            ->get();
        $customersByPeriod = $customerRows->groupBy('period');

        return [
            'labels' => $labels,
            'tagihan' => $keys->map(fn (string $key) => (float) $invoices->get($key, 0))->values()->all(),
            'pembayaran' => $keys->map(fn (string $key) => (float) $payments->get($key, 0))->values()->all(),
            'pelangganBaru' => $keys->map(fn (string $key) => (int) $customersByPeriod->get($key, collect())->sum('total'))->values()->all(),
            'pelangganAktif' => $keys->map(fn (string $key) => (int) $customersByPeriod->get($key, collect())->where('status', 'aktif')->sum('total'))->values()->all(),
            'pelangganTerisolir' => $keys->map(fn (string $key) => (int) $customersByPeriod->get($key, collect())->where('status', 'isolir')->sum('total'))->values()->all(),
            'demo' => false,
        ];
    }
}
