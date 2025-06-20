<?php

namespace App\Http\Controllers\Design\GenerateImage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignModel\GenerateImageModel\GenerateImage;
use Illuminate\Support\Facades\Http;

class GenerateImageController extends Controller
{
    public function generateImage(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
            'style' => 'required|string',
            'aspect_ratio' => 'required|string',
        ]);
    
        $prompt = $request->input('prompt');
        $style = $request->input('style');
        $aspect_ratio = $request->input('aspect_ratio');
    
        // Map aspect_ratio to valid OpenAI size
        $sizeMap = [
            '1:1' => '512x512',
            '4:3' => '512x384',
            '16:9' => '1024x576', // Note: This is not a valid OpenAI size, so fallback to closest valid size
        ];
    
        // Use valid sizes only: fallback to '512x512' if not recognized
        $size = $sizeMap[$aspect_ratio] ?? '512x512';
    
        // Since OpenAI only supports 256x256, 512x512, 1024x1024, adjust accordingly:
        if (!in_array($size, ['256x256', '512x512', '1024x1024'])) {
            $size = '512x512'; // fallback to a valid size
        }
    
        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => 'dall-e-3', // specify the model
                'prompt' => "$prompt, $style",
                'n' => 1,
                'size' => $size,
            ]);
    
        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to generate image'], 500);
        }
    
        $imageUrl = $response->json('data.0.url');
    
        $image = GenerateImage::create([
            'prompt' => $prompt,
            'style' => $style,
            'aspect_ratio' => $aspect_ratio,
            'image_url' => $imageUrl,
            'user_id' => $request->user()->id,
        ]);
    
        return response()->json(['image' => $image], 201);
    }
}
