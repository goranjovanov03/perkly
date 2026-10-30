<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;

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
}