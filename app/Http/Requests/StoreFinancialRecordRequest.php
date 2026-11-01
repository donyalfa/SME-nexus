<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\IndustrySector;
use App\Enums\PeriodType;
use App\Services\DetectService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreFinancialRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** Let users paste "Rp 1.500.000" style amounts: DetectService turns them into numbers. */
    protected function prepareForValidation(): void
    {
        $this->merge(app(DetectService::class)->normalizeInput($this->all()));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $money = ['required', 'numeric', 'min:0', 'max:9999999999999.99'];

        return [
            // Existing business owned by the user, or the profile fields for a new one.
            'sme_id'          => ['nullable', 'integer', Rule::exists('smes', 'id')->where('user_id', $this->user()?->id)],
            'business_name'   => ['required_without:sme_id', 'nullable', 'string', 'max:150'],
            'industry_sector' => ['required_without:sme_id', 'nullable', Rule::enum(IndustrySector::class)],

            'period_type' => ['required', Rule::enum(PeriodType::class)],
            'period_date' => [
                'required', 'date', 'before_or_equal:today',
                Rule::unique('financial_records', 'period_date')->where(
                    fn ($q) => $q->where('sme_id', $this->input('sme_id'))->where('period_type', $this->input('period_type'))
                ),
            ],

            'revenue'             => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'cogs'                => $money,
            'operating_expenses'  => $money,
            'current_assets'      => $money,
            'current_liabilities' => $money,
            'cash_balance'        => $money,
            'receivables'         => $money,
            'payables'            => $money,
            'inventory_value'     => $money,
        ];
    }

    /** Cross-field checks that run only when basic validation passed. */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $d     = $validator->getData();
                $parts = (float) $d['cash_balance'] + (float) $d['receivables'] + (float) $d['inventory_value'];

                if ($parts - (float) $d['current_assets'] > 0.01) {
                    $validator->errors()->add(
                        'current_assets',
                        'Aset lancar minimal harus sebesar kas + piutang + persediaan.'
                    );
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period_date.unique'               => 'Bisnis ini sudah memiliki data untuk periode tersebut.',
            'period_date.before_or_equal'      => 'Tanggal periode tidak boleh di masa depan.',
            'business_name.required_without'   => 'Nama bisnis wajib diisi.',
            'industry_sector.required_without' => 'Pilih sektor industri.',
            'revenue.gt'                       => 'Pendapatan harus lebih dari 0 agar rasio bisa dihitung.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'sme_id'              => 'bisnis',
            'business_name'       => 'nama bisnis',
            'industry_sector'     => 'sektor industri',
            'period_type'         => 'panjang periode',
            'period_date'         => 'tanggal periode',
            'revenue'             => 'pendapatan',
            'cogs'                => 'harga pokok penjualan',
            'operating_expenses'  => 'biaya operasional',
            'current_assets'      => 'aset lancar',
            'current_liabilities' => 'liabilitas lancar',
            'cash_balance'        => 'saldo kas',
            'receivables'         => 'piutang',
            'payables'            => 'utang dagang',
            'inventory_value'     => 'nilai persediaan',
        ];
    }
}
