<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    private const MAX_IMAGES = 3;
    private const MAX_IMAGE_KB = 10240;

    private array $roomsColumnCache = [];
    private array $roomImagesColumnCache = [];

    public function index(): JsonResponse
    {
        $query = Room::with(['images' => function ($q) {
            if ($this->hasRoomImagesColumn('sort_order')) {
                $q->orderBy('sort_order');
            }
            $q->orderBy('id');
        }]);

        if ($this->hasRoomsColumn('sort_order')) {
            $query->orderBy('sort_order');
        }

        $query->orderByDesc('id');

        $rooms = $query->get();

        return response()->json([
            'success' => true,
            'data' => $rooms->map(fn (Room $room) => $this->transformRoom($room))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // ✅ nullable property_id allowed
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            'images' => ['required', 'array', 'min:1', 'max:' . self::MAX_IMAGES],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_IMAGE_KB],
        ], [
            'images.max' => 'One room can have maximum ' . self::MAX_IMAGES . ' images.',
            'images.*.max' => 'Each image must not be greater than 10MB.',
        ]);

        // ✅ No forced property lookup anymore
        // If property_id is missing, it will stay null (after DB column is nullable)

        $room = DB::transaction(function () use ($request, $validated) {
            $payload = $this->buildRoomPayloadForSave($validated, null);

            $room = Room::create($payload);

            foreach ($request->file('images', []) as $index => $file) {
                $path = $file->store('rooms', 'public');

                $imageData = [
                    'image_path' => $path,
                ];

                if ($this->hasRoomImagesColumn('sort_order')) {
                    $imageData['sort_order'] = $index;
                }

                $room->images()->create($imageData);
            }

            return $room->load(['images' => function ($q) {
                if ($this->hasRoomImagesColumn('sort_order')) {
                    $q->orderBy('sort_order');
                }
                $q->orderBy('id');
            }]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Room created successfully.',
            'data' => $this->transformRoom($room),
        ], 201);
    }

    public function show(Room $room): JsonResponse
    {
        $room->load(['images' => function ($q) {
            if ($this->hasRoomImagesColumn('sort_order')) {
                $q->orderBy('sort_order');
            }
            $q->orderBy('id');
        }]);

        return response()->json([
            'success' => true,
            'data' => $this->transformRoom($room),
        ]);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            'images' => ['nullable', 'array', 'max:' . self::MAX_IMAGES],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_IMAGE_KB],
        ], [
            'images.max' => 'One room can have maximum ' . self::MAX_IMAGES . ' images.',
            'images.*.max' => 'Each image must not be greater than 10MB.',
        ]);

        DB::transaction(function () use ($request, $validated, $room) {
            $payload = $this->buildRoomPayloadForSave($validated, $room);

            if (!empty($payload)) {
                $room->update($payload);
            }

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

                    $imageData = [
                        'image_path' => $path,
                    ];

                    if ($this->hasRoomImagesColumn('sort_order')) {
                        $imageData['sort_order'] = $index;
                    }

                    $room->images()->create($imageData);
                }
            }
        });

        $room->load(['images' => function ($q) {
            if ($this->hasRoomImagesColumn('sort_order')) {
                $q->orderBy('sort_order');
            }
            $q->orderBy('id');
        }]);

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully.',
            'data' => $this->transformRoom($room),
        ]);
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->load('images');

        DB::transaction(function () use ($room) {
            foreach ($room->images as $img) {
                if ($img->image_path && Storage::disk('public')->exists($img->image_path)) {
                    Storage::disk('public')->delete($img->image_path);
                }
            }

            $room->images()->delete();
            $room->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully.',
        ]);
    }

    private function transformRoom(Room $room): array
    {
        $nameCol = $this->getRoomNameColumn();
        $descCol = $this->getRoomDescriptionColumn();

        return [
            'id' => $room->id,
            'property_id' => $this->hasRoomsColumn('property_id') ? $room->property_id : null,
            'name' => (string) ($room->{$nameCol} ?? ''),
            'description' => (string) ($room->{$descCol} ?? ''),
            'is_active' => $this->hasRoomsColumn('is_active') ? (bool) $room->is_active : true,
            'sort_order' => $this->hasRoomsColumn('sort_order') ? (int) ($room->sort_order ?? 0) : 0,
            'created_at' => $room->created_at,
            'updated_at' => $room->updated_at,
            'images' => $room->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'image_path' => $img->image_path,
                    'image_url' => $img->image_path ? Storage::url($img->image_path) : null,
                    'sort_order' => $this->hasRoomImagesColumn('sort_order') ? (int) ($img->sort_order ?? 0) : 0,
                    'created_at' => $img->created_at,
                    'updated_at' => $img->updated_at,
                ];
            })->values(),
        ];
    }

    private function buildRoomPayloadForSave(array $validated, ?Room $room = null): array
    {
        $payload = [];

        $nameColumn = $this->getRoomNameColumn();
        $descColumn = $this->getRoomDescriptionColumn();

        if (array_key_exists('name', $validated)) {
            $payload[$nameColumn] = $validated['name'];
        }

        if (array_key_exists('description', $validated)) {
            $payload[$descColumn] = $validated['description'];
        }

        // ✅ property_id can be null now
        if ($this->hasRoomsColumn('property_id') && array_key_exists('property_id', $validated)) {
            $payload['property_id'] = $validated['property_id'] ? (int) $validated['property_id'] : null;
        }

        if ($this->hasRoomsColumn('is_active')) {
            if (array_key_exists('is_active', $validated)) {
                $payload['is_active'] = (bool) $validated['is_active'];
            } elseif (!$room) {
                $payload['is_active'] = true;
            }
        }

        if ($this->hasRoomsColumn('sort_order')) {
            if (array_key_exists('sort_order', $validated)) {
                $payload['sort_order'] = (int) $validated['sort_order'];
            } elseif (!$room) {
                $payload['sort_order'] = 0;
            }
        }

        return $payload;
    }

    private function getRoomNameColumn(): string
    {
        if ($this->hasRoomsColumn('name')) return 'name';
        if ($this->hasRoomsColumn('room_name')) return 'room_name';
        if ($this->hasRoomsColumn('title')) return 'title';

        throw new \RuntimeException('Rooms table is missing a name column (expected: name or room_name or title).');
    }

    private function getRoomDescriptionColumn(): string
    {
        if ($this->hasRoomsColumn('description')) return 'description';
        if ($this->hasRoomsColumn('room_description')) return 'room_description';
        if ($this->hasRoomsColumn('details')) return 'details';

        throw new \RuntimeException('Rooms table is missing a description column (expected: description or room_description or details).');
    }

    private function hasRoomsColumn(string $column): bool
    {
        if (!array_key_exists($column, $this->roomsColumnCache)) {
            $this->roomsColumnCache[$column] = Schema::hasColumn('rooms', $column);
        }

        return $this->roomsColumnCache[$column];
    }

    private function hasRoomImagesColumn(string $column): bool
    {
        if (!array_key_exists($column, $this->roomImagesColumnCache)) {
            $this->roomImagesColumnCache[$column] = Schema::hasColumn('room_images', $column);
        }

        return $this->roomImagesColumnCache[$column];
    }
}