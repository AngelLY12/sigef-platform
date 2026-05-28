<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PaymentConcept;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_concept_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PaymentConcept::class)->constrained('payment_concepts')->onDelete('cascade');
            $table->foreignIdFor(User::class)->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['payment_concept_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_concept_user');
    }
};
