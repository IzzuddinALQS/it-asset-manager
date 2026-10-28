<?php

namespace App\Models;

use App\Enums\LicenseType;
use Database\Factories\LicenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class License extends Model
{
    /** @use HasFactory<LicenseFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'manufacturer_id',
        'category_id',
        'license_type',
        'product_key',
        'seats_total',
        'supplier_id',
        'purchase_order',
        'purchase_date',
        'annual_cost',
        'expires_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'license_type' => LicenseType::class,
            'product_key' => 'encrypted',
            'seats_total' => 'integer',
            'purchase_date' => 'date',
            'annual_cost' => 'decimal:2',
            'expires_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Manufacturer, $this>
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany<LicenseSeat, $this>
     */
    public function seats(): HasMany
    {
        return $this->hasMany(LicenseSeat::class);
    }

    /**
     * @return HasMany<LicenseSeat, $this>
     */
    public function activeSeats(): HasMany
    {
        return $this->seats()->whereNull('released_at');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): void
    {
        $query->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays($days))
            ->whereDate('expires_at', '>=', now()->toDateString());
    }

    public function seatsUsed(): int
    {
        return $this->activeSeats()->count();
    }

    public function seatsAvailable(): int
    {
        $used = $this->relationLoaded('activeSeats') ? $this->activeSeats->count() : $this->seatsUsed();

        return max(0, $this->seats_total - $used);
    }

    public function maskedProductKey(): ?string
    {
        if ($this->product_key === null || $this->product_key === '') {
            return null;
        }

        $key = $this->product_key;
        $visible = substr($key, -4);

        return str_repeat('•', max(0, strlen($key) - 4)).$visible;
    }
}
