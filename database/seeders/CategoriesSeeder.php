<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Акции',
                'description' => 'Спецпредложения и скидки на стройматериалы',
                'is_active' => true,
            ],
            [
                'name' => 'Инструменты',
                'description' => 'Электроинструмент, ручной инструмент и оснастка',
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Электроинструмент',
                        'description' => 'Дрели, перфораторы, болгарки, шуруповёрты',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Ручной инструмент',
                        'description' => 'Молотки, отвёртки, ключи, рулетки',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Оснастка и расходники',
                        'description' => 'Свёрла, биты, диски, насадки',
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'name' => 'Крепёж',
                'description' => 'Саморезы, гвозди, анкеры, дюбели',
                'is_active' => true,
            ],
            [
                'name' => 'Сухие смеси',
                'description' => 'Цемент, штукатурка, шпаклёвка, плиточный клей',
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Цемент и бетон',
                        'description' => 'Портландцемент, ЦПС, бетонные смеси',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Штукатурка и шпаклёвка',
                        'description' => 'Гипсовые и цементные смеси для стен',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Клей плиточный',
                        'description' => 'Клей для керамической плитки и керамогранита',
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'name' => 'Отделочные материалы',
                'description' => 'Краски, обои, плитка и декор',
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Краски и грунтовки',
                        'description' => 'Водоэмульсионные и эмалевые краски, грунты',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Обои',
                        'description' => 'Виниловые, флизелиновые, бумажные обои',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Плитка и керамогранит',
                        'description' => 'Настенная и напольная плитка',
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'name' => 'Сантехника',
                'description' => 'Трубы, фитинги, смесители, сантехнические приборы',
                'is_active' => true,
            ],
            [
                'name' => 'Электрика',
                'description' => 'Кабель, розетки, выключатели, лампы',
                'is_active' => true,
            ],
            [
                'name' => 'Напольные покрытия',
                'description' => 'Ламинат, линолеум, паркетная доска',
                'is_active' => true,
            ],
            [
                'name' => 'Гипсокартон и профиль',
                'description' => 'ГКЛ, профили, подвесы и комплектующие',
                'is_active' => true,
            ],
            [
                'name' => 'Изоляция',
                'description' => 'Тепло-, звуко- и гидроизоляция',
                'is_active' => true,
            ],
            [
                'name' => 'Двери и окна',
                'description' => 'Межкомнатные двери, окна и фурнитура',
                'is_active' => true,
            ],
            [
                'name' => 'Спецодежда и СИЗ',
                'description' => 'Рабочая одежда, перчатки, каски, защита',
                'is_active' => true,
            ],
            [
                'name' => 'Сад и участок',
                'description' => 'Грунт, тротуарная плитка, садовый инвентарь',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $categoryData) {
            $this->createCategory($categoryData);
        }
    }

    private function createCategory(array $data, ?int $parentId = null): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $data['parent_id'] = $parentId;

        $category = Category::create($data);

        foreach ($children as $childData) {
            $this->createCategory($childData, $category->id);
        }
    }
}
