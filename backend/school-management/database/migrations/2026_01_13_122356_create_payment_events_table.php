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
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Payment::class)->nullable()->constrained('payments')->onDelete('cascade');
            $table->string('stripe_event_id')->nullable()->index();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_session_id')->nullable()->index();
            $table->string('event_type');
            $table->json('metadata')->nullable();
            $table->decimal('amount_received', 10, 2)->nullable();
            $table->string('status')->nullable();
            $table->boolean('processed')->default(false);
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['stripe_event_id', 'event_type']);
            $table->index(['payment_id', 'processed', 'created_at']);
            $table->index(['stripe_payment_intent_id', 'processed']);
            $table->index(['stripe_session_id', 'processed']);
            $table->index(['event_type', 'processed', 'created_at']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
