<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\RetireReason;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property AssetStatus $status
 * @property RetireReason|null $retire_reason
 * @property Carbon|null $warranty_until
 * @property Carbon|null $purchase_date
 * @property Carbon|null $expected_return_date
 * @property Carbon|null $assigned_at
 * @property Carbon|null $retired_at
 * @property Carbon|null $last_audited_at
 */
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'asset_tag',
        'serial_number',
        'asset_model_id',
        'status',
        'assigned_employee_id',
        'assigned_location_id',
        'assigned_at',
        'expected_return_date',
        'location_id',
        'supplier_id',
        'purchase_order',
        'purchase_date',
        'purchase_cost',
        'warranty_until',
        'last_audited_at',
        'notes',
        'retired_at',
        'retire_reason',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'retire_reason' => RetireReason::class,
            'assigned_at' => 'datetime',
            'expected_return_date' => 'date',
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'warranty_until' => 'date',
            'last_audited_at' => 'date',
            'retired_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AssetModel, $this>
     */
    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function assignedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'assigned_location_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<AssetLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AssetLog::class)->latest('created_at');
    }

    /**
     * @return HasMany<ComponentInstallation, $this>
     */
    public function componentInstallations(): HasMany
    {
        return $this->hasMany(ComponentInstallation::class);
    }

    /**
     * @return HasMany<LicenseSeat, $this>
     */
    public function licenseSeats(): HasMany
    {
        return $this->hasMany(LicenseSeat::class);
    }

    /**
     * @return HasMany<Maintenance, $this>
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeStatus(Builder $query, AssetStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', AssetStatus::Ready);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeDeployed(Builder $query): void
    {
        $query->where('status', AssetStatus::Deployed);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function categoryName(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->assetModel?->category?->name);
    }

    public function isDeployed(): bool
    {
        return $this->status === AssetStatus::Deployed;
    }

    public function isRetired(): bool
    {
        return $this->status === AssetStatus::Retired;
    }

    /**
     * @return MorphMany<Alert, $this>
     */
    public function alerts(): MorphMany
    {
        return $this->morphMany(Alert::class, 'subject');
    }
}
