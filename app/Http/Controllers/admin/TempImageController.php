<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\TempImage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class TempImageController extends Controller
{
    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 400,
                    'errors' => $validator->errors()
                ], 400);
            }

            // Create folders if not exist
            if (!file_exists(public_path('uploads/temp'))) {
                mkdir(public_path('uploads/temp'), 0777, true);
            }

            if (!file_exists(public_path('uploads/temp/thumb'))) {
                mkdir(public_path('uploads/temp/thumb'), 0777, true);
            }

            // Save DB record
            $tempImage = new TempImage();
            $tempImage->name = 'Dummy Name';
            $tempImage->save();

            // Upload image
            $image = $request->file('image');

            $imageName = time() . '.' . $image->extension();

            $image->move(public_path('uploads/temp'), $imageName);

            // Update DB
            $tempImage->name = $imageName;
            $tempImage->save();

            // Create thumbnail
            $manager = new ImageManager(new Driver());

            $img = $manager->read(
                public_path('uploads/temp/' . $imageName)
            );

            $img->cover(400, 450);

            $img->save(
                public_path('uploads/temp/thumb/' . $imageName)
            );

            return response()->json([
                'status' => 200,
                'message' => 'Image uploaded successfully',
                'data' => $tempImage
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }
}