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
        Schema::create('finances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_name');
            $table->enum('transaction_type', ['income', 'expense']);
            $table->decimal('amount', 11, 2);;
            $table->decimal('tax_amount', 11, 2);
            $table->text('document_evidence');
            $table->text('image_evidence');
            $table->enum('status', ['approve', 'decline'])->default('decline');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finances');
    }
};
