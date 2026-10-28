<?php

namespace App\Http\Requests\Reports;

use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Report selection and period shared by the report page and its CSV export.
 */
class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report' => ['nullable', Rule::enum(ReportType::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ];
    }

    public function reportType(): ReportType
    {
        return ReportType::tryFrom($this->string('report')->toString()) ?? ReportType::AssetsByStatus;
    }

    /**
     * The period only applies to reports that have a date to filter on.
     */
    public function from(): ?Carbon
    {
        return $this->dateFilter('from');
    }

    public function to(): ?Carbon
    {
        return $this->dateFilter('to');
    }

    private function dateFilter(string $key): ?Carbon
    {
        if ($this->reportType()->dateLabel() === null || ! $this->filled($key)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $this->string($key)->toString())->startOfDay();
    }
}
