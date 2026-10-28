<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property EmploymentStatus $employment_status
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'herd_id',
        'employee_number',
        'name',
        'email',
        'job_title',
        'department_id',
        'location_id',
        'manager_id',
        'employment_status',
        'status_effective_date',
        'synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employment_status' => EmploymentStatus::class,
            'status_effective_date' => 'date',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'assigned_employee_id');
    }

    /**
     * @return HasMany<AccessoryCheckout, $this>
     */
    public function accessoryCheckouts(): HasMany
    {
        return $this->hasMany(AccessoryCheckout::class);
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
    public function reportedMaintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'reported_by_employee_id');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('employment_status', EmploymentStatus::Active);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeNeedsOffboarding(Builder $query): void
    {
        $query->whereIn('employment_status', EmploymentStatus::offboardingStatuses());
    }

    public function isActive(): bool
    {
        return $this->employment_status->isActive();
    }
}
