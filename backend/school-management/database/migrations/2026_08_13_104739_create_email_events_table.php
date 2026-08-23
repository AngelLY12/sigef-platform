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
        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('event_type', 50);
            $table->string('recipient_email');
            $table->string('status', 30)->default(\App\Core\Domain\Enum\Events\Status\EmailEventStatus::PENDING->value);
            $table->string('source_type', 50)->nullable();
            $table->string('source_id', 100)->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['event_type']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['source_type', 'source_id']);
            $table->unique(['source_type', 'source_id', 'event_type']);
            $table->index(['recipient_email', 'created_at']);
            $table->index(['created_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_events');
    }
};
