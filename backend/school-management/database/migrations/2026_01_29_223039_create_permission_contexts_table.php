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
        Schema::create('permission_contexts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\Spatie\Permission\Models\Permission::class)->constrained('permissions')->onDelete('cascade');
            $table->string('context', 50);
            $table->string('target_role', 50);
            $table->timestamps();
            $table->index(['target_role', 'context']);
            $table->index(['permission_id', 'target_role']);
            $table->unique(['permission_id', 'context', 'target_role']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_contexts');
    }
};
