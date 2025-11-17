<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Compte;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Liste des clients à créer
        $clients = [
            [
                'nom' => 'Diallo',
                'prenom' => 'Amadou',
                'telephone' => '781234563',
                'solde' => 150000,
            ],
            [
                'nom' => 'Ba',
                'prenom' => 'Fatou',
                'telephone' => '771234563',
                'solde' => 200000,
            ],
            [
                'nom' => 'Traore',
                'prenom' => 'Moussa',
                'telephone' => '781234564',
                'solde' => 100000,
            ],
            [
                'nom' => 'Sow',
                'prenom' => 'Aissatou',
                'telephone' => '771234564',
                'solde' => 250000,
            ],
            [
                'nom' => 'Toure',
                'prenom' => 'Ismaïl',
                'telephone' => '781234565',
                'solde' => 300000,
            ],
            [
                'nom' => 'Diop',
                'prenom' => 'Aminata',
                'telephone' => '771234565',
                'solde' => 175000,
            ],
            [
                'nom' => 'Ndiaye',
                'prenom' => 'Cheikh',
                'telephone' => '781234566',
                'solde' => 500000,
            ],
            [
                'nom' => 'Sarr',
                'prenom' => 'Khady',
                'telephone' => '771234566',
                'solde' => 225000,
            ],
        ];

        // Créer chaque client
        foreach ($clients as $clientData) {
            $user = User::create([
                'nom' => $clientData['nom'],
                'prenom' => $clientData['prenom'],
                'cni' => 'CLIENT_' . Str::random(10),
                'telephone' => $clientData['telephone'],
                'sexe' => rand(0, 1) ? 'M' : 'F',
                'role' => 'client',
                'password' => Hash::make('client123'),
            ]);

            $compte = Compte::create([
                'user_id' => $user->id,
                'solde' => $clientData['solde'],
                'type' => 'client',
            ]);

            $this->command->info("✅ Client créé: {$clientData['prenom']} {$clientData['nom']} - Téléphone: {$clientData['telephone']} - Solde: {$clientData['solde']}");
        }

        $this->command->info("\n✅ Total: " . count($clients) . " clients créés avec succès!");
    }
}
