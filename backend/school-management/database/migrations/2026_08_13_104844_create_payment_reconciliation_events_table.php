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
        Schema::create('payment_reconciliation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->nullOnDelete();
            $table->string('outcome', 30)->nullable();
            $table->string('status', 30)->default(\App\Core\Domain\Enum\Events\Status\ReconciliationEventStatus::PENDING->value);
            $table->string('source_type', 50)->nullable();
            $table->string('source_id', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['payment_id']);
            $table->index(['source_type', 'source_id']);
            $table->unique(['source_type', 'source_id']);
            $table->index(['status', 'created_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_events');
    }
};
