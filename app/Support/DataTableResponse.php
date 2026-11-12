<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataTableResponse
{
    public static function from(
        Request $request,
        Builder $query,
        Closure $applySearch,
        array $sortableColumns,
        Closure $transform,
        string $defaultOrder = 'created_at'
    ): JsonResponse {
        $draw = max((int) $request->input('draw', 0), 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = min(max((int) $request->input('length', 10), 1), 100);
        $countQuery = clone $query;
        $countQuery->getQuery()->orders = null;
        $countQuery->getQuery()->limit = null;
        $countQuery->getQuery()->offset = null;
        $recordsTotal = $countQuery->count();
        $filteredQuery = clone $query;
        $search = mb_substr(trim((string) $request->input('search.value', '')), 0, 100);

        if ($search !== '') {
            $applySearch($filteredQuery, $search);
        }

        $recordsFiltered = (clone $filteredQuery)->count();
        $columnIndex = (int) $request->input('order.0.column', -1);
        $column = (string) $request->input("columns.{$columnIndex}.data", '');
        $direction = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (isset($sortableColumns[$column])) {
            $filteredQuery->orderBy($sortableColumns[$column], $direction);
        }
        $filteredQuery->orderBy($defaultOrder, 'desc');

        $rows = $filteredQuery
            ->offset($start)
            ->limit($length)
            ->get()
            ->map($transform)
            ->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }
}
