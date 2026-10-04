<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_day_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('month', 10);
            $table->string('year', 10);
            $table->decimal('work_days', 5, 1);
            $table->timestamps();

            $table->unique(['month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_day_overrides');
    }
};
