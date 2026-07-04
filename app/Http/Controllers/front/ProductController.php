<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;

class ProductController extends Controller
{
    public function getProducts(Request $request)
    {
        $products = Product::orderBy('created_at', 'DESC')
                    ->where('status', 1);

        if (!empty($request->category)) {
            $catArray = explode(',', $request->category);
            $products = $products->whereIn('category_id', $catArray);
        }

        if (!empty($request->brand)) {
            $brandArray = explode(',', $request->brand);
            $products = $products->whereIn('brand_id', $brandArray);
        }

        $products = $products->get();

        return response()->json([
            'status' => 200,
            'data' => $products
        ], 200);
    }

    public function latestProducts()
    {
        $products = Product::orderBy('created_at', 'DESC')
                    ->where('status', 1)
                    ->limit(8)
                    ->get();

        return response()->json([
            'status' => 200,
            'data' => $products
        ], 200);
    }

    public function featuredProducts()
    {
        $products = Product::orderBy('created_at', 'DESC')
                    ->where('status', 1)
                    ->where('is_featured', 'yes')
                    ->limit(8)
                    ->get();

        return response()->json([
            'status' => 200,
            'data' => $products
        ], 200);
    }

    public function getCategories()
    {
        $categories = Category::orderBy('created_at', 'DESC')
            ->where('status', 1)
            ->get();

        return response()->json([
            'status' => 200,
            'data' => $categories
        ], 200);
    }

    public function getbrands()
    {
        $brands = Brand::orderBy('created_at', 'DESC')
            ->where('status', 1)
            ->get();

        return response()->json([
            'status' => 200,
            'data' => $brands
        ], 200);
    }

   public function getProduct($id)
    {
        $product = Product::with(['product_images', 'sizes'])->find($id);

        if ($product == null) {
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => $product
        ], 200);
    }
}
