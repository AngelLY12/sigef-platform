<?php

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
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
        Schema::create('payment_concepts', function (Blueprint $table) {
            $table->id();
            $table->string('concept_name')->index();
            $table->text('description')->nullable();
            $table->string('status',50)->default(PaymentConceptStatus::ACTIVO->value);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('amount', 7,2)->index()->check('amount >= 10');
            $table->string('applies_to',50)->default(PaymentConceptAppliesTo::TODOS->value);
            $table->timestamp('mark_as_deleted_at')->nullable()->index();
            $table->timestamps();
            $table->index(['created_at']);
            $table->index(['status', 'start_date', 'end_date']);
            $table->index(['status', 'updated_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_concepts');
    }
};
