<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        if (!$adminRoleId) {
            return;
        }

        Schema::table('model_has_roles', function (Blueprint $table) use ($adminRoleId) {
            $table->unique(['role_id'], 'unique_admin_role')
                ->whereRaw("role_id = {$adminRoleId}");
            $table->index(['role_id', 'model_type']);
            $table->index(['model_id','role_id']);
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropUnique('unique_admin_role');
        });
    }
};
