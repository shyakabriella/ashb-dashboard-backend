<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Mail\UserCredentialsMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RegisterController extends BaseController
{
    /**
     * POST /api/register
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'role'  => 'nullable|string|max:50',
        ], [
            'name.required'  => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email'    => 'Email format is invalid.',
            'email.unique'   => 'This email is already used.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $input = $request->only(['name', 'email', 'phone', 'role']);

        // default role
        if (empty($input['role'])) {
            $input['role'] = 'waiters';
        }

        // normalize phone (remove spaces)
        if (!empty($input['phone'])) {
            $input['phone'] = preg_replace('/\s+/', '', $input['phone']);
        }

        // ✅ password = RC + 5 digits
        $randomDigits = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $plainPassword = 'RC' . $randomDigits; // e.g. RC48291
        $input['password'] = Hash::make($plainPassword);

        // create user
        $user = User::create($input);

        // username = phone else email
        $username = !empty($user->phone) ? $user->phone : $user->email;

        // ✅ send email with credentials (do not break register if mail fails)
        try {
            Mail::to($user->email)->send(
                new UserCredentialsMail($user->name, $username, $plainPassword, $user->role)
            );
        } catch (\Throwable $e) {
            // Optional: log error if you want
            // \Log::warning('User credential email failed: '.$e->getMessage());
        }

        $token = $user->createToken('MyApp')->plainTextToken;

        $success = [
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'phone' => $user->phone ?? null,
            ],
        ];

        return $this->sendResponse($success, 'User registered successfully. Credentials emailed.');
    }

    /**
     * POST /api/login
     * Accepts:
     * - { login: "email or phone", password: "..." }
     * - { email: "...", password: "..." }
     * - { phone: "...", password: "..." }
     */
    public function login(Request $request): JsonResponse
    {
        $login = $request->input('login')
            ?? $request->input('email')
            ?? $request->input('phone');

        $validator = Validator::make(
            [
                'login'    => $login,
                'password' => $request->input('password'),
            ],
            [
                'login'    => ['required', 'string', 'max:255'],
                'password' => ['required', 'string'],
            ],
            [
                'login.required'    => 'Email or phone is required.',
                'password.required' => 'Password is required.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $login = trim((string) $login);
        $password = (string) $request->input('password');

        // normalize spaces for phone
        $loginNoSpaces = preg_replace('/\s+/', '', $login);

        // detect email or phone
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

        /** @var \App\Models\User|null $user */
        $user = $isEmail
            ? User::where('email', $login)->first()
            : User::where('phone', $loginNoSpaces)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised.',
                'errors'  => ['error' => ['Invalid credentials']],
            ], 401);
        }

        // Optional: remove old tokens if you want single-session login
        // $user->tokens()->delete();

        $token = $user->createToken('MyApp')->plainTextToken;

        $success = [
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'phone' => $user->phone ?? null,
            ],
        ];

        return $this->sendResponse($success, 'User login successfully.');
    }

    /**
     * POST /api/logout  (auth:sanctum)
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return $this->sendResponse([], 'Logged out successfully.');
    }
}