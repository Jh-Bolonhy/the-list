<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Element;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $elements = $query->orderBy('parent_element_id', 'asc')
                          ->orderBy('order', 'asc')
                          ->orderBy('created_at', 'asc')
                          ->get();
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
            'completed' => 'boolean',
            'parent_element_id' => 'nullable|exists:elements,id'
        ]);

        // Get the maximum order for elements with the same parent_element_id
        $parentId = $request->input('parent_element_id');
        $maxOrder = Element::where('user_id', Auth::id())
            ->where('parent_element_id', $parentId)
            ->max('order') ?? 0;

        $element = Element::create([
            ...$request->all(),
            'user_id' => Auth::id(),
            'order' => $maxOrder + 1
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

    /**
     * Reorder elements in batch
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'updates' => 'required|array|min:1',
            'updates.*.id' => 'required|exists:elements,id',
            'updates.*.order' => 'required|integer|min:1',
            'parent_element_id' => 'nullable|exists:elements,id'
        ]);

        $parentId = $request->input('parent_element_id');
        $updates = $request->input('updates');
        $userId = Auth::id();

        // Verify all elements belong to the user and have the same parent_element_id
        $elementIds = array_column($updates, 'id');
        $elements = Element::where('user_id', $userId)
            ->whereIn('id', $elementIds)
            ->get();

        if ($elements->count() !== count($elementIds)) {
            return response()->json(['error' => 'Some elements not found or do not belong to you'], 400);
        }

        // Verify all elements have the same parent_element_id as requested
        foreach ($elements as $element) {
            if ($element->parent_element_id != $parentId) {
                return response()->json(['error' => 'All elements must have the same parent_element_id'], 400);
            }
        }

        // Verify order values are unique
        $orders = array_column($updates, 'order');
        if (count($orders) !== count(array_unique($orders))) {
            return response()->json(['error' => 'Order values must be unique'], 400);
        }

        // Update all elements in a transaction
        DB::beginTransaction();
        try {
            foreach ($updates as $update) {
                Element::where('id', $update['id'])
                    ->where('user_id', $userId)
                    ->update(['order' => $update['order']]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to reorder elements'], 500);
        }

        // Return updated elements
        $updatedElements = Element::where('user_id', $userId)
            ->whereIn('id', $elementIds)
            ->orderBy('order', 'asc')
            ->get();

        return response()->json([
            'message' => 'Elements reordered successfully',
            'elements' => $updatedElements
        ]);
    }

    /**
     * Move element to new position and parent atomically
     * This method handles both parent change and reordering in a single transaction
     */
    public function move(Request $request): JsonResponse
    {
        $request->validate([
            'element_id' => 'required|exists:elements,id',
            'new_parent_id' => 'nullable|exists:elements,id',
            'target_order' => 'nullable|integer|min:1'
        ]);

        $elementId = $request->input('element_id');
        $newParentId = $request->input('new_parent_id');
        $targetOrder = $request->input('target_order');
        $userId = Auth::id();

        // Get the element to move
        $element = Element::where('user_id', $userId)->findOrFail($elementId);

        // Prevent element from being its own parent
        if ($newParentId == $elementId) {
            return response()->json(['error' => 'Element cannot be its own parent'], 400);
        }

        // Verify new parent belongs to the same user if provided
        if ($newParentId) {
            $parent = Element::where('user_id', $userId)->find($newParentId);
            if (!$parent) {
                return response()->json(['error' => 'Parent element not found or does not belong to you'], 400);
            }
        }

        $oldParentId = $element->parent_element_id;

        // Start transaction for atomic update
        DB::beginTransaction();
        try {
            // Update parent_element_id if changed
            if ($oldParentId != $newParentId) {
                $element->parent_element_id = $newParentId;
                $element->save();
            }

            // Get all elements in the new parent group (after moving)
            $newGroupElements = Element::where('user_id', $userId)
                ->where('parent_element_id', $newParentId)
                ->where('id', '!=', $elementId) // Exclude the moved element temporarily
                ->orderBy('order', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            // Determine target order
            if ($targetOrder === null) {
                // Move to end
                $targetOrder = $newGroupElements->count() + 1;
            } else {
                // Ensure target order is within valid range
                $targetOrder = max(1, min($targetOrder, $newGroupElements->count() + 1));
            }

            // Recalculate orders for new group
            $order = 1;
            $movedElementOrdered = false;
            $affectedElementIds = [$elementId];

            foreach ($newGroupElements as $groupElement) {
                if ($order === $targetOrder && !$movedElementOrdered) {
                    // Insert moved element here
                    $element->order = $order;
                    $element->save();
                    $movedElementOrdered = true;
                    $order++;
                }
                if ($groupElement->order != $order) {
                    $groupElement->order = $order;
                    $groupElement->save();
                    $affectedElementIds[] = $groupElement->id;
                }
                $order++;
            }

            // If target order is at the end, add moved element at the end
            if (!$movedElementOrdered) {
                $element->order = $order;
                $element->save();
            }

            // Update orders in old parent group if parent changed
            // (if parent didn't change, the group was already updated above)
            if ($oldParentId != $newParentId && $oldParentId !== null) {
                $oldGroupElements = Element::where('user_id', $userId)
                    ->where('parent_element_id', $oldParentId)
                    ->orderBy('order', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->get();

                $order = 1;
                foreach ($oldGroupElements as $oldGroupElement) {
                    if ($oldGroupElement->order != $order) {
                        $oldGroupElement->order = $order;
                        $oldGroupElement->save();
                        $affectedElementIds[] = $oldGroupElement->id;
                    }
                    $order++;
                }
            }

            DB::commit();

            // Return all affected elements
            $affectedElements = Element::where('user_id', $userId)
                ->whereIn('id', array_unique($affectedElementIds))
                ->orderBy('parent_element_id', 'asc')
                ->orderBy('order', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'message' => 'Element moved successfully',
                'elements' => $affectedElements
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to move element: ' . $e->getMessage()], 500);
        }
    }
}

