<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::query();

        // Optional filters
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('property_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('stage') && $request->stage !== 'All') {
            $query->where('onboarding_stage', $request->stage);
        }

        $properties = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_name'     => ['required', 'string', 'max:255'],
            'property_type'     => ['nullable', 'string', 'max:100'],
            'star_rating'       => ['nullable', 'string', 'max:10'],

            'contact_person'    => ['required', 'string', 'max:255'],
            'phone'             => ['required', 'string', 'max:50'],
            'email'             => ['required', 'email', 'max:255'],

            'country'           => ['nullable', 'string', 'max:100'],
            'city'              => ['required', 'string', 'max:100'],
            'address'           => ['nullable', 'string', 'max:255'],

            'onboarding_stage'  => ['nullable', 'string', 'max:100'],
            'ota_status'        => ['nullable', 'string', 'max:100'],
            'seo_status'        => ['nullable', 'string', 'max:100'],

            'services'          => ['nullable'], // JSON string or array
            'notes'             => ['nullable', 'string'],

            'logo'              => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Handle services array from FormData (can come as JSON string)
        if ($request->has('services')) {
            if (is_string($request->services)) {
                $decoded = json_decode($request->services, true);
                $validated['services'] = is_array($decoded) ? $decoded : [];
            } else {
                $validated['services'] = $request->services;
            }
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('properties/logos', 'public');
        }

        $property = Property::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Property created successfully',
            'data'    => $property,
        ], 201);
    }

    public function show(Property $property)
    {
        return response()->json([
            'success' => true,
            'data' => $property,
        ]);
    }

    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'property_name'     => ['sometimes', 'required', 'string', 'max:255'],
            'property_type'     => ['nullable', 'string', 'max:100'],
            'star_rating'       => ['nullable', 'string', 'max:10'],

            'contact_person'    => ['sometimes', 'required', 'string', 'max:255'],
            'phone'             => ['sometimes', 'required', 'string', 'max:50'],
            'email'             => ['sometimes', 'required', 'email', 'max:255'],

            'country'           => ['nullable', 'string', 'max:100'],
            'city'              => ['sometimes', 'required', 'string', 'max:100'],
            'address'           => ['nullable', 'string', 'max:255'],

            'onboarding_stage'  => ['nullable', 'string', 'max:100'],
            'ota_status'        => ['nullable', 'string', 'max:100'],
            'seo_status'        => ['nullable', 'string', 'max:100'],

            'services'          => ['nullable'],
            'notes'             => ['nullable', 'string'],

            'logo'              => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo'       => ['nullable', 'boolean'],
        ]);

        // Handle services array from FormData (can come as JSON string)
        if ($request->has('services')) {
            if (is_string($request->services)) {
                $decoded = json_decode($request->services, true);
                $validated['services'] = is_array($decoded) ? $decoded : [];
            } else {
                $validated['services'] = $request->services;
            }
        }

        // Remove old logo if requested
        if ($request->boolean('remove_logo') && $property->logo) {
            Storage::disk('public')->delete($property->logo);
            $validated['logo'] = null;
        }

        // Upload new logo (replace old)
        if ($request->hasFile('logo')) {
            if ($property->logo) {
                Storage::disk('public')->delete($property->logo);
            }

            $validated['logo'] = $request->file('logo')->store('properties/logos', 'public');
        }

        $property->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Property updated successfully',
            'data'    => $property->fresh(),
        ]);
    }

    public function destroy(Property $property)
    {
        if ($property->logo) {
            Storage::disk('public')->delete($property->logo);
        }

        $property->delete();

        return response()->json([
            'success' => true,
            'message' => 'Property deleted successfully',
        ]);
    }
}