<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['numeric', 'categorical']);
            $table->jsonb('options')->nullable(); // для categorical
            $table->timestamps();
        });

        Schema::create('metric_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metric_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bug_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('numeric_value', 15, 4)->nullable();
            $table->string('categorical_value')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->unique(['metric_id', 'bug_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_values');
        Schema::dropIfExists('metrics');
    }
};
