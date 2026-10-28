<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\HandledBy;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use Database\Factories\MaintenanceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property MaintenanceStatus $status
 * @property MaintenanceType $type
 * @property AssetStatus|null $asset_status_before
 */
class Maintenance extends Model
{
    /** @use HasFactory<MaintenanceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ticket_number',
        'asset_id',
        'plan_id',
        'type',
        'status',
        'priority',
        'title',
        'description',
        'handled_by',
        'supplier_id',
        'reported_by_employee_id',
        'estimated_cost',
        'actual_cost',
        'start_date',
        'target_date',
        'completed_at',
        'asset_status_before',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MaintenanceType::class,
            'status' => MaintenanceStatus::class,
            'priority' => MaintenancePriority::class,
            'handled_by' => HandledBy::class,
            'asset_status_before' => AssetStatus::class,
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'start_date' => 'date',
            'target_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<MaintenancePlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'plan_id');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reported_by_employee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ComponentInstallation, $this>
     */
    public function componentInstallations(): HasMany
    {
        return $this->hasMany(ComponentInstallation::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [
            MaintenanceStatus::Reported,
            MaintenanceStatus::InProgress,
            MaintenanceStatus::WaitingParts,
        ]);
    }
}
