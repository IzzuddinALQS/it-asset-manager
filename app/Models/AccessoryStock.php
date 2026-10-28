<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only row of the `accessory_stock` view.
 *
 * @property int $accessory_id
 * @property int $qty_total
 * @property int $qty_out
 * @property int $qty_available
 */
class AccessoryStock extends Model
{
    protected $table = 'accessory_stock';

    protected $primaryKey = 'accessory_id';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_total' => 'integer',
            'qty_out' => 'integer',
            'qty_available' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Accessory, $this>
     */
    public function accessory(): BelongsTo
    {
        return $this->belongsTo(Accessory::class);
    }
}
