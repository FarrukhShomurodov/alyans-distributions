<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\ProductsExport;
use App\Http\Requests\ProductRequest;
use App\Imports\ProductsImport;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\ProductSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProductController
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): View
    {
        $categoryId = $request->query('category_id');
        $search = trim((string) $request->query('search', ''));
        $photoFilter = $request->query('photo'); // '' | 'with' | 'without'

        $baseQuery = Product::query()->select([
            'id', 'name', 'slug', 'is_active', 'is_top', 'price', 'unit', 'brand', 'category_id', 'external_id',
        ])
            ->when($search !== '', function ($query) use ($search) {
                ProductSearch::apply($query, $search);
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($photoFilter === 'with', function ($query) {
                $query->whereHas('images');
            })
            ->when($photoFilter === 'without', function ($query) {
                $query->whereDoesntHave('images');
            });

        $products = (clone $baseQuery)
            ->with(['category:id,name'])
            ->withCount('images')
            ->orderByDesc('id')
            ->paginate(50)
            ->appends($request->query());

        $categories = Category::query()->select(['id', 'name'])->orderBy('name')->get();

        $stats = [
            'total_products' => (clone $baseQuery)->count(),
            'total_categories' => Category::count(),
            'avg_price' => (clone $baseQuery)->avg('price'),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
        ];

        return view('admin.products.index', compact('products', 'stats', 'categories', 'categoryId', 'search', 'photoFilter'));
    }

    public function create(): View
    {
        $categories = Category::query()->select(['id', 'name'])->where('is_active', true)->get();
        $attributes = Attribute::query()->with('values')->orderBy('id')->get();

        return view('admin.products.create', compact('categories', 'attributes'));
    }

    public function show(Product $product): View
    {
        return view('admin.products.show', compact('product'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        try {
            $this->service->create((array) $request->validated());
        } catch (QueryException $e) {
            Log::warning('Product store DB error: ' . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', $this->humanizeDbError($e, 'Не удалось создать товар.'));
        } catch (Throwable $e) {
            Log::error('Product store failed: ' . $e->getMessage(), ['ex' => $e]);

            return back()
                ->withInput()
                ->with('error', 'Ошибка при создании товара: ' . $e->getMessage());
        }

        $backFilters = (array) $request->input('_back', []);

        return redirect()
            ->route('products.index', $backFilters)
            ->with('success', 'Товар успешно добавлен');
    }

    public function edit(Product $product): View
    {
        $categories = Category::query()->select(['id', 'name'])->where('is_active', true)->get();
        $attributes = Attribute::query()->with('values')->orderBy('id')->get();
        $product->load('attributes');

        return view('admin.products.edit', compact('product', 'categories', 'attributes'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->service->update($product, (array) $request->validated());
        } catch (QueryException $e) {
            Log::warning("Product update DB error (id={$product->id}): " . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', $this->humanizeDbError($e, 'Не удалось сохранить изменения.'));
        } catch (Throwable $e) {
            Log::error("Product update failed (id={$product->id}): " . $e->getMessage(), ['ex' => $e]);

            return back()
                ->withInput()
                ->with('error', 'Ошибка при сохранении товара: ' . $e->getMessage());
        }

        $backFilters = (array) $request->input('_back', []);

        return redirect()
            ->route('products.index', $backFilters)
            ->with('success', 'Товар успешно обновлён');
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->service->delete($product);
        } catch (QueryException $e) {
            Log::warning("Product destroy DB error (id={$product->id}): " . $e->getMessage());

            // Скорее всего, foreign key constraint: товар используется в заказах
            if (str_contains(strtolower($e->getMessage()), 'foreign key')
                || str_contains($e->getMessage(), '23503')
            ) {
                return back()->with(
                    'error',
                    "Нельзя удалить товар «{$product->name}» — он используется в заказах или корзинах. " .
                    "Можно сделать его неактивным в настройках."
                );
            }

            return back()->with('error', $this->humanizeDbError($e, 'Не удалось удалить товар.'));
        } catch (Throwable $e) {
            Log::error("Product destroy failed (id={$product->id}): " . $e->getMessage(), ['ex' => $e]);

            return back()->with('error', 'Ошибка при удалении товара: ' . $e->getMessage());
        }

        return redirect()->route('products.index')->with('success', 'Товар успешно удалён');
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new ProductsExport, 'products.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new ProductsImport();
            Excel::import($import, $request->file('file'));
        } catch (Throwable $e) {
            Log::error('Product import failed: ' . $e->getMessage(), ['ex' => $e]);

            return redirect()->route('products.index')
                ->with('error', 'Ошибка при импорте: ' . $e->getMessage());
        }

        $message = "Импорт завершен: добавлено {$import->getImportedCount()}, обновлено {$import->getUpdatedCount()} товаров.";

        $skipped = $import->getSkippedRows();
        if (!empty($skipped)) {
            $message .= " Пропущено (категория не найдена): " . implode(', ', $skipped);
        }

        return redirect()->route('products.index')->with('success', $message);
    }

    public function importTemplate()
    {
        $path = storage_path('app/private/product_import_template.xlsx');
        if (!file_exists($path)) {
            return back()->with('error', 'Шаблон не найден.');
        }
        return response()->download($path, 'шаблон_импорта_товаров.xlsx');
    }

    /**
     * Превращает сырую ошибку PostgreSQL в читаемое сообщение для пользователя.
     */
    private function humanizeDbError(QueryException $e, string $fallback): string
    {
        $msg = $e->getMessage();
        $code = $e->getCode();

        // Нарушение уникальности (UNIQUE constraint)
        if (str_contains($msg, '23505') || str_contains(strtolower($msg), 'duplicate')) {
            if (str_contains($msg, 'external_id')) {
                return 'Товар с таким артикулом (external_id) уже существует. Укажите другой.';
            }
            if (str_contains($msg, 'slug')) {
                return 'Товар с таким названием/slug уже есть. Измените название.';
            }
            return 'Такая запись уже существует (нарушение уникальности).';
        }

        // Check constraint violation
        if (str_contains($msg, '23514')) {
            return 'Недопустимое значение одного из полей.';
        }

        // Not null / foreign key
        if (str_contains($msg, '23502')) {
            return 'Обязательное поле не заполнено.';
        }
        if (str_contains($msg, '23503')) {
            return 'Ссылка на несуществующую запись (например, удалённая категория).';
        }

        // Неверный формат данных (например, ожидался int, пришла строка)
        if (str_contains($msg, '22P02')) {
            return 'Неверный формат одного из полей. Проверьте числовые значения.';
        }

        // Длина превышена
        if (str_contains($msg, '22001')) {
            return 'Одно из полей слишком длинное.';
        }

        return $fallback . ' (код ошибки: ' . $code . ')';
    }
}
