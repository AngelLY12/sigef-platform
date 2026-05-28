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
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('parent_role_id')->nullable();
            $table->unsignedBigInteger('student_role_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_role_id')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('student_role_id')->references('id')->on('roles')->onDelete('set null');
            $table->string('relationship',50)->nullable();
            $table->unique(['parent_id', 'student_id']);
            $table->index('parent_id');
            $table->index('student_id');
            $table->index(['student_id', 'parent_id']);
            $table->timestamps();
        });

        Schema::create('parent_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('email');
            $table->uuid('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('student_id');
            $table->index('email');
            $table->index('expires_at');
            $table->index('used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_student');
    }
};
