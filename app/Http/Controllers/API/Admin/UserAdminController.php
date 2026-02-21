<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        // only admin/manager can view
        if (!$me || !in_array($me->role, ['admin', 'manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $limit = (int) $request->query('limit', 4);
        $limit = max(1, min($limit, 50));

        $users = User::query()
            ->select('id', 'name', 'email', 'role', 'phone', 'created_at')
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}