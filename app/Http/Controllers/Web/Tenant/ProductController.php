<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterialItem;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findProduct(int|string $id): Product
    {
        return Product::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Product::where('tenant_id', $this->tenantId())->latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $products = $query->paginate(20)->withQueryString();

        return view('tenant.products.index', compact('products'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        return view('tenant.products.create');
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'product_code'      => ['nullable', 'string', 'max:50'],
            'description'       => ['nullable', 'string'],
            'rate'              => ['required', 'numeric', 'min:0'],
            'tax_percent'       => ['required', 'numeric', 'min:0', 'max:100'],
            'hsn'               => ['nullable', 'string', 'max:50'],
            'unit'              => ['nullable', 'string', 'max:50'],
            'is_active'         => ['nullable', 'boolean'],
            'type'              => ['nullable', 'in:finished_good,raw_material'],
            'current_stock'     => ['nullable', 'numeric', 'min:0'],
            'reorder_level'     => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['tenant_id'] = $this->tenantId();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['type']      = $data['type'] ?? 'finished_good';

        Product::create($data);

        return redirect()->route('tenant.products.index')
            ->with('success', 'Product added successfully.');
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $product = $this->findProduct($id);

        $rawMaterials = [];
        $bomItems     = collect();

        if ($product->type === 'finished_good') {
            $rawMaterials = Product::where('tenant_id', $this->tenantId())
                ->rawMaterial()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'product_code', 'unit']);

            $bomItems = $product->billOfMaterials()->with('material')->get();
        }

        return view('tenant.products.edit', compact('product', 'rawMaterials', 'bomItems'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $product = $this->findProduct($id);

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'product_code'      => ['nullable', 'string', 'max:50'],
            'description'       => ['nullable', 'string'],
            'rate'              => ['required', 'numeric', 'min:0'],
            'tax_percent'       => ['required', 'numeric', 'min:0', 'max:100'],
            'hsn'               => ['nullable', 'string', 'max:50'],
            'unit'              => ['nullable', 'string', 'max:50'],
            'is_active'         => ['nullable', 'boolean'],
            'type'              => ['nullable', 'in:finished_good,raw_material'],
            'current_stock'     => ['nullable', 'numeric', 'min:0'],
            'reorder_level'     => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity'  => ['nullable', 'numeric', 'min:0'],
            'materials'                       => ['nullable', 'array'],
            'materials.*.material_id'         => ['nullable', 'integer', 'exists:products,id'],
            'materials.*.quantity_per_unit'   => ['nullable', 'numeric', 'min:0.0001'],
        ]);

        $materials = $data['materials'] ?? null;
        unset($data['materials']);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['type']      = $data['type'] ?? 'finished_good';

        DB::transaction(function () use ($product, $data, $materials) {
            $product->update($data);

            if ($product->type === 'finished_good' && is_array($materials)) {
                $keepIds = [];

                foreach ($materials as $row) {
                    if (empty($row['material_id']) || empty($row['quantity_per_unit'])) {
                        continue;
                    }

                    $bomRow = BillOfMaterialItem::updateOrCreate(
                        [
                            'tenant_id'   => $product->tenant_id,
                            'product_id'  => $product->id,
                            'material_id' => $row['material_id'],
                        ],
                        [
                            'quantity_per_unit' => $row['quantity_per_unit'],
                        ]
                    );

                    $keepIds[] = $bomRow->id;
                }

                BillOfMaterialItem::where('product_id', $product->id)
                    ->whereNotIn('id', $keepIds)
                    ->delete();
            }
        });

        return redirect()->route('tenant.products.index')
            ->with('success', 'Product updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $this->findProduct($id)->delete();

        return redirect()->route('tenant.products.index')
            ->with('success', 'Product deleted.');
    }

    // ── Low Stock ─────────────────────────────────────────────────
    public function lowStock(): View
    {
        $products = StockService::lowStockProducts($this->tenantId());

        $suggestions = $products
            ->filter(fn ($p) => $p->type === 'finished_good')
            ->mapWithKeys(fn ($p) => [$p->id => StockService::suggestedMaterialsFor($p)]);

        return view('tenant.products.low-stock', compact('products', 'suggestions'));
    }

    // ── Batches — traceability list for one product ─────────────────
    public function batches(int|string $id): View
    {
        $product = $this->findProduct($id);

        $batches = $product->batches()
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->orderByDesc('received_at')
            ->get();

        return view('tenant.products.batches', compact('product', 'batches'));
    }

    // ── JSON search (used by invoice/quotation item rows) ─────────
    public function search(Request $request): JsonResponse
    {
        $products = Product::where('tenant_id', $this->tenantId())
            ->active()
            ->when($request->filled('q'), fn($q) => $q->where(function ($sub) use ($request) {
                $sub->where('name', 'like', "%{$request->q}%")
                    ->orWhere('product_code', 'like', "%{$request->q}%");
            }))
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'product_code', 'name', 'description', 'rate', 'tax_percent', 'hsn', 'unit']);

        return response()->json($products);
    }
}
