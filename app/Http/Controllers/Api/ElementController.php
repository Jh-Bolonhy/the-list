<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Element;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ElementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Element::query()->where('user_id', Auth::id());
        
        // Filter by archived status if provided
        if ($request->has('archived')) {
            $archived = $request->boolean('archived');
            $query->where('archived', $archived);
        }
        // If no archived parameter, return all elements (for 'both' view)
        
        $elements = $query->orderBy('created_at', 'desc')->get();
        return response()->json($elements);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean'
        ]);

        $element = Element::create([
            ...$request->all(),
            'user_id' => Auth::id()
        ]);
        return response()->json($element, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $element = Element::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($element);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'sometimes|boolean',
            'parent_element_id' => 'nullable|exists:elements,id'
        ]);

        $element = Element::where('user_id', Auth::id())->findOrFail($id);
        
        // Prevent element from being its own parent
        if ($request->has('parent_element_id') && $request->input('parent_element_id') == $id) {
            return response()->json(['error' => 'Element cannot be its own parent'], 400);
        }
        
        // Ensure parent_element_id belongs to the same user if provided
        if ($request->has('parent_element_id') && $request->input('parent_element_id')) {
            $parent = Element::where('user_id', Auth::id())
                ->find($request->input('parent_element_id'));
            if (!$parent) {
                return response()->json(['error' => 'Parent element not found or does not belong to you'], 400);
            }
        }
        
        $element->update($request->all());
        return response()->json($element);
    }

    /**
     * Archive the specified resource and all its descendants.
     */
    public function archive(string $id): JsonResponse
    {
        $element = Element::where('user_id', Auth::id())->findOrFail($id);
        $element->archiveWithDescendants();
        return response()->json(['message' => 'Element and all descendants archived successfully'], 200);
    }

    /**
     * Restore an archived element and all its descendants.
     */
    public function restore(string $id): JsonResponse
    {
        $element = Element::where('user_id', Auth::id())->findOrFail($id);
        
        if (!$element->archived) {
            return response()->json(['message' => 'Element is not archived'], 400);
        }
        
        $element->restoreWithDescendants();
        return response()->json(['message' => 'Element and all descendants restored successfully'], 200);
    }

    /**
     * Permanently delete an element (only if already archived).
     */
    public function forceDelete(string $id): JsonResponse
    {
        $element = Element::where('user_id', Auth::id())->findOrFail($id);
        
        if (!$element->archived) {
            return response()->json(['message' => 'Element must be archived before permanent deletion'], 400);
        }
        
        $element->delete();
        return response()->json(['message' => 'Element permanently deleted'], 200);
    }
}

