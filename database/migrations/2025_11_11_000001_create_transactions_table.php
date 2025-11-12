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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('montant');
            $table->string('type');
            $table->string('statut');
            $table->uuid('compte_source_id');
            $table->uuid('compte_dest_id')->nullable();
            $table->uuid('marchand_id')->nullable();
            $table->string('reference')->unique();
            $table->bigInteger('frais')->default(0);
            $table->timestamps();

            $table->foreign('compte_source_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->foreign('compte_dest_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->foreign('marchand_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};