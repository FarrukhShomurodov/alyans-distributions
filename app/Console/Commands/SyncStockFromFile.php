<?php

namespace App\Console\Commands;

use App\Services\OneCIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SyncStockFromFile extends Command
{
    protected $signature = 'stock:sync-file
        {file? : Path to CSV/Excel file (relative to storage/app)}
        {--disk=local : Storage disk}
        {--source=sova : Source name for history}
        {--delete-after : Delete file after processing}';

    protected $description = 'Sync stock quantities from CSV/Excel file (universal: СОВА, 1С, manual)';

    public function handle(OneCIntegrationService $service): int
    {
        $file = $this->argument('file') ?? 'stock-sync/stock.csv';
        $disk = $this->option('disk');
        $source = $this->option('source');

        if (!Storage::disk($disk)->exists($file)) {
            $this->info("File not found: {$file} (disk: {$disk}). Skipping.");
            return self::SUCCESS;
        }

        $this->info("Processing: {$file}");

        try {
            $filePath = Storage::disk($disk)->path($file);
            $items = $this->parseFile($filePath);

            if (empty($items)) {
                $this->warn('No valid rows found in file.');
                return self::SUCCESS;
            }

            $this->info("Found " . count($items) . " items to sync.");

            $result = $service->syncStocks($items, $source);

            $this->info("Updated: {$result['updated']}, Skipped: {$result['skipped']}");

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $err) {
                    $this->warn("Row {$err['index']}: {$err['reason']}");
                }
            }

            Log::info('Stock sync from file completed', [
                'file' => $file,
                'source' => $source,
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'errors_count' => count($result['errors']),
            ]);

            if ($this->option('delete-after')) {
                Storage::disk($disk)->delete($file);
                $this->info("Deleted processed file: {$file}");
            }

        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            Log::error('Stock sync from file failed', ['error' => $e->getMessage(), 'file' => $file]);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function parseFile(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $items = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            $items = $this->parseExcel($filePath);
        } elseif ($ext === 'csv') {
            $items = $this->parseCsv($filePath);
        } elseif ($ext === 'xml') {
            $items = $this->parseXml($filePath);
        } else {
            throw new \RuntimeException("Unsupported file format: {$ext}. Use csv, xlsx, xls, or xml.");
        }

        return $items;
    }

    private function parseCsv(string $filePath): array
    {
        $items = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) return [];

        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) return [];

        $header = array_map(fn($h) => $this->normalizeHeader(trim($h)), $header);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $mapped = array_combine($header, array_pad($row, count($header), ''));
            $item = $this->mapRow($mapped);
            if ($item) $items[] = $item;
        }

        fclose($handle);
        return $items;
    }

    private function parseExcel(string $filePath): array
    {
        $items = [];
        $rows = Excel::toArray(null, $filePath);

        if (empty($rows) || empty($rows[0])) return [];

        $sheet = $rows[0];
        $header = array_map(fn($h) => $this->normalizeHeader(trim((string)$h)), $sheet[0]);

        for ($i = 1; $i < count($sheet); $i++) {
            $mapped = array_combine($header, array_pad($sheet[$i], count($header), ''));
            $item = $this->mapRow($mapped);
            if ($item) $items[] = $item;
        }

        return $items;
    }

    /**
     * Parse XML file from СОВА or any stock management system.
     * Supports multiple XML structures:
     *   <items><item><code>ABC</code><quantity>10</quantity></item></items>
     *   <products><product code="ABC" quantity="10"/></products>
     *   <stock><row><kod>ABC</kod><kolichestvo>10</kolichestvo></row></stock>
     */
    private function parseXml(string $filePath): array
    {
        $items = [];
        $xml = simplexml_load_file($filePath);
        if (!$xml) {
            throw new \RuntimeException('Failed to parse XML file.');
        }

        // Find the repeating child elements (items/products/rows)
        $children = $xml->children();
        if ($children->count() === 0) return [];

        foreach ($children as $node) {
            $attrs = [];

            // Collect from XML attributes (e.g. <item code="ABC" qty="10"/>)
            foreach ($node->attributes() as $key => $val) {
                $attrs[mb_strtolower((string)$key)] = (string)$val;
            }

            // Collect from child elements (e.g. <code>ABC</code>)
            foreach ($node->children() as $child) {
                $attrs[mb_strtolower($child->getName())] = (string)$child;
            }

            // Normalize keys
            $mapped = [];
            foreach ($attrs as $key => $val) {
                $mapped[$this->normalizeHeader($key)] = $val;
            }

            $item = $this->mapRow($mapped);
            if ($item) $items[] = $item;
        }

        return $items;
    }

    /**
     * Normalize header/tag names (support Russian & English).
     */
    private function normalizeHeader(string $h): string
    {
        $h = mb_strtolower($h);
        $map = [
            // product identifiers
            'код товара' => 'external_id',
            'код' => 'external_id',
            'code' => 'external_id',
            'артикул' => 'external_id',
            'external_id' => 'external_id',
            'article' => 'external_id',
            'artikul' => 'external_id',
            'kod' => 'external_id',
            'kodtovara' => 'external_id',
            'sku' => 'external_id',
            'barcode' => 'external_id',
            'штрихкод' => 'external_id',
            'id товара' => 'product_id',
            'product_id' => 'product_id',
            'id' => 'product_id',
            // quantity
            'количество' => 'quantity',
            'остаток' => 'quantity',
            'остатки' => 'quantity',
            'кол-во' => 'quantity',
            'кол' => 'quantity',
            'quantity' => 'quantity',
            'qty' => 'quantity',
            'stock' => 'quantity',
            'kolichestvo' => 'quantity',
            'ostatok' => 'quantity',
            'count' => 'quantity',
        ];

        return $map[$h] ?? $h;
    }

    private function mapRow(array $row): ?array
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
