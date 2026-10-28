<?php

namespace App\Models;

use Database\Factories\AccessoryCheckoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessoryCheckout extends Model
{
    /** @use HasFactory<AccessoryCheckoutFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'accessory_id',
        'employee_id',
        'qty',
        'checked_out_at',
        'checked_out_by',
        'checked_in_at',
        'checked_in_by',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'checked_out_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Accessory, $this>
     */
    public function accessory(): BelongsTo
    {
        return $this->belongsTo(Accessory::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isActive(): bool
    {
        return $this->checked_in_at === null;
    }
}
