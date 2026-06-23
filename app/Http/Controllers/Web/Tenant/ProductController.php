<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'name'         => ['required', 'string', 'max:255'],
            'product_code' => ['nullable', 'string', 'max:50'],
            'description'  => ['nullable', 'string'],
            'rate'         => ['required', 'numeric', 'min:0'],
            'tax_percent'  => ['required', 'numeric', 'min:0', 'max:100'],
            'hsn'          => ['nullable', 'string', 'max:50'],
            'unit'         => ['nullable', 'string', 'max:50'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $data['tenant_id'] = $this->tenantId();
        $data['is_active'] = $request->boolean('is_active', true);

        Product::create($data);

        return redirect()->route('tenant.products.index')
            ->with('success', 'Product added successfully.');
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $product = $this->findProduct($id);

        return view('tenant.products.edit', compact('product'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $product = $this->findProduct($id);

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'product_code' => ['nullable', 'string', 'max:50'],
            'description'  => ['nullable', 'string'],
            'rate'         => ['required', 'numeric', 'min:0'],
            'tax_percent'  => ['required', 'numeric', 'min:0', 'max:100'],
            'hsn'          => ['nullable', 'string', 'max:50'],
            'unit'         => ['nullable', 'string', 'max:50'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $product->update($data);

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
