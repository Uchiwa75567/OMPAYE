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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marchand_id');
            $table->bigInteger('montant');
            $table->string('code')->unique();
            $table->string('statut')->default('active');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('marchand_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};