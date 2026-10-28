<?php

namespace App\Models;

use Database\Factories\ManufacturerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends Model
{
    /** @use HasFactory<ManufacturerFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['name'];

    /**
     * @return HasMany<AssetModel, $this>
     */
    public function assetModels(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }

    /**
     * @return HasMany<Component, $this>
     */
    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }

    /**
     * @return HasMany<Accessory, $this>
     */
    public function accessories(): HasMany
    {
        return $this->hasMany(Accessory::class);
    }

    /**
     * @return HasMany<License, $this>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
