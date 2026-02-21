<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\HomeAbout;
use Illuminate\Http\Request;

class HomeAboutController extends Controller
{
    /**
     * GET /api/home/about
     * Public: latest active record
     */
    public function showPublic()
    {
        $row = HomeAbout::where('is_active', true)->latest()->first();

        return response()->json([
            'success' => true,
            'data' => $row,
        ]);
    }

    /**
     * GET /api/admin/property/home-about
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => HomeAbout::latest()->get(),
        ]);
    }

    /**
     * ✅ REQUIRED by apiResource
     * GET /api/admin/property/home-about/{home_about}
     */
    public function show(HomeAbout $home_about)
    {
        return response()->json([
            'success' => true,
            'data' => $home_about,
        ]);
    }

    /**
     * POST /api/admin/property/home-about
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],

            'mission_title' => ['nullable','string','max:255'],
            'mission_text' => ['nullable','string'],

            'vision_title' => ['nullable','string','max:255'],
            'vision_text' => ['nullable','string'],

            'is_active' => ['nullable','boolean'],
        ]);

        $row = HomeAbout::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,

            'mission_title' => $validated['mission_title'] ?? 'Our Mission',
            'mission_text' => $validated['mission_text'] ?? null,

            'vision_title' => $validated['vision_title'] ?? 'Our Vision',
            'vision_text' => $validated['vision_text'] ?? null,

            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Home About created.',
            'data' => $row,
        ], 201);
    }

    /**
     * PUT/PATCH /api/admin/property/home-about/{home_about}
     */
    public function update(Request $request, HomeAbout $home_about)
    {
        $validated = $request->validate([
            'title' => ['sometimes','required','string','max:255'],
            'description' => ['nullable','string'],

            'mission_title' => ['nullable','string','max:255'],
            'mission_text' => ['nullable','string'],

            'vision_title' => ['nullable','string','max:255'],
            'vision_text' => ['nullable','string'],

            'is_active' => ['nullable','boolean'],
        ]);

        if ($request->has('title')) $home_about->title = $validated['title'];
        if ($request->has('description')) $home_about->description = $validated['description'];

        if ($request->has('mission_title')) $home_about->mission_title = $validated['mission_title'];
        if ($request->has('mission_text')) $home_about->mission_text = $validated['mission_text'];

        if ($request->has('vision_title')) $home_about->vision_title = $validated['vision_title'];
        if ($request->has('vision_text')) $home_about->vision_text = $validated['vision_text'];

        if ($request->has('is_active')) $home_about->is_active = (bool) $validated['is_active'];

        $home_about->save();

        return response()->json([
            'success' => true,
            'message' => 'Home About updated.',
            'data' => $home_about,
        ]);
    }

    /**
     * DELETE /api/admin/property/home-about/{home_about}
     */
    public function destroy(HomeAbout $home_about)
    {
        $home_about->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted.',
        ]);
    }
}