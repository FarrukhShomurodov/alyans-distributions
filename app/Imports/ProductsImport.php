<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ProductsImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    private int $imported = 0;
    private int $updated = 0;
    private array $skippedRows = [];

    private function normalize(array $row): array
    {
        $map = [
            'nazvanie' => 'name', 'название' => 'name', 'name' => 'name', 'naimenovanie' => 'name',
            'tsena' => 'price', 'цена' => 'price', 'price' => 'price', 'cena' => 'price',
            'kategoriia' => 'category', 'категория' => 'category', 'category' => 'category', 'kategoria' => 'category',
            'kategoriia_id' => 'category_id', 'категория_id' => 'category_id', 'category_id' => 'category_id',
            'skidka' => 'discount', 'скидка' => 'discount', 'discount_percent' => 'discount', 'skidka_' => 'discount',
            'status' => 'status', 'статус' => 'status',
            'id' => 'id',
            'external_id' => 'external_id', 'externalid' => 'external_id', 'внешний_id' => 'external_id',
            'артикул' => 'external_id', 'artikul' => 'external_id', 'sku' => 'external_id', 'код' => 'external_id',
        ];

        $normalized = [];
        foreach ($row as $key => $value) {
            $k = mb_strtolower(trim((string) $key));
            $k = preg_replace('/[^\p{L}\p{N}_]/u', '', $k);
            $canonical = $map[$k] ?? null;
            if ($canonical && !isset($normalized[$canonical])) {
                $normalized[$canonical] = $value;
            }
        }

        return $normalized;
    }

    public function model(array $row)
    {
        $r = $this->normalize($row);

        $categoryId = null;
        if (!empty($r['category'])) {
            $category = Category::where('name', $r['category'])->first();
            $categoryId = $category?->id;
        } elseif (!empty($r['category_id'])) {
            $categoryId = (int) $r['category_id'];
        }

        $existingProduct = null;
        if (!empty($r['id'])) {
            $existingProduct = Product::find($r['id']);
        }
        if (!$existingProduct && !empty($r['external_id'])) {
            $existingProduct = Product::where('external_id', $r['external_id'])->first();
        }
        if (!$existingProduct && !empty($r['name'])) {
            $existingProduct = Product::where('name', $r['name'])->first();
        }

        if ($existingProduct) {
            $updates = array_filter([
                'name' => $r['name'] ?? null,
                'price' => isset($r['price']) ? (float) $r['price'] : null,
                'category_id' => $categoryId,
                'is_active' => isset($r['status']) ? ($r['status'] === 'Активен' || $r['status'] == 1) : null,
                'discount_percent' => isset($r['discount']) ? (float) $r['discount'] : null,
                'external_id' => $r['external_id'] ?? null,
            ], fn($v) => $v !== null);

            $existingProduct->update($updates);
            $this->updated++;
            return null;
        }

        $name = $r['name'] ?? null;
        if (!$name) return null;

        if (!$categoryId) {
            $this->skippedRows[] = $name;
            return null;
        }

        $this->imported++;

        return new Product([
            'name' => $name,
            'price' => isset($r['price']) ? (float) $r['price'] : 0,
            'category_id' => $categoryId,
            'is_active' => isset($r['status']) ? ($r['status'] === 'Активен' || $r['status'] == 1) : true,
            'discount_percent' => isset($r['discount']) ? (float) $r['discount'] : 0,
            'external_id' => $r['external_id'] ?? null,
        ]);
    }

    public function getImportedCount(): int { return $this->imported; }
    public function getUpdatedCount(): int { return $this->updated; }
    public function getSkippedRows(): array { return $this->skippedRows; }
}
