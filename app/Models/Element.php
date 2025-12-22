<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Element extends Model
{
    protected $table = 'elements';

    protected $fillable = [
        'user_id',
        'parent_element_id',
        'order',
        'title',
        'description',
        'completed',
        'archived',
        'collapsed'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'archived' => 'boolean',
        'collapsed' => 'boolean'
    ];

    /**
     * Get the parent element.
     */
    public function parent()
    {
        return $this->belongsTo(Element::class, 'parent_element_id');
    }

    /**
     * Get the child elements.
     */
    public function children()
    {
        return $this->hasMany(Element::class, 'parent_element_id');
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Archive this element and all its descendants
     * Preserves the parent-child structure (parent_element_id remains unchanged)
     */
    public function archiveWithDescendants()
    {
        // Get all children of the same user
        $children = Element::where('parent_element_id', $this->id)
            ->where('user_id', $this->user_id)
            ->get();
        
        // Archive all descendants first (recursive)
        foreach ($children as $child) {
            if (!$child->archived) {
                $child->archiveWithDescendants();
            }
        }
        
        // Archive this element
        $this->update(['archived' => true]);
    }

    /**
     * Restore this element and all its descendants
     */
    public function restoreWithDescendants()
    {
        // Restore this element first
        $this->update(['archived' => false]);
        
        // Get all descendants of the same user and restore them recursively
        $descendants = Element::where('parent_element_id', $this->id)
            ->where('user_id', $this->user_id)
            ->get();
        foreach ($descendants as $child) {
            if ($child->archived) {
                $child->restoreWithDescendants();
            }
        }
    }

    /**
     * Restore archived parents in the chain up to the first non-archived parent or root
     * Only restores the parent chain, not other children of those parents
     */
    public function restoreParentChain()
    {
        $currentId = $this->parent_element_id;
        $restoredParents = [];

        // Traverse up the parent chain
        while ($currentId !== null) {
            $parent = Element::where('user_id', $this->user_id)
                ->find($currentId);
            
            if (!$parent) {
                break; // Parent not found or doesn't belong to user
            }

            // If parent is archived, restore it
            if ($parent->archived) {
                $parent->update(['archived' => false]);
                $restoredParents[] = $parent->id;
            } else {
                // Found first non-archived parent, stop here
                break;
            }

            // Move to next parent
            $currentId = $parent->parent_element_id;
        }

        return $restoredParents;
    }
}

