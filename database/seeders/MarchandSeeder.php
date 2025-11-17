<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Compte;
use App\Models\MarchandCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarchandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Liste des marchands à créer
        $marchands = [
            [
                'nom' => 'Boutique Orange',
                'prenom' => 'Dakar',
                'telephone' => '781234560',
                'solde' => 500000,
                'nom_marchand' => 'Boutique Orange Dakar'
            ],
            [
                'nom' => 'Restaurant Thieboudienne',
                'prenom' => 'Thies',
                'telephone' => '771234560',
                'solde' => 300000,
                'nom_marchand' => 'Restaurant Thieboudienne'
            ],
            [
                'nom' => 'Pharmacie Centrale',
                'prenom' => 'Rufisque',
                'telephone' => '781234561',
                'solde' => 200000,
                'nom_marchand' => 'Pharmacie Centrale'
            ],
            [
                'nom' => 'Supermarché Carrefour',
                'prenom' => 'Kaolack',
                'telephone' => '771234561',
                'solde' => 750000,
                'nom_marchand' => 'Supermarché Carrefour'
            ],
            [
                'nom' => 'Station Essence Shell',
                'prenom' => 'Kolda',
                'telephone' => '781234562',
                'solde' => 1000000,
                'nom_marchand' => 'Station Shell'
            ],
            [
                'nom' => 'Hôtel Le Sine',
                'prenom' => 'Kaolack',
                'telephone' => '771234562',
                'solde' => 600000,
                'nom_marchand' => 'Hôtel Le Sine'
            ],
        ];

        // Créer chaque marchand
        foreach ($marchands as $marchandData) {
            // Créer l'utilisateur
            $user = User::create([
                'nom' => $marchandData['nom'],
                'prenom' => $marchandData['prenom'],
                'cni' => 'MARCHAND_' . Str::random(10),
                'telephone' => $marchandData['telephone'],
                'sexe' => 'M',
                'role' => 'marchand',
                'password' => Hash::make('marchand123'),
            ]);

            // Créer le compte
            $compte = Compte::create([
                'user_id' => $user->id,
                'solde' => $marchandData['solde'],
                'type' => 'marchand',
            ]);

            // Générer un code marchand unique
            $codeMarchand = 'M' . strtoupper(Str::random(6)) . rand(100, 999);

            // Créer le code marchand
            $marchand_code = MarchandCode::create([
                'user_id' => $user->id,
                'code_marchand' => $codeMarchand,
                'actif' => true,
            ]);

            $this->command->info("✅ Marchand créé: {$marchandData['nom']} - Téléphone: {$marchandData['telephone']} - Code: {$codeMarchand} - Solde: {$marchandData['solde']}");
        }

        $this->command->info("\n✅ Total: " . count($marchands) . " marchands créés avec succès!");
    }
}
