<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'property_name',
        'property_type',
        'star_rating',
        'logo',
        'contact_person',
        'phone',
        'email',
        'country',
        'city',
        'address',
        'onboarding_stage',
        'ota_status',
        'seo_status',
        'services',
        'notes',
    ];

    protected $casts = [
        'services' => 'array',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) return null;
        return asset('storage/' . $this->logo);
    }

    // ✅ One property has many rooms
    public function rooms()
    {
        return $this->hasMany(Room::class)->orderBy('sort_order');
    }
}