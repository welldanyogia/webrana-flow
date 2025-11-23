<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'is_shared_db')) {
                $table->boolean('is_shared_db')->default(true);
            }
            if (!Schema::hasColumn('packages', 'cpu_limit')) {
                $table->string('cpu_limit')->default('0.5');
            }
            if (!Schema::hasColumn('packages', 'ram_limit')) {
                $table->string('ram_limit')->default('256M');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['is_shared_db', 'cpu_limit', 'ram_limit']);
        });
    }
};
