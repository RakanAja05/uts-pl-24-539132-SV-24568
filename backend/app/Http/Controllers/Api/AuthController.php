<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Login validation failed', [
                'who' => [
                    'user_id' => 'guest',
                    'ip' => $request->ip(),
                ],
                'what' => [
                    'action' => 'auth_login',
                    'endpoint' => $request->path(),
                ],
                'why' => [
                    'reason' => 'Validation error',
                    'failed_fields' => array_keys($validator->errors()->toArray()),
                ],
            ]);

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $user = User::where('email', $data['email'])->first();

        // Security: NEVER log passwords, tokens, or plain credentials
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            Log::warning('Authentication failed: Invalid credentials provided', [
                'who' => [
                    'user_id' => 'guest',
                    'ip' => $request->ip(),
                ],
                'what' => [
                    'action' => 'auth_login',
                    'endpoint' => $request->path(),
                ],
                'why' => [
                    'reason' => 'Invalid email or password mismatch',
                ],
            ]);

            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        Log::info('User authenticated successfully', [
            'who' => [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ],
            'what' => [
                'action' => 'auth_login',
                'endpoint' => $request->path(),
            ],
            'why' => [
                'reason' => 'Valid credentials provided, token issued',
            ],
        ]);

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : 'guest';

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        Log::info('User logged out successfully', [
            'who' => [
                'user_id' => $userId,
                'ip' => $request->ip(),
            ],
            'what' => [
                'action' => 'auth_logout',
                'endpoint' => $request->path(),
            ],
            'why' => [
                'reason' => 'Access token revoked',
            ],
        ]);

        return response()->json(['message' => 'Logout berhasil']);
    }
}
