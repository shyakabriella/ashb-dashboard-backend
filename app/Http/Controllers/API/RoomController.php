<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    /**
     * Max images per room
     */
    private const MAX_IMAGES = 3;

    /**
     * Max image size in KB (10MB)
     */
    private const MAX_IMAGE_KB = 10240;

    /**
     * GET /api/admin/property/rooms
     * GET /api/home/rooms
     */
    public function index(): JsonResponse
    {
        $rooms = Room::with(['images' => function ($q) {
                $q->orderBy('sort_order')->orderBy('id');
            }])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rooms->map(fn (Room $room) => $this->transformRoom($room)),
        ]);
    }

    /**
     * POST /api/admin/property/rooms
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            // images[] (1 to 3 files)
            'images' => ['required', 'array', 'min:1', 'max:' . self::MAX_IMAGES],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_IMAGE_KB],
        ], [
            'images.max' => 'One room can have maximum ' . self::MAX_IMAGES . ' images.',
            'images.*.max' => 'Each image must not be greater than 10MB.',
        ]);

        $room = DB::transaction(function () use ($request, $validated) {
            $room = Room::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'is_active' => $validated['is_active'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            foreach ($request->file('images', []) as $index => $file) {
                $path = $file->store('rooms', 'public');

                $room->images()->create([
                    'image_path' => $path,
                    'sort_order' => $index,
                ]);
            }

            return $room->load(['images' => function ($q) {
                $q->orderBy('sort_order')->orderBy('id');
            }]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Room created successfully.',
            'data' => $this->transformRoom($room),
        ], 201);
    }

    /**
     * GET /api/admin/property/rooms/{room}
     */
    public function show(Room $room): JsonResponse
    {
        $room->load(['images' => function ($q) {
            $q->orderBy('sort_order')->orderBy('id');
        }]);

        return response()->json([
            'success' => true,
            'data' => $this->transformRoom($room),
        ]);
    }

    /**
     * PUT/PATCH /api/admin/property/rooms/{room}
     */
    public function update(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            // optional on update, but if sent => max 3
            'images' => ['nullable', 'array', 'max:' . self::MAX_IMAGES],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_IMAGE_KB],
        ], [
            'images.max' => 'One room can have maximum ' . self::MAX_IMAGES . ' images.',
            'images.*.max' => 'Each image must not be greater than 10MB.',
        ]);

        DB::transaction(function () use ($request, $validated, $room) {
            $room->update([
                'name' => $validated['name'] ?? $room->name,
                'description' => $validated['description'] ?? $room->description,
                'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $room->is_active,
                'sort_order' => $validated['sort_order'] ?? $room->sort_order,
            ]);

            // If new images uploaded, replace old images
            if ($request->hasFile('images')) {
                $room->load('images');

                foreach ($room->images as $img) {
                    if ($img->image_path && Storage::disk('public')->exists($img->image_path)) {
                        Storage::disk('public')->delete($img->image_path);
                    }
                }

                $room->images()->delete();

                foreach ($request->file('images', []) as $index => $file) {
                    $path = $file->store('rooms', 'public');

                    $room->images()->create([
                        'image_path' => $path,
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        $room->load(['images' => function ($q) {
            $q->orderBy('sort_order')->orderBy('id');
        }]);

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully.',
            'data' => $this->transformRoom($room),
        ]);
    }

    /**
     * DELETE /api/admin/property/rooms/{room}
     */
    public function destroy(Room $room): JsonResponse
    {
        $room->load('images');

        DB::transaction(function () use ($room) {
            foreach ($room->images as $img) {
                if ($img->image_path && Storage::disk('public')->exists($img->image_path)) {
                    Storage::disk('public')->delete($img->image_path);
                }
            }

            // If no DB cascade exists, this ensures cleanup
            $room->images()->delete();
            $room->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully.',
        ]);
    }

    /**
     * Format room for frontend (adds image_url)
     */
    private function transformRoom(Room $room): array
    {
        return [
            'id' => $room->id,
            'name' => $room->name,
            'description' => $room->description,
            'is_active' => (bool) $room->is_active,
            'sort_order' => (int) ($room->sort_order ?? 0),
            'created_at' => $room->created_at,
            'updated_at' => $room->updated_at,
            'images' => $room->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'image_path' => $img->image_path,
                    'image_url' => $img->image_path ? Storage::url($img->image_path) : null, // /storage/rooms/xxx.jpg
                    'sort_order' => (int) ($img->sort_order ?? 0),
                    'created_at' => $img->created_at,
                    'updated_at' => $img->updated_at,
                ];
            })->values(),
        ];
    }
}