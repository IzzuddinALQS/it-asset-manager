<?php

namespace App\Actions\Herd;

use App\Actions\Alerts\SyncOffboardingAlert;
use App\Enums\EmploymentStatus;
use App\Models\HerdSyncRun;
use App\Models\User;
use App\Services\Herd\HerdApiClient;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Pulls every employee granted to this app in HERD, upserts them by NIK and logs the run in herd_sync_runs.
 *
 * Employees HERD reports as inactive become "Nonaktif" here, which raises the offboarding alert
 * when they still hold assets, accessories or license seats.
 */
class SyncEmployeesFromHerd
{
    private const int PER_PAGE = 100;

    public function __construct(
        private HerdApiClient $herd,
        private SyncEmployeeFromHerd $syncEmployee,
        private SyncOffboardingAlert $syncOffboardingAlert,
    ) {}

    public function handle(?User $triggeredBy = null): HerdSyncRun
    {
        $run = HerdSyncRun::query()->create([
            'started_at' => Carbon::now(),
            'status' => 'running',
            'triggered_by' => $triggeredBy?->id,
        ]);

        $counts = ['created_count' => 0, 'updated_count' => 0, 'offboarding_count' => 0];

        try {
            $page = 1;

            do {
                $result = $this->herd->applicationUsers(page: $page, perPage: self::PER_PAGE);

                foreach ($result['data'] as $grantedUser) {
                    $this->syncOne($grantedUser, $counts);
                }

                $page++;
            } while ($page <= $result['meta']['lastPage']);
        } catch (Throwable $exception) {
            $run->update([...$counts, 'status' => 'failed', 'finished_at' => Carbon::now(), 'error_message' => $exception->getMessage()]);

            return $run;
        }

        $run->update([...$counts, 'status' => 'success', 'finished_at' => Carbon::now()]);

        return $run;
    }

    /**
     * @param  array<string, mixed>  $grantedUser
     * @param  array{created_count: int, updated_count: int, offboarding_count: int}  $counts
     */
    private function syncOne(array $grantedUser, array &$counts): void
    {
        $employee = $this->syncEmployee->handle(SyncEmployeeFromHerd::fromGrantedUser($grantedUser));
        $counts[$employee->wasRecentlyCreated ? 'created_count' : 'updated_count']++;

        $isActiveInHerd = (bool) ($grantedUser['isActive'] ?? true);

        if (! $isActiveInHerd && $employee->isActive()) {
            $employee->update([
                'employment_status' => EmploymentStatus::Inactive,
                'status_effective_date' => Carbon::today(),
            ]);
            $counts['offboarding_count']++;
        }

        $this->syncOffboardingAlert->handle($employee);
    }
}
