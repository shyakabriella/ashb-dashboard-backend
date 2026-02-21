<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\HomeSectionOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomeSectionOneController extends Controller
{
    // ✅ helper: attach image_url every time
    private function withImageUrl(?HomeSectionOne $row)
    {
        if (!$row) return null;
        $row->image_url = $row->image ? asset('storage/' . $row->image) : null;
        return $row;
    }

    /**
     * GET /api/home/section-one
     * Public: return the active section (latest).
     */
    public function showPublic()
    {
        $row = HomeSectionOne::where('is_active', true)->latest()->first();
        $row = $this->withImageUrl($row);

        return response()->json([
            'success' => true,
            'data' => $row,
        ]);
    }

    /**
     * GET /api/admin/property/home-section-one
     * Resource index: list all rows
     */
    public function index()
    {
        $rows = HomeSectionOne::latest()->get();

        // add image_url for each row
        $rows->transform(function ($r) {
            return $this->withImageUrl($r);
        });

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    /**
     * ✅ REQUIRED by apiResource
     * GET /api/admin/property/home-section-one/{home_section_one}
     */
    public function show(HomeSectionOne $home_section_one)
    {
        $row = $this->withImageUrl($home_section_one);

        return response()->json([
            'success' => true,
            'data' => $row,
        ]);
    }

    /**
     * ✅ IMPORTANT: always returns a row (creates one if table empty)
     * GET /api/admin/property/home-section-one/current
     */
    public function current()
    {
        $row = HomeSectionOne::latest()->first();

        if (!$row) {
            $row = HomeSectionOne::create([
                'title' => '',
                'subtitle' => '',
                'is_active' => true,
                'image' => null,
            ]);
        }

        $row = $this->withImageUrl($row);

        return response()->json([
            'success' => true,
            'data' => $row,
        ]);
    }

    /**
     * POST /api/admin/property/home-section-one
     * Resource store
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required','string','max:255'],
            'subtitle' => ['nullable','string','max:255'],
            'is_active' => ['nullable','boolean'],
            'image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
        ]);

        $path = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $name = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('home/section1', $name, 'public');
        }

        $row = HomeSectionOne::create([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'image' => $path,
        ]);

        $row = $this->withImageUrl($row);

        return response()->json([
            'success' => true,
            'message' => 'Home Section One created.',
            'data' => $row,
        ], 201);
    }

    /**
     * PUT/PATCH /api/admin/property/home-section-one/{home_section_one}
     * Resource update
     */
    public function update(Request $request, HomeSectionOne $home_section_one)
    {
        $validated = $request->validate([
            'title' => ['sometimes','required','string','max:255'],
            'subtitle' => ['nullable','string','max:255'],
            'is_active' => ['nullable','boolean'],
            'image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'remove_image' => ['nullable','boolean'],
        ]);

        // remove image
        if (($validated['remove_image'] ?? false) && $home_section_one->image) {
            Storage::disk('public')->delete($home_section_one->image);
            $home_section_one->image = null;
        }

        // replace image
        if ($request->hasFile('image')) {
            if ($home_section_one->image) Storage::disk('public')->delete($home_section_one->image);

            $file = $request->file('image');
            $name = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $home_section_one->image = $file->storeAs('home/section1', $name, 'public');
        }

        if ($request->has('title')) $home_section_one->title = $validated['title'];
        if ($request->has('subtitle')) $home_section_one->subtitle = $validated['subtitle'];
        if ($request->has('is_active')) $home_section_one->is_active = (bool) $validated['is_active'];

        $home_section_one->save();

        $row = $this->withImageUrl($home_section_one);

        return response()->json([
            'success' => true,
            'message' => 'Home Section One updated.',
            'data' => $row,
        ]);
    }

    /**
     * DELETE /api/admin/property/home-section-one/{home_section_one}
     * Resource destroy
     */
    public function destroy(HomeSectionOne $home_section_one)
    {
        if ($home_section_one->image) {
            Storage::disk('public')->delete($home_section_one->image);
        }

        $home_section_one->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted.',
        ]);
    }
}