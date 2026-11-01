<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sme_id')->constrained('smes')->cascadeOnDelete();
            $table->date('period_date');
            $table->string('period_type', 20)->default('monthly'); // App\Enums\PeriodType

            // Income statement (for the whole period)
            $table->decimal('revenue', 15, 2);
            $table->decimal('cogs', 15, 2);
            $table->decimal('operating_expenses', 15, 2);

            // Balance sheet (snapshot at period_date)
            $table->decimal('current_assets', 15, 2);
            $table->decimal('current_liabilities', 15, 2);
            $table->decimal('cash_balance', 15, 2);
            $table->decimal('receivables', 15, 2);
            $table->decimal('payables', 15, 2);
            $table->decimal('inventory_value', 15, 2);

            $table->timestamps();

            // One record per business per period.
            $table->unique(['sme_id', 'period_date', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_records');
    }
};
