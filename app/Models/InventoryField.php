<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryField extends Model
{
    public const SOURCE_COLUMN = 'column';

    public const SOURCE_CUSTOM = 'custom';

    protected $table = 'inventory_fields';

    protected $fillable = [
        'section_id',
        'name',
        'label',
        'type',
        'help',
        'source',
        'validation_rules',
        'order',
        'is_active',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(InventoryFieldSection::class, 'section_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('source', self::SOURCE_CUSTOM);
    }

    public function isCustom(): bool
    {
        return $this->source === self::SOURCE_CUSTOM;
    }
}
