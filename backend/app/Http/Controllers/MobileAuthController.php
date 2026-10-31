<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\AuthenticationException;
use App\Models\User;

class MobileAuthController extends Controller
{
    public function issueToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            $requestId = $request->header('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid();
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Invalid credentials.',
                    'request_id' => $requestId,
                ],
            ], 401);
        }

        $passwordCheck = Hash::check($request->password, $user->password);

        if (! $passwordCheck) {
            $requestId = $request->header('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid();
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Invalid credentials.',
                    'request_id' => $requestId,
                ],
            ], 401);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function revokeToken(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        
        if ($token) {
            \Illuminate\Support\Facades\Log::info('Revoking token', ['token_id' => $token->id]);
            $token->delete();
            
            // Verify deletion
            $deleted = \Laravel\Sanctum\PersonalAccessToken::find($token->id);
            \Illuminate\Support\Facades\Log::info('Token deletion verified', ['found' => $deleted ? true : false]);
            
            \Illuminate\Support\Facades\Log::info('Token deleted', ['token_id' => $token->id]);
        } else {
            \Illuminate\Support\Facades\Log::warning('No current access token found');
        }

        return response()->json(['message' => 'Token revoked']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }
}