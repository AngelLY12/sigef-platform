<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
        $table->id();
        $table->foreignIdFor(User::class)->constrained('users')->onDelete('cascade');
        $table->string('stripe_payment_method_id',100)->unique();
        $table->string('brand',20)->nullable();
        $table->string('last4', 4)->nullable();
        $table->unsignedTinyInteger('exp_month')->nullable();
        $table->unsignedSmallInteger('exp_year')->nullable();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
