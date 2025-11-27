<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'header_name' => $request->name, // Initialize with name
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'header_name' => $user->header_name, // Always return header_name, even if null
                'email' => $user->email,
            ],
            'csrf_token' => csrf_token()
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name,
                    'header_name' => Auth::user()->header_name, // Always return header_name, even if null
                    'email' => Auth::user()->email,
                ],
                'csrf_token' => csrf_token()
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout successful',
            'csrf_token' => csrf_token()
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        $response = [];
        
        if (Auth::check()) {
            $response['user'] = [
                'id' => Auth::id(),
                'name' => Auth::user()->name,
                'header_name' => Auth::user()->header_name, // Always return header_name, even if null
                'email' => Auth::user()->email,
            ];
        } else {
            $response['user'] = null;
        }
        
        // Always include CSRF token
        $response['csrf_token'] = csrf_token();
        
        return response()->json($response);
    }
    
    /**
     * Get CSRF token
     */
    public function csrfToken(): JsonResponse
    {
        return response()->json([
            'csrf_token' => csrf_token()
        ]);
    }
    
    /**
     * Update header name for authenticated user
     */
    public function updateHeaderName(Request $request): JsonResponse
    {
        $request->validate([
            'header_name' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $user->header_name = $request->input('header_name');
        $user->save();

        return response()->json([
            'message' => 'Header name updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'header_name' => $user->header_name, // Always return header_name, even if null
                'email' => $user->email,
            ]
        ]);
    }
}

