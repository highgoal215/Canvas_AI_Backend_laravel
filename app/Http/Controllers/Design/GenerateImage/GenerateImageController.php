<?php

namespace App\Http\Controllers\Design\GenerateImage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignModel\GenerateImageModel\GenerateImageModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    
        // Map aspect_ratio to valid DALL-E 3 sizes
        $sizeMap = [
            '1:1' => '1024x1024',
            '16:9' => '1792x1024',
            '9:16' => '1024x1792', // Common portrait aspect ratio
            '4:3' => '1024x1024', // Fallback to square for similar ratios
            '3:4' => '1024x1792', // Portrait
        ];
    
        $size = $sizeMap[$aspect_ratio] ?? '1024x1024';
    
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => 'dall-e-3',
                'prompt' => "$prompt, in a $style style", // Refined prompt
                'n' => 1,
                'size' => $size,
                'quality' => 'standard', // Specify quality for clarity
            ]);
    
        if (!$response->successful()) {
            // Log the entire response for debugging
            Log::error('OpenAI API request failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            // Return a more detailed error to the client
            return response()->json([
                'error' => 'Failed to generate image from API.',
                'details' => $response->json() // The actual error from OpenAI
            ], $response->status());
        }
        
        $imageUrl = $response->json('data.0.url');
    
        $image = GenerateImageModel::create([
            'prompt' => $prompt,
            'style' => $style,
            'aspect_ratio' => $aspect_ratio,
            'image_url' => $imageUrl,
            'user_id' => $request->user()->id,
        ]);
    
        return response()->json(['image' => $image], 201);
    }
}
