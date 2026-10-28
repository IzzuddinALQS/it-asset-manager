<?php

namespace App\Http\Controllers\Alerts;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Alert::class);

        $type = AlertType::tryFrom($request->string('type')->toString());
        $severity = AlertSeverity::tryFrom($request->string('severity')->toString());
        $status = $request->string('status')->toString() === 'resolved' ? 'resolved' : 'open';

        $alerts = Alert::query()
            ->with(['subject', 'resolvedBy.employee:id,name'])
            ->when($status === 'open', fn (Builder $query) => $query->open(), fn (Builder $query) => $query->whereNotNull('resolved_at'))
            ->when($type !== null, fn (Builder $query) => $query->where('type', $type))
            ->when($severity !== null, fn (Builder $query) => $query->where('severity', $severity))
            ->when(
                $status === 'open',
                fn (Builder $query) => $query
                    ->orderByRaw("case severity when 'critical' then 0 when 'warning' then 1 else 2 end")
                    ->orderByRaw('due_date is null, due_date'),
                fn (Builder $query) => $query->latest('resolved_at'),
            )
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Alert $alert): array => $this->present($alert));

        $openCounts = Alert::query()->open()->toBase()
            ->selectRaw('severity, count(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        return Inertia::render('Alerts/Index', [
            'filters' => [
                'type' => $type?->value ?? '',
                'severity' => $severity?->value ?? '',
                'status' => $status,
            ],
            'alerts' => $alerts,
            'summary' => collect(AlertSeverity::cases())
                ->mapWithKeys(fn (AlertSeverity $case): array => [$case->value => (int) ($openCounts[$case->value] ?? 0)])
                ->all(),
            'types' => collect(AlertType::cases())->map(fn (AlertType $case): array => ['value' => $case->value, 'label' => $case->label()])->all(),
            'severities' => collect(AlertSeverity::cases())->map(fn (AlertSeverity $case): array => ['value' => $case->value, 'label' => $case->label()])->all(),
            'can' => [
                'resolve' => $request->user()?->can('resolve', new Alert) ?? false,
            ],
        ]);
    }

    /**
     * @return array{id: int, type: string, type_label: string, severity: string, severity_label: string, message: string, due_date: string|null, subject_label: string|null, subject_url: string|null, created_at: string|null, resolved_at: string|null, resolved_by: string|null}
     */
    public static function present(Alert $alert): array
    {
        return [
            'id' => $alert->id,
            'type' => $alert->type->value,
            'type_label' => $alert->type->label(),
            'severity' => $alert->severity->value,
            'severity_label' => $alert->severity->label(),
            'message' => $alert->message,
            'due_date' => $alert->due_date?->toDateString(),
            'subject_label' => $alert->subjectLabel(),
            'subject_url' => $alert->subjectUrl(),
            'created_at' => $alert->created_at?->toIso8601String(),
            'resolved_at' => $alert->resolved_at?->toIso8601String(),
            'resolved_by' => $alert->resolvedBy?->employee?->name,
        ];
    }
}
