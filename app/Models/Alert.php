<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'severity',
        'subject_type',
        'subject_id',
        'message',
        'due_date',
        'resolved_at',
        'resolved_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AlertType::class,
            'severity' => AlertSeverity::class,
            'due_date' => 'date',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Human-readable name of the subject; expects `subject` to be loaded.
     */
    public function subjectLabel(): ?string
    {
        return match (true) {
            $this->subject instanceof Asset => $this->subject->asset_tag,
            $this->subject instanceof Maintenance => $this->subject->ticket_number,
            $this->subject instanceof Accessory,
            $this->subject instanceof Component,
            $this->subject instanceof Employee,
            $this->subject instanceof License,
            $this->subject instanceof MaintenancePlan => $this->subject->name,
            default => null,
        };
    }

    /**
     * Page where the alert can be acted on; null when the subject is gone.
     */
    public function subjectUrl(): ?string
    {
        if ($this->subject === null) {
            return null;
        }

        return match ($this->subject_type) {
            'asset' => route('assets.show', $this->subject_id, false),
            'license' => route('licenses.show', $this->subject_id, false),
            'accessory' => route('inventory.accessories.show', $this->subject_id, false),
            'component' => route('inventory.components.edit', $this->subject_id, false),
            'maintenance' => route('maintenance.show', $this->subject_id, false),
            'maintenance_plan' => route('maintenance.plans.index', absolute: false),
            'employee' => route('directory.index', absolute: false),
            default => null,
        };
    }
}
