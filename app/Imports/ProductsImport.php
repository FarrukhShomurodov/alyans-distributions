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

    /** Кэш категорий, чтобы не плодить запросы */
    private array $categoryCache = [];

    private function normalize(array $row): array
    {
        $map = [
            // Название
            'nazvanie' => 'name', 'название' => 'name', 'name' => 'name', 'naimenovanie' => 'name',
            // Цена
            'tsena' => 'price', 'цена' => 'price', 'price' => 'price', 'cena' => 'price',
            // Категория (родительская)
            'kategoriia' => 'category', 'категория' => 'category', 'category' => 'category', 'kategoria' => 'category',
            'kategoriia_id' => 'category_id', 'категория_id' => 'category_id', 'category_id' => 'category_id',
            // Группа (подкатегория)
            'группа' => 'group', 'gruppa' => 'group', 'group' => 'group', 'subcategory' => 'group',
            // Скидка
            'skidka' => 'discount', 'скидка' => 'discount', 'discount_percent' => 'discount', 'skidka_' => 'discount',
            // Статус
            'status' => 'status', 'статус' => 'status',
            'id' => 'id',
            // Внешний ID / артикул / код
            'external_id' => 'external_id', 'externalid' => 'external_id', 'внешний_id' => 'external_id',
            'артикул' => 'external_id', 'artikul' => 'external_id', 'sku' => 'external_id', 'код' => 'external_id',
            'ид' => 'external_id', 'id_1c' => 'external_id',
            // Бренд / торговая марка
            'торговая_марка' => 'brand', 'торговаямарка' => 'brand', 'марка' => 'brand',
            'brand' => 'brand', 'manufacturer' => 'brand', 'производитель' => 'brand',
            // Единица измерения
            'ед_изм' => 'unit', 'едизм' => 'unit', 'единица' => 'unit',
            'unit' => 'unit', 'единица_измерения' => 'unit',
            // Топ позиции
            'топ_позиции' => 'is_top', 'топпозиции' => 'is_top', 'топ' => 'is_top',
            'is_top' => 'is_top', 'top' => 'is_top', 'хит' => 'is_top',
        ];

        $normalized = [];
        foreach ($row as $key => $value) {
            $k = mb_strtolower(trim((string) $key));
            $k = preg_replace('/[^\p{L}\p{N}_]/u', '_', $k);
            $k = trim($k, '_');
            $canonical = $map[$k] ?? null;
            if ($canonical && !isset($normalized[$canonical])) {
                $normalized[$canonical] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Найти или создать категорию по имени. Если задана group — создаст подкатегорию (parent_id).
     */
    private function resolveCategory(?string $categoryName, ?string $groupName): ?int
    {
        $categoryName = trim((string) $categoryName);
        $groupName = trim((string) $groupName);

        if ($categoryName === '' && $groupName === '') {
            return null;
        }

        // Если есть только group — используем его как самостоятельную категорию
        if ($categoryName === '' && $groupName !== '') {
            return $this->getOrCreateCategory($groupName, null);
        }

        $parentId = $this->getOrCreateCategory($categoryName, null);

        if ($groupName !== '' && $groupName !== $categoryName) {
            return $this->getOrCreateCategory($groupName, $parentId);
        }

        return $parentId;
    }

    private function getOrCreateCategory(string $name, ?int $parentId): int
    {
        $cacheKey = $name . '|' . ($parentId ?? '0');
        if (isset($this->categoryCache[$cacheKey])) {
            return $this->categoryCache[$cacheKey];
        }

        $category = Category::where('name', $name)
            ->where('parent_id', $parentId)
            ->first();

        if (!$category) {
            $category = Category::create([
                'name' => $name,
                'description' => $name,
                'parent_id' => $parentId,
                'is_active' => true,
            ]);
        }

        return $this->categoryCache[$cacheKey] = $category->id;
    }

    private function parseBool($value): bool
    {
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return (int) $value !== 0;
        $s = mb_strtolower(trim((string) $value));
        if ($s === '') return false;
        return in_array($s, ['1', 'true', 'yes', 'да', 'топ позиции', 'топ', 'хит', 'on', '+'], true);
    }

    private function parseActive($value, bool $default = true): bool
    {
        if ($value === null || $value === '') return $default;
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return (int) $value !== 0;
        $s = mb_strtolower(trim((string) $value));
        return in_array($s, ['1', 'true', 'активен', 'активный', 'active', 'on', 'да'], true);
    }

    public function model(array $row)
    {
        $r = $this->normalize($row);

        $name = trim((string) ($r['name'] ?? ''));
        if ($name === '') return null;

        // Категория + Группа → дерево категорий
        $categoryId = null;
        if (!empty($r['category_id'])) {
            $categoryId = (int) $r['category_id'];
        } else {
            $categoryId = $this->resolveCategory($r['category'] ?? null, $r['group'] ?? null);
        }

        $payload = [
            'name'             => $name,
            'price'            => isset($r['price']) ? (float) $r['price'] : 0,
            'category_id'      => $categoryId,
            'is_active'        => $this->parseActive($r['status'] ?? null, true),
            'discount_percent' => isset($r['discount']) ? (float) $r['discount'] : 0,
            'external_id'      => isset($r['external_id']) ? trim((string) $r['external_id']) : null,
            'brand'            => isset($r['brand']) ? trim((string) $r['brand']) : null,
            'unit'             => isset($r['unit']) ? trim((string) $r['unit']) : null,
            'is_top'           => $this->parseBool($r['is_top'] ?? null),
        ];

        // Поиск существующего
        $existingProduct = null;
        if (!empty($r['id'])) {
            $existingProduct = Product::find($r['id']);
        }
        if (!$existingProduct && !empty($payload['external_id'])) {
            $existingProduct = Product::where('external_id', $payload['external_id'])->first();
        }
        if (!$existingProduct) {
            $existingProduct = Product::where('name', $name)->first();
        }

        if ($existingProduct) {
            // обновляем только непустые поля
            $updates = array_filter($payload, fn($v) => $v !== null && $v !== '');
            $existingProduct->update($updates);
            $this->updated++;
            return null;
        }

        if (!$categoryId) {
            $this->skippedRows[] = $name . ' (нет категории)';
            return null;
        }

        $this->imported++;
        return new Product($payload);
    }

    public function getImportedCount(): int { return $this->imported; }
    public function getUpdatedCount(): int { return $this->updated; }
    public function getSkippedRows(): array { return $this->skippedRows; }
}
