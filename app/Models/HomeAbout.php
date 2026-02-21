<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeAbout extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'mission_title',
        'mission_text',
        'vision_title',
        'vision_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}