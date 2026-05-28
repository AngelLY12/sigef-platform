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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Payment::class)->unique()->constrained('payments')->cascadeOnDelete();
            $table->string('folio',50)->unique();
            $table->string('payer_name');
            $table->string('payer_email')->nullable();
            $table->string('concept_name');
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_received', 10, 2)->nullable();
            $table->string('transaction_reference', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();
            $table->index('issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
