<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;

class ProductSearch
{
    /**
     * Применяет умный поиск по названию товара.
     *
     * - Если запрос — число, ищется также по id и external_id (код SOVA)
     * - Запрос разбивается на слова, каждое должно встретиться в name
     *   (порядок неважен, регистр неважен)
     *
     * @param Builder|BuilderContract $query
     * @param string|null $search
     */
    public static function apply($query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $query->where(function ($outer) use ($search) {
            // Точное совпадение по id или external_id
            if (ctype_digit($search)) {
                $outer->orWhere('id', (int) $search);
                $outer->orWhere('external_id', $search);
            }

            // Многословный поиск: все токены должны встретиться в name
            $tokens = preg_split('/\s+/u', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (!empty($tokens)) {
                $outer->orWhere(function ($w) use ($tokens) {
                    foreach ($tokens as $token) {
                        $w->where('name', 'ILIKE', '%' . $token . '%');
                    }
                });
            }
        });
    }
}
