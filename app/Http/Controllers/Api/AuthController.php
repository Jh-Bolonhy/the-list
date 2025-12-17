<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Element;
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
            'locale' => 'nullable|string|in:en,ru', // Optional locale for headline generation
        ]);

        // Generate default headline based on locale (default to English)
        $locale = $request->input('locale', 'en');
        $defaultHeadline = $locale === 'ru' 
            ? "Список {$request->name}"
            : "The {$request->name}'s List";

        $user = User::create([
            'name' => $request->name,
            'headline' => $defaultHeadline,
            'locale' => $locale, // Save locale from request or default to 'en'
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
            'csrf_token' => csrf_token()
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
                $request->session()->regenerate();

                return response()->json([
                    'message' => 'Login successful',
                    'user' => new UserResource(Auth::user()),
                    'csrf_token' => csrf_token()
                ]);
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
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
        return response()->json([
            'user' => Auth::check() ? new UserResource(Auth::user()) : null,
            'csrf_token' => csrf_token(),
        ]);
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
     * Update headline for authenticated user
     */
    public function updateHeadline(Request $request): JsonResponse
    {
        $request->validate([
            'headline' => 'nullable|string|max:255', // Frontend handles width limitation
        ]);

        $user = Auth::user();
        $user->headline = $request->input('headline');
        $user->save();

        return response()->json([
            'message' => 'Headline updated successfully',
            'user' => new UserResource($user),
        ]);
    }
    
    /**
     * Update locale for authenticated user
     */
    public function updateLocale(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|in:en,ru',
        ]);

        $user = Auth::user();
        $user->locale = $request->input('locale');
        $user->save();

        return response()->json([
            'message' => 'Locale updated successfully',
            'user' => new UserResource($user),
        ]);
    }
    
    /**
     * Update show_mode for authenticated user
     */
    public function updateShowMode(Request $request): JsonResponse
    {
        $request->validate([
            'show_mode' => 'required|string|in:active,archived,both',
        ]);

        $user = Auth::user();
        $user->show_mode = $request->input('show_mode');
        $user->save();

        return response()->json([
            'message' => 'Show mode updated successfully',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Update locked_element_id for authenticated user
     * Only one lock per user; null means "unlocked"
     */
    public function updateLockedElement(Request $request): JsonResponse
    {
        $request->validate([
            'locked_element_id' => 'nullable|integer',
        ]);

        $user = Auth::user();
        $lockedElementId = $request->input('locked_element_id');

        if ($lockedElementId !== null) {
            // Ensure element exists and belongs to current user
            $element = Element::where('user_id', $user->id)->find($lockedElementId);
            if (!$element) {
                return response()->json(['message' => 'Element not found'], 404);
            }

            // Ensure it's a "parent tab" (has at least one child)
            $hasChildren = Element::where('user_id', $user->id)
                ->where('parent_element_id', $lockedElementId)
                ->exists();
            if (!$hasChildren) {
                return response()->json(['message' => 'Lock can be enabled only for elements with children'], 400);
            }
        }

        $user->locked_element_id = $lockedElementId;
        $user->save();

        return response()->json([
            'message' => 'Locked element updated successfully',
            'user' => new UserResource($user),
        ]);
    }
}

