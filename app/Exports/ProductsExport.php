<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Product::with('category')->select([
            'id', 'name', 'price', 'is_active', 'category_id', 'external_id',
        ])->get();
    }

    public function headings(): array
    {
        return ['ID', 'External ID', 'Название', 'Цена', 'Статус', 'Категория'];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->external_id ?? '',
            $product->name,
            $product->price,
            $product->is_active ? 'Активен' : 'Неактивен',
            $product->category->name ?? '—',
        ];
    }
}
