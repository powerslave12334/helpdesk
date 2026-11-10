<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryFieldSection extends Model
{
    protected $table = 'inventory_field_sections';

    protected $fillable = ['key', 'title', 'icon', 'description', 'order'];

    protected $casts = [
        'order' => 'integer',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(InventoryField::class, 'section_id')->orderBy('order');
    }
}
