<?php

namespace App\Models\DesignModel\GenerateImageModel;

use Illuminate\Database\Eloquent\Model;

class GenerateImageModel extends Model
{
    protected $table = 'generate_images';

    protected $fillable = [
        'prompt', 'style', 'aspect_ratio', 'image_url', 'user_id'
    ];
}
