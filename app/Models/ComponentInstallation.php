<?php

namespace App\Models;

use Database\Factories\ComponentInstallationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentInstallation extends Model
{
    /** @use HasFactory<ComponentInstallationFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'component_id',
        'asset_id',
        'qty',
        'is_factory',
        'maintenance_id',
        'installed_at',
        'installed_by',
        'removed_at',
        'removed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'is_factory' => 'boolean',
            'installed_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Component, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<Maintenance, $this>
     */
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }
}
