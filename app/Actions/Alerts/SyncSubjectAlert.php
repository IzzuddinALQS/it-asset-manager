<?php

namespace App\Actions\Alerts;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Models\Alert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Opens, refreshes or resolves the single open alert of a type for a subject. The
 * `alerts_open_unique` partial index guarantees there is never more than one.
 */
class SyncSubjectAlert
{
    /**
     * @param  array{severity: AlertSeverity, message: string, due_date: string|null}  $attributes
     */
    public function open(Model $subject, AlertType $type, array $attributes): void
    {
        $openAlert = $this->openQuery($subject, $type)->first();

        if ($openAlert !== null) {
            $openAlert->update($attributes);

            return;
        }

        Alert::query()->create([
            ...$attributes,
            'type' => $type,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }

    public function resolve(Model $subject, AlertType $type): void
    {
        $this->openQuery($subject, $type)->update(['resolved_at' => Carbon::now()]);
    }

    /**
     * @return Builder<Alert>
     */
    private function openQuery(Model $subject, AlertType $type): Builder
    {
        return Alert::query()
            ->open()
            ->where('type', $type)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }
}
