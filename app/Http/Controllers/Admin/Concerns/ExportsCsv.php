<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a filtered Eloquent query to a CSV download. Chunked so large exports stay
 * memory-safe; the filename is timestamped.
 */
trait ExportsCsv
{
    /**
     * @template TModel of Model
     *
     * @param  list<string>  $headers
     * @param  Builder<TModel>  $query
     * @param  callable(TModel): list<mixed>  $row
     */
    protected function streamCsv(string $name, array $headers, Builder $query, callable $row): StreamedResponse
    {
        $filename = $name.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $query, $row): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            // cursor() streams one row at a time from a single query, so it stays memory-safe
            // for large exports while preserving the query's own ordering.
            foreach ($query->cursor() as $record) {
                fputcsv($out, array_map(static fn ($v) => $v ?? '', $row($record)));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
