<?php

use App\Models\PaymentConcept;
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
        Schema::create('payment_concept_semester', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PaymentConcept::class)->constrained('payment_concepts')->onDelete('cascade');
            $table->integer('semestre');
            $table->timestamps();
            $table->unique(['payment_concept_id', 'semestre']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_concept_semester');
    }
};
