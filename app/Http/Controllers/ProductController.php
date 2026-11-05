<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\MaterialCategory;
use App\Models\MaterialProduct;
use App\Models\ProductImage;
use App\Services\AuditService;
use App\Services\ListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $f = $r->validate(['q' => 'nullable|string|max:100', 'city' => 'nullable|string|max:80', 'brand' => 'nullable|string|max:100', 'category' => 'nullable|integer', 'sort' => 'nullable|in:newest,price,total,stock,brand']);
        $q = MaterialProduct::published()->with('category', 'user.location', 'images')->when($f['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', '%'.$v.'%'))->when($f['city'] ?? null, fn ($q, $v) => $q->where('city', 'like', '%'.$v.'%'))->when($f['brand'] ?? null, fn ($q, $v) => $q->where('brand', 'like', '%'.$v.'%'))->when($f['category'] ?? null, fn ($q, $v) => $q->where('material_category_id', $v));
        switch ($f['sort'] ?? 'newest') {
            case 'price':$q->orderBy('price');
                break;
            case 'total':$q->orderByRaw('shipping_cost IS NULL ASC')->orderByRaw('(price + COALESCE(shipping_cost,0)) ASC');
                break;
            case 'stock':$q->orderByDesc('stock');
                break;
            case 'brand':$q->orderBy('brand');
                break;
            default:$q->latest();
        }

        return view('products.index', ['products' => $q->paginate(12)->withQueryString(), 'categories' => MaterialCategory::all()]);
    }

    public function create()
    {
        return view('products.form', ['product' => new MaterialProduct, 'categories' => MaterialCategory::all()]);
    }

    public function store(ProductRequest $r, ListingService $s)
    {
        $p = $s->saveProduct($r->validated());

        return redirect()->route('products.show', $p)->with('success', 'آگهی ذخیره شد. انتشار عمومی پس از تأیید مدیر انجام می‌شود.');
    }

    public function edit(MaterialProduct $product)
    {
        Gate::authorize('update', $product);

        return view('products.form', ['product' => $product, 'categories' => MaterialCategory::all()]);
    }

    public function update(ProductRequest $r, MaterialProduct $product, ListingService $s)
    {
        Gate::authorize('update', $product);
        $s->saveProduct($r->validated(), $product);

        return redirect()->route('products.show', $product)->with('success', 'قیمت و موجودی به‌روز شد؛ آگهی برای بررسی مجدد ارسال شد.');
    }

    public function show(MaterialProduct $product)
    {
        Gate::authorize('view', $product);
        $product->load('category', 'images', 'user.location');

        return view('products.show', compact('product'));
    }

    public function deactivate(MaterialProduct $product)
    {
        Gate::authorize('update', $product);
        DB::transaction(function () use ($product) {
            $product->update(['status' => 'inactive']);
            app(AuditService::class)->record('product.deactivated', $product);
        });

        return back()->with('success', 'آگهی غیرفعال شد.');
    }

    public function compare(Request $r)
    {
        $d = $r->validate(['ids' => 'required|array|min:2|max:4', 'ids.*' => 'required|integer|distinct|exists:material_products,id']);
        $products = MaterialProduct::published()->with('user.location', 'images', 'category')->whereIn('id', $d['ids'])->get();
        abort_unless($products->count() === count($d['ids']), 404);

        return view('products.compare', compact('products'));
    }

    public function deleteImage(MaterialProduct $product, ProductImage $image)
    {
        Gate::authorize('update', $product);
        $path = DB::transaction(function () use ($product, $image) {
            $p = MaterialProduct::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $img = $p->images()->whereKey($image->id)->firstOrFail();
            $path = $img->path;
            $img->delete();
            app(AuditService::class)->record('product.image_deleted', $p, ['image_id' => $image->id]);

            return $path;
        });
        if (preg_match('~^products/[a-f0-9-]{36}\.webp$~D', $path)) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'تصویر حذف شد.');
    }
}
