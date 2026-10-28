<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only row of the `component_stock` view.
 *
 * @property int $component_id
 * @property int $qty_total
 * @property int $qty_installed
 * @property int $qty_available
 */
class ComponentStock extends Model
{
    protected $table = 'component_stock';

    protected $primaryKey = 'component_id';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_total' => 'integer',
            'qty_installed' => 'integer',
            'qty_available' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Component, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}
