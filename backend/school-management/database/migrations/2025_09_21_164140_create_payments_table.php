<?php

use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Models\User;
use App\Models\PaymentConcept;
use App\Models\PaymentMethod;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable()->constrained('users')->onDelete('set null');
            $table->foreignIdFor(PaymentConcept::class)->nullable()->constrained('payment_concepts')->onDelete('set null');
            $table->foreignIdFor(PaymentMethod::class)->nullable()->constrained('payment_methods')->onDelete('set null');
            $table->string('stripe_payment_method_id',100)->nullable()->index();
            $table->string('concept_name')->index();
            $table->decimal('amount', 7,2)->index();
            $table->decimal('amount_received', 7,2)->nullable()->index();
            $table->json('payment_method_details');
            $table->string('status',50)->default(PaymentStatus::DEFAULT->value);
            $table->string('payment_intent_id',100)->unique()->nullable();
            $table->text('url')->nullable();
            $table->string('stripe_session_id', 100)->nullable()->unique();
            $table->timestamps();
            $table->index(['created_at', 'amount_received']);
            $table->index(['status', 'created_at']);
            $table->index(['payment_concept_id', 'user_id', 'id']);
            $table->index(['payment_concept_id', 'user_id', 'status']);
            $table->index(['created_at', 'user_id']);

        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
