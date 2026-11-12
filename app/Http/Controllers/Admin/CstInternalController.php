<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CstInternalRepository;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CstInternalController extends Controller
{
    public const SORTABLE = ['id', 'nama', 'wilayah', 'paket', 'pppoe_username', 'status'];

    public function __construct(private readonly CstInternalRepository $repository)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'wilayah', 'paket', 'router']);
        $sort = $request->query('sort', 'id');
        $sort = in_array($sort, self::SORTABLE, true) ? $sort : 'id';
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $customers = $this->repository->filter($filters)->{
            $direction === 'desc' ? 'sortByDesc' : 'sortBy'
        }(...[fn (array $row) => mb_strtolower((string) ($row[$sort] ?? ''))]);
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 25, 50], true) ? $perPage : 10;

        $paginator = new LengthAwarePaginator(
            $customers->forPage($request->integer('page', 1), $perPage)->values(),
            $customers->count(),
            $perPage,
            $request->integer('page', 1),
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.cst-internal.index', [
            'customers' => $paginator->getCollection(),
            'paginator' => $paginator,
            'perPage' => $perPage,
            'filters' => $filters,
            'statuses' => CstInternalRepository::STATUSES,
            'wilayahs' => $this->repository->options('wilayah'),
            'pakets' => $this->repository->options('paket'),
            'routers' => $this->repository->options('router'),
            'total' => $this->repository->all()->count(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(string $cst): View
    {
        $customer = $this->repository->find($cst);
        abort_if($customer === null, 404);

        return view('admin.cst-internal.show', [
            'customer' => $customer,
            'statuses' => CstInternalRepository::STATUSES,
            'wilayahs' => $this->repository->options('wilayah'),
            'pakets' => $this->repository->options('paket'),
            'routers' => $this->repository->options('router'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'no_hp' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['required', 'string', 'max:500'],
            'wilayah' => ['required', 'string', 'max:100'],
            'paket' => ['required', 'string', 'max:100'],
            'router' => ['required', 'string', 'max:100'],
            'pppoe_username' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $record = $this->repository->create($data);

        return redirect()
            ->route('admin.cst-internal.show', $record['id'])
            ->with('success', 'Registrasi CST '.$record['id'].' berhasil. Data demo tersimpan pada sesi ini.');
    }

    public function update(Request $request, string $cst): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'no_hp' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['required', 'string', 'max:500'],
            'wilayah' => ['required', 'string', 'max:100'],
            'paket' => ['required', 'string', 'max:100'],
            'router' => ['required', 'string', 'max:100'],
            'pppoe_username' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        abort_if($this->repository->find($cst) === null, 404);
        $this->repository->updateProfile($cst, $data);

        return redirect()
            ->route('admin.cst-internal.show', $cst)
            ->with('success', 'Data pelanggan '.$cst.' diperbarui (mode demo).');
    }

    public function updateStatus(Request $request, string $cst): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', CstInternalRepository::STATUSES)],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        abort_if($this->repository->find($cst) === null, 404);
        $this->repository->updateStatus($cst, $data['status'], $data['reason']);

        return redirect()
            ->route('admin.cst-internal.show', $cst)
            ->with('success', 'Status pelanggan diubah menjadi '.$data['status'].'. Perubahan tercatat pada timeline.');
    }

    public function export(Request $request): StreamedResponse
    {
        $customers = $this->repository->filter($request->only(['q', 'status', 'wilayah', 'paket', 'router']));

        return response()->streamDownload(function () use ($customers): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Nomor CST', 'Nama', 'No. HP', 'Email', 'Alamat', 'Wilayah', 'Cluster', 'ODP',
                'Paket', 'Bandwidth', 'Router', 'PPPoE', 'Status', 'Tanggal Aktivasi', 'Jatuh Tempo',
            ]);

            foreach ($customers as $customer) {
                fputcsv($stream, [
                    $customer['id'],
                    $customer['nama'],
                    $customer['no_hp'],
                    $customer['email'],
                    $customer['alamat'],
                    $customer['wilayah'],
                    $customer['cluster'],
                    $customer['odp'],
                    $customer['paket'],
                    $customer['bandwidth'],
                    $customer['router'],
                    $customer['pppoe_username'],
                    $customer['status'],
                    $customer['tanggal_aktivasi'] ?? '-',
                    $customer['jatuh_tempo'] ?? '-',
                ]);
            }

            fclose($stream);
        }, 'cst-internal-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
