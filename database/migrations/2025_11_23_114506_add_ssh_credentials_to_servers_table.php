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
        Schema::table('servers', function (Blueprint $table) {
            $table->string('ssh_user')->default('root')->after('ip_address');
            $table->integer('ssh_port')->default(22)->after('ssh_user');
            $table->text('ssh_private_key')->nullable()->after('ssh_port');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['ssh_user', 'ssh_port', 'ssh_private_key']);
        });
    }
};
