<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name', 150);
            $table->string('industry_sector', 50)->index(); // App\Enums\IndustrySector
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smes');
    }
};
