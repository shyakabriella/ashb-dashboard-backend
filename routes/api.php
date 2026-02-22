<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\Admin\UserAdminController;
use App\Http\Controllers\API\HomeSectionOneController;
use App\Http\Controllers\API\RoomController;
use App\Http\Controllers\API\PropertyController; 

Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});

// ✅ Protected routes
Route::middleware('auth:sanctum')->group(function () {

    Route::get('me', function (\Illuminate\Http\Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    });

    Route::post('logout', function (\Illuminate\Http\Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    });

    Route::get('admin/users', [UserAdminController::class, 'index']);

    Route::get('admin/property/home-section-one/current', [HomeSectionOneController::class, 'current']);
    Route::apiResource('admin/property/home-section-one', HomeSectionOneController::class)
        ->names('home-section-one');

    // ✅ Properties (now works)
    Route::apiResource('admin/property/properties', PropertyController::class)
        ->names('admin-property-properties');

    // ✅ Alias (optional)
    Route::apiResource('properties', PropertyController::class)
        ->names('properties');

    Route::apiResource('admin/property/rooms', RoomController::class)
        ->names('admin-property-rooms');

    Route::apiResource('rooms', RoomController::class)
        ->names('rooms');
});

// ✅ Public route
Route::get('home/section-one', [HomeSectionOneController::class, 'showPublic']);

// ✅ Public Rooms route
Route::get('home/rooms', [RoomController::class, 'index']);