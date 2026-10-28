<?php

namespace App\Models;

use Database\Factories\ComponentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends Model
{
    /** @use HasFactory<ComponentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'spec',
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
     * @return HasMany<ComponentInstallation, $this>
     */
    public function installations(): HasMany
    {
        return $this->hasMany(ComponentInstallation::class);
    }

    /**
     * @return HasMany<ComponentInstallation, $this>
     */
    public function activeInstallations(): HasMany
    {
        return $this->installations()->whereNull('removed_at');
    }

    /**
     * @return HasOne<ComponentStock, $this>
     */
    public function stock(): HasOne
    {
        return $this->hasOne(ComponentStock::class, 'component_id');
    }

    /**
     * @return MorphMany<Alert, $this>
     */
    public function alerts(): MorphMany
    {
        return $this->morphMany(Alert::class, 'subject');
    }
}
