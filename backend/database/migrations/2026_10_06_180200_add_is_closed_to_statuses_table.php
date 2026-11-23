<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Переименование дефолтных статусов в мужской род (Баг — мужского рода).
     */
    private const RENAMES = [
        'Новая' => 'Новый',
        'Назначена' => 'Назначен',
        'Исправлена' => 'Исправлен',
        'Переоткрыта' => 'Переоткрыт',
        'Закрыта' => 'Закрыт',
    ];

    public function up(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->boolean('is_closed')->default(false);
        });

        foreach (self::RENAMES as $old => $new) {
            DB::table('statuses')->where('name', $old)->update(['name' => $new]);
        }

        DB::table('statuses')->where('name', 'Закрыт')->update(['is_closed' => true]);
    }

    public function down(): void
    {
        DB::table('statuses')->where('is_closed', true)->update(['is_closed' => false]);

        foreach (self::RENAMES as $old => $new) {
            DB::table('statuses')->where('name', $new)->update(['name' => $old]);
        }

        Schema::table('statuses', function (Blueprint $table) {
            $table->dropColumn('is_closed');
        });
    }
};
