<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSectionOne extends Model
{
    protected $fillable = [
        'image',
        'title',
        'subtitle',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}