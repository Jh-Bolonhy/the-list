<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Element extends Model
{
    protected $table = 'elements';

    protected $fillable = [
        'user_id',
        'parent_element_id',
        'title',
        'description',
        'completed',
        'archived'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'archived' => 'boolean'
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
}

