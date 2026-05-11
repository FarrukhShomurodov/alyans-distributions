<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Requests\StockRequest;
use App\Models\Stock;
use App\Services\OneCIntegrationService;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

class StockController
{
    protected StockService $service;

    public function __construct(StockService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): View
    {
        $stockFilter = $request->string('stock_filter')->toString();
        $search = trim((string) $request->query('search', ''));
        $allowedFilters = ['', 'out', 'low', 'ok', 'restock'];

        if (!in_array($stockFilter, $allowedFilters, true)) {
            $stockFilter = '';
        }

        $stocks = Stock::with('product')
            ->select('*')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    if (ctype_digit($search)) {
                        $subQuery->where('id', $search)
                            ->orWhere('product_id', $search);
                    }
                    $subQuery->orWhereHas('product', function ($productQuery) use ($search) {
                        \App\Support\ProductSearch::apply($productQuery, $search);
                    });
                });
            })
            ->when($stockFilter === 'out', fn ($query) => $query->where('quantity', 0))
            ->when($stockFilter === 'low', fn ($query) => $query->whereBetween('quantity', [1, 5]))
            ->when($stockFilter === 'restock', fn ($query) => $query->where('quantity', '<=', 5))
            ->when($stockFilter === 'ok', fn ($query) => $query->where('quantity', '>', 5))
            ->orderByRaw('
            CASE
                WHEN quantity = 0 THEN 0
                WHEN quantity <= 5 THEN 1
                ELSE 2
            END
        ')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.stocks.index', compact('stocks', 'stockFilter', 'search'));
    }

    public function edit(Stock $stock): View
    {
        $histories = $stock->history;

        return view('admin.stocks.edit', compact('stock', 'histories'));
    }

    public function show(Stock $stock, Request $request): View
    {
        $history = $stock->history()
            ->when($request->source, fn ($q) => $q->where('source', $request->source))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.stocks.show', compact('stock', 'history'));
    }

    public function update(Stock $stock, StockRequest $request): RedirectResponse
    {
        $this->service->update($stock, (array) $request->validated());

        return redirect()->route('stocks.index')->with('success', 'Остатки успешно обновлены!');
    }

    public function import(Request $request, OneCIntegrationService $syncService): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'source' => 'nullable|string|in:sova,1c,file,manual',
        ]);

        $source = $request->input('source', 'file');

        try {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            $items = [];

            if (in_array($ext, ['xlsx', 'xls'])) {
                $rows = Excel::toArray(null, $file);
                if (!empty($rows[0])) {
                    $sheet = $rows[0];
                    $header = array_map(fn($h) => $this->normalizeStockHeader(trim((string)$h)), $sheet[0]);
                    for ($i = 1; $i < count($sheet); $i++) {
                        $mapped = array_combine($header, array_pad($sheet[$i], count($header), ''));
                        $item = $this->mapStockRow($mapped);
                        if ($item) $items[] = $item;
                    }
                }
            } else {
                $handle = fopen($file->getRealPath(), 'r');
                $firstLine = fgets($handle);
                rewind($handle);
                $delimiter = str_contains($firstLine, ';') ? ';' : ',';
                $header = fgetcsv($handle, 0, $delimiter);
                $header = array_map(fn($h) => $this->normalizeStockHeader(trim($h)), $header);
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $mapped = array_combine($header, array_pad($row, count($header), ''));
                    $item = $this->mapStockRow($mapped);
                    if ($item) $items[] = $item;
                }
                fclose($handle);
            }

            if (empty($items)) {
                return back()->with('error', 'Файл пуст или не содержит корректных данных.');
            }

            $result = $syncService->syncStocks($items, $source);

            return back()->with('success',
                "Импорт завершён: обновлено {$result['updated']}, пропущено {$result['skipped']}" .
                (!empty($result['errors']) ? ', ошибок: ' . count($result['errors']) : '')
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Ошибка импорта: ' . $e->getMessage());
        }
    }

    public function export()
    {
        $stocks = Stock::with('product')->get();

        $csv = "ID товара;Код товара;Название;Количество\n";
        foreach ($stocks as $stock) {
            $csv .= implode(';', [
                $stock->product_id,
                $stock->product?->external_id ?? '',
                '"' . str_replace('"', '""', $stock->product?->name ?? '') . '"',
                $stock->quantity,
            ]) . "\n";
        }

        return Response::make("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="stocks_' . date('Y-m-d') . '.csv"',
        ]);
    }

    public function template()
    {
        $csv = "код товара;количество\n";
        $csv .= "ABC-001;50\n";
        $csv .= "ABC-002;100\n";

        return Response::make("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="stock_template.csv"',
        ]);
    }

    private function normalizeStockHeader(string $h): string
    {
        $h = mb_strtolower($h);
        $map = [
            'код товара' => 'external_id', 'код' => 'external_id', 'code' => 'external_id',
            'артикул' => 'external_id', 'external_id' => 'external_id',
            'id товара' => 'product_id', 'product_id' => 'product_id', 'id' => 'product_id',
            'количество' => 'quantity', 'остаток' => 'quantity', 'кол-во' => 'quantity',
            'quantity' => 'quantity', 'qty' => 'quantity', 'stock' => 'quantity',
        ];
        return $map[$h] ?? $h;
    }

    private function mapStockRow(array $row): ?array
    {
        $externalId = $row['external_id'] ?? null;
        $productId = $row['product_id'] ?? null;
        $quantity = $row['quantity'] ?? null;

        if ($quantity === null || $quantity === '') return null;
        if (!$externalId && !$productId) return null;

        $item = ['quantity' => (int) $quantity];
        if ($externalId) $item['external_id'] = trim((string) $externalId);
        if ($productId) $item['product_id'] = (int) $productId;
        return $item;
    }
}
