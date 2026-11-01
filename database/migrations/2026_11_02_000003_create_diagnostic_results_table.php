<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sme_id')->constrained('smes')->cascadeOnDelete();
            $table->foreignId('financial_record_id')->constrained('financial_records')->cascadeOnDelete();

            // Normalised 0-100 pillar scores
            $table->decimal('liquidity_score', 5, 2);
            $table->decimal('profitability_score', 5, 2);
            $table->decimal('efficiency_score', 5, 2);
            $table->decimal('operational_score', 5, 2);
            $table->decimal('overall_health_score', 5, 2);

            // Stored as string for MySQL/PostgreSQL portability; validated by App\Enums\HealthStatus.
            $table->string('health_status', 20)->index();

            $table->json('metrics');         // raw computed metrics snapshot
            $table->json('key_findings');
            $table->json('recommendations');
            $table->string('scoring_version', 10);

            // Immutable snapshot => no updated_at.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sme_id', 'financial_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_results');
    }
};
