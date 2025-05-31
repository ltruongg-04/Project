<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Facades\Log;


class ShopController extends Controller
{
    public function index(Request $request)
    {
        $size = $request->query('size', 12);
        $order = $request->query('order', -1);
        $f_brands = $request->query('brands', '');
        $f_categories = $request->query('categories', '');
        $min_price = $request->query('min', 1);
        $max_price = $request->query('max', 500);

        // Thiết lập cột và thứ tự sắp xếp
        $order_options = [
            1 => ['created_at', 'DESC'],
            2 => ['created_at', 'ASC'],
            3 => ['sale_price', 'ASC'],
            4 => ['sale_price', 'DESC'],
            -1 => ['id', 'DESC']
        ];
        [$o_column, $o_order] = $order_options[$order] ?? ['id', 'DESC'];

        // Cache brands và categories
        $brands = Cache::remember('shop_brands', 60, function () {
            dump('Cache miss - truy vấn lấy dữ liệu brands từ database.');
            return Brand::orderBy('name', 'ASC')->get();
        });

        $categories = Cache::remember('shop_categories', 3600, function () {
            return Category::orderBy('name', 'ASC')->get();
        });

        // Tạo cache key duy nhất dựa trên filter
        $cacheKey = 'products_' . md5(json_encode([
            'brands' => $f_brands,
            'categories' => $f_categories,
            'min' => $min_price,
            'max' => $max_price,
            'order' => $order,
            'size' => $size,
            'page' => $request->query('page', 1)
        ]));

        $products = Cache::remember($cacheKey, 300, function () use (
            $f_brands, $f_categories, $min_price, $max_price, $o_column, $o_order, $size
        ) {
            return Product::where(function ($query) use ($f_brands) {
                    $query->whereIn('brand_id', explode(',', $f_brands))
                          ->orWhereRaw("'" . $f_brands . "'=''");
                })
                ->where(function ($query) use ($f_categories) {
                    $query->whereIn('category_id', explode(',', $f_categories))
                          ->orWhereRaw("'" . $f_categories . "'=''");
                })
                ->where(function ($query) use ($min_price, $max_price) {
                    $query->whereBetween('regular_price', [$min_price, $max_price])
                          ->orWhereBetween('sale_price', [$min_price, $max_price]);
                })
                ->orderBy($o_column, $o_order)
                ->paginate($size);
        });

        return view('shop', compact(
            'products', 'categories', 'size', 'order',
            'brands', 'f_brands', 'f_categories', 'min_price', 'max_price'
        ));
    }

    public function product_details($product_slug)
    {
        // Cache chi tiết sản phẩm
        $product = Cache::remember("product_{$product_slug}", 3600, function () use ($product_slug) {
            return Product::where('slug', $product_slug)->first();
        });

        // Cache sản phẩm liên quan
        $rproducts = Cache::remember("related_{$product_slug}", 3600, function () use ($product_slug) {
            return Product::where('slug', '<>', $product_slug)->take(8)->get();
        });

        return view('details', compact('product', 'rproducts'));
    }
}
