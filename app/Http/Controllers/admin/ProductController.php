<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Models\ProductImage;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\Product;
use App\Models\TempImage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('created_at', 'DESC')->get();

        return response()->json([
            'status' => 200,
            'data' => $products
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required',
            'price'       => 'required|numeric',
            'category'    => 'required|integer',
            'sku'         => 'required|unique:products,sku',
            'is_featured' => 'required',
            'status'      => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->errors()
            ], 400);
        }

        $product = new Product();
        $product->title             = $request->title;
        $product->price             = $request->price;
        $product->compare_price     = $request->compare_price;
        $product->category_id       = $request->category;
        $product->brand_id          = $request->brand;
        $product->sku               = $request->sku;
        $product->qty               = $request->qty;
        $product->description       = $request->description;
        $product->short_description = $request->short_description;
        $product->barcode           = $request->barcode;
        $product->status            = $request->status;
        $product->is_featured       = $request->is_featured;
        $product->save();

        // Save sizes (sizes[] = size IDs from DB)
        if (!empty($request->sizes)) {
            foreach ($request->sizes as $sizeId) {
                $productSize = new ProductSize();
                $productSize->product_id = $product->id;
                $productSize->size_id    = $sizeId;
                $productSize->save();
            }
        }

        // Save gallery images
        if (!empty($request->gallery)) {
            if (!file_exists(public_path('uploads/products/large'))) {
                mkdir(public_path('uploads/products/large'), 0777, true);
            }
            if (!file_exists(public_path('uploads/products/small'))) {
                mkdir(public_path('uploads/products/small'), 0777, true);
            }

            $manager = new ImageManager(new Driver());

            foreach ($request->gallery as $key => $tempImageId) {
                $tempImage = TempImage::find($tempImageId);
                if (!$tempImage) continue;

                $sourcePath = public_path('uploads/temp/' . $tempImage->name);
                if (!file_exists($sourcePath)) continue;

                $extArray  = explode('.', $tempImage->name);
                $ext       = end($extArray);
                $imageName = $product->id . '_' . time() . '_' . $key . '.' . $ext;

                try {
                    $img = $manager->read($sourcePath);
                    $img->resize(1200, 1200);
                    $img->save(public_path('uploads/products/large/' . $imageName));

                    $img = $manager->read($sourcePath);
                    $img->cover(400, 460);
                    $img->save(public_path('uploads/products/small/' . $imageName));

                    // First image = main product image
                    if ($key == 0) {
                        $product->image = $imageName;
                        $product->save();
                    }

                    // Save in product_images table
                    $productImage             = new ProductImage();
                    $productImage->product_id = $product->id;
                    $productImage->image      = $imageName;
                    $productImage->save();

                } catch (\Exception $e) {
                    return response()->json([
                        'status'  => 500,
                        'message' => 'Image Processing Failed',
                        'error'   => $e->getMessage()
                    ], 500);
                }
            }
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Product created successfully',
        ]);
    }

    public function show($id)
    {
        $product = Product::with(['sizes', 'product_images'])->find($id);

        if ($product === null) {
            return response()->json([
                'status'  => 404,
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data'   => $product
        ], 200);
    }

    public function update($id, Request $request)
    {
        $product = Product::find($id);

        if ($product === null) {
            return response()->json([
                'status'  => 404,
                'message' => 'Product not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'required',
            'price'       => 'required|numeric',
            'category'    => 'required|integer',
            'sku'         => 'required|unique:products,sku,' . $id . ',id',
            'is_featured' => 'required',
            'status'      => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->errors()
            ], 400);
        }

        $product->title             = $request->title;
        $product->price             = $request->price;
        $product->compare_price     = $request->compare_price;
        $product->category_id       = $request->category;
        $product->brand_id          = $request->brand;
        $product->sku               = $request->sku;
        $product->qty               = $request->qty;
        $product->description       = $request->description;
        $product->short_description = $request->short_description;
        $product->barcode           = $request->barcode;
        $product->status            = $request->status;
        $product->is_featured       = $request->is_featured;
        $product->save();

        // Update sizes - delete old, add new
        ProductSize::where('product_id', $id)->delete();
        if (!empty($request->sizes)) {
            foreach ($request->sizes as $sizeId) {
                $productSize             = new ProductSize();
                $productSize->product_id = $product->id;
                $productSize->size_id    = $sizeId;
                $productSize->save();
            }
        }

        // Save new gallery images
        if (!empty($request->input('gallery'))) {
            if (!file_exists(public_path('uploads/products/large'))) {
                mkdir(public_path('uploads/products/large'), 0777, true);
            }
            if (!file_exists(public_path('uploads/products/small'))) {
                mkdir(public_path('uploads/products/small'), 0777, true);
            }

            try {
                $manager = new ImageManager(new Driver());

                foreach ($request->input('gallery') as $key => $tempImageId) {
                    $tempImage = TempImage::find($tempImageId);
                    if (!$tempImage) continue;

                    $sourcePath = public_path('uploads/temp/' . $tempImage->name);
                    if (!file_exists($sourcePath)) continue;

                    $extArray  = explode('.', $tempImage->name);
                    $ext       = end($extArray);
                    $imageName = $product->id . '_' . time() . '_' . $key . '.' . $ext;

                    $img = $manager->read($sourcePath);
                    $img->resize(1200, 1200);
                    $img->save(public_path('uploads/products/large/' . $imageName));

                    $img = $manager->read($sourcePath);
                    $img->cover(400, 460);
                    $img->save(public_path('uploads/products/small/' . $imageName));

                    if ($key == 0) {
                        $product->image = $imageName;
                        $product->save();
                    }

                    // Save in product_images table
                    $productImage             = new ProductImage();
                    $productImage->product_id = $product->id;
                    $productImage->image      = $imageName;
                    $productImage->save();
                }
            } catch (\Exception $e) {
                return response()->json([
                    'status'  => 500,
                    'message' => 'Image Processing Failed',
                    'error'   => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile()
                ], 500);
            }
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Product updated successfully',
        ], 200);
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if ($product === null) {
            return response()->json([
                'status'  => 404,
                'message' => 'Product not found',
            ], 404);
        }

        // Delete product images from disk and DB
        $productImages = ProductImage::where('product_id', $id)->get();
        foreach ($productImages as $img) {
            File::delete(public_path('uploads/products/large/' . $img->image));
            File::delete(public_path('uploads/products/small/' . $img->image));
            $img->delete();
        }

        // Delete sizes pivot
        ProductSize::where('product_id', $id)->delete();

        $product->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Product deleted successfully',
        ], 200);
    }

    public function deleteProductImage($id)
    {
        $productImage = ProductImage::find($id);

        if ($productImage === null) {
            return response()->json([
                'status'  => 404,
                'message' => 'Product image not found',
            ], 404);
        }

        File::delete(public_path('uploads/products/large/' . $productImage->image));
        File::delete(public_path('uploads/products/small/' . $productImage->image));
        $productImage->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Product image deleted successfully',
        ], 200);
    }
}
