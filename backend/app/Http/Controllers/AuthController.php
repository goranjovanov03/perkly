<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\AuthenticationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::guard('web')->attempt($request->only('email', 'password'))) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $request->session()->regenerate();

        $response = response()->json([
            'user' => [
                'id' => Auth::guard('web')->user()->id,
                'name' => Auth::guard('web')->user()->name,
                'email' => Auth::guard('web')->user()->email,
            ],
        ]);

        if (app()->environment('testing')) {
            $response->withCookie(cookie(config('session.cookie'), $request->session()->getId(), config('session.lifetime')));
        }

        return $response;
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = response()->json(['message' => 'Logged out']);
        $response->withCookie(cookie('perkly-session', '', -3600));

        return $response;
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

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            $requestId = $request->header('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid();
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The current password is incorrect.',
                    'request_id' => $requestId,
                    'fields' => [
                        'current_password' => ['The current password is incorrect.'],
                    ],
                ],
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        PersonalAccessToken::where('tokenable_id', $user->id)->delete();

        $currentSessionId = $request->session()->getId();
        \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return response()->json(['message' => 'Password updated successfully']);
    }
}