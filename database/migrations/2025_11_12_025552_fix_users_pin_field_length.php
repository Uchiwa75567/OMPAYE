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
        // Étape 1: Autoriser les NULL pour les utilisateurs sans pin
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Étape 1: Remettre la longueur à 4 et NOT NULL
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin', 4)->change();
        });
    }
};
