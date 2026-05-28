<?php

use App\Models\Career;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PaymentConcept;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('career_payment_concept', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Career::class)->constrained('careers')->onDelete('cascade');
            $table->foreignIdFor(PaymentConcept::class)->constrained('payment_concepts')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['payment_concept_id', 'career_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_payment_concept');
    }
};
