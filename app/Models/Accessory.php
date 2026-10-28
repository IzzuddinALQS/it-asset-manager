<?php

namespace App\Models;

use Database\Factories\AccessoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accessory extends Model
{
    /** @use HasFactory<AccessoryFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'model',
        'category_id',
        'manufacturer_id',
        'qty_total',
        'min_qty',
        'unit_cost',
        'location_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_total' => 'integer',
            'min_qty' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Manufacturer, $this>
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<AccessoryCheckout, $this>
     */
    public function checkouts(): HasMany
    {
        return $this->hasMany(AccessoryCheckout::class);
    }

    /**
     * @return HasMany<AccessoryCheckout, $this>
     */
    public function activeCheckouts(): HasMany
    {
        return $this->checkouts()->whereNull('checked_in_at');
    }

    /**
     * @return HasOne<AccessoryStock, $this>
     */
    public function stock(): HasOne
    {
        return $this->hasOne(AccessoryStock::class, 'accessory_id');
    }

    /**
     * @return MorphMany<Alert, $this>
     */
    public function alerts(): MorphMany
    {
        return $this->morphMany(Alert::class, 'subject');
    }
}
