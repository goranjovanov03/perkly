<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\RateLimiter;
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
        $user = $request->user();
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'onboarded_at' => $user->onboarded_at,
            ],
        ]);
    }

    public function organizations(Request $request)
    {
        $organizations = $request->user()
            ->memberships()
            ->where('status', 'active')
            ->with('organization')
            ->get()
            ->map(function ($membership) {
                return [
                    'id' => $membership->organization->id,
                    'name' => $membership->organization->name,
                    'slug' => $membership->organization->slug,
                ];
            });

        return response()->json(['organizations' => $organizations]);
    }

    public function completeOnboarding(Request $request)
    {
        $user = $request->user();
        $user->onboarded_at = now();
        $user->save();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'onboarded_at' => $user->onboarded_at,
            ],
        ]);
    }

    public function logoutAll(Request $request)
    {
        $user = $request->user();

        PersonalAccessToken::where('tokenable_id', $user->id)->delete();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = response()->json(['message' => 'Logged out from all devices']);
        $response->withCookie(cookie('perkly-session', '', -3600));

        return $response;
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $key = 'forgot-password:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => [
                    'code' => 'TOO_MANY_REQUESTS',
                    'message' => 'Too many requests. Please try again in ' . $seconds . ' seconds.',
                    'request_id' => $request->header('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid(),
                ],
            ], 429);
        }

        RateLimiter::hit($key, 900);

        $status = Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'If the email exists, a reset link has been sent.'])->setStatusCode(202);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                PersonalAccessToken::where('tokenable_id', $user->id)->delete();

                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password has been reset.']);
        }

        $requestId = $request->header('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid();
        return response()->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Invalid or expired reset token.',
                'request_id' => $requestId,
                'fields' => [
                    'token' => ['This password reset token is invalid or has expired.'],
                ],
            ],
        ], 422);
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