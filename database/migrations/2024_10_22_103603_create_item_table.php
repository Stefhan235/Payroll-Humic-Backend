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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->text('information');
            $table->bigInteger('bruto_amount');
            $table->bigInteger('tax_amount');
            $table->bigInteger('netto_amount');
            $table->enum('category', ['internal', 'eksternal', 'rka']);
            $table->boolean('isAddition');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
