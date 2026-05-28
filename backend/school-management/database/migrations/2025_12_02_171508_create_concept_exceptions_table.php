<?php

use App\Models\PaymentConcept;
use App\Models\User;
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
        Schema::create('concept_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PaymentConcept::class)->nullable()->constrained('payment_concepts')->onDelete('cascade');
            $table->foreignIdFor(User::class)->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['payment_concept_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('concept_exceptions');
    }
};
