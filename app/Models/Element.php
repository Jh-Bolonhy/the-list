<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Element extends Model
{
    protected $table = 'elements';

    protected $fillable = [
        'parent_element_id',
        'title',
        'description',
        'completed'
    ];

    protected $casts = [
        'completed' => 'boolean'
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
}

