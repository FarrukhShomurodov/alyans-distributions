<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrdersExport implements FromCollection, WithColumnFormatting, WithHeadings, WithMapping
{
    public function collection()
    {
        return Order::with('user')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Покупатель',
            'Телефон доставки',
            'Адрес доставки',
            'Тип доставки',
            'Сумма',
            'Тип оплаты',
            'Статус оплаты',
            'Статус заказа',
            'Создан',
        ];
    }

    public function map($order): array
    {
        $fullName = trim(($order->user->first_name ?? '').' '.($order->user->second_name ?? ''));

        return [
            $order->id,
            $fullName !== '' ? $fullName : '—',
            (string) ($order->delivery_phone ?? ''),
            (string) ($order->delivery_address ?? ''),
            (string) ($order->delivery_type ?? ''),
            $order->total,
            $order->getPaymentNameAttribute(),
            $order->getPaymentStatusNameAttribute(),
            $order->getStatusNameAttribute(),
            $order->created_at->format('d.m.Y H:i'),
        ];
    }

    // ✅ Заставляем Excel считать колонку "Телефон" текстом
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
