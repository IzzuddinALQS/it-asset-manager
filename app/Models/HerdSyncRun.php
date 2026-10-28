<?php

namespace App\Models;

use Database\Factories\HerdSyncRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HerdSyncRun extends Model
{
    /** @use HasFactory<HerdSyncRunFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'started_at',
        'finished_at',
        'status',
        'created_count',
        'updated_count',
        'offboarding_count',
        'error_message',
        'triggered_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'offboarding_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
