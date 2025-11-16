<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Compte;
use App\Models\MarchandCode;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur administrateur
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'Système',
            'cni' => 'ADMIN001',
            'telephone' => '781111111',
            'sexe' => 'M',
            'role' => 'admin',
            'password' => Hash::make('admin123'),
        ]);

        Compte::create([
            'user_id' => $admin->id,
            'solde' => 0,
            'type' => 'admin',
        ]);

        // Créer quelques utilisateurs normaux
        $users = [
            [
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'cni' => '123456789',
                'telephone' => '782345678',
                'sexe' => 'M',
                'role' => 'utilisateur',
                'password' => Hash::make('user123'),
                'solde' => 500000, // 5000 FCFA
            ],
            [
                'nom' => 'Martin',
                'prenom' => 'Marie',
                'cni' => '987654321',
                'telephone' => '783456789',
                'sexe' => 'F',
                'role' => 'utilisateur',
                'password' => Hash::make('user123'),
                'solde' => 250000, // 2500 FCFA
            ],
            [
                'nom' => 'Dia',
                'prenom' => 'Amadou',
                'cni' => '555666777',
                'telephone' => '784567890',
                'sexe' => 'M',
                'role' => 'utilisateur',
                'password' => Hash::make('user123'),
                'solde' => 750000, // 7500 FCFA
            ],
        ];

        foreach ($users as $userData) {
            $password = $userData['password'];
            unset($userData['password']);
            unset($userData['solde']);

            $user = User::create($userData);
            $user->password = $password;

            Compte::create([
                'user_id' => $user->id,
                'solde' => $userData['solde'] ?? 0,
                'type' => 'utilisateur',
            ]);
        }

        // Créer quelques marchands
        $marchands = [
            [
                'nom' => 'Boutique',
                'prenom' => 'Youssou',
                'cni' => 'M123456789',
                'telephone' => '785678901',
                'sexe' => 'M',
                'role' => 'marchand',
                'password' => Hash::make('marchand123'),
                'solde' => 1000000, // 10000 FCFA
            ],
            [
                'nom' => 'Restaurant',
                'prenom' => 'Fatou',
                'cni' => 'M987654321',
                'telephone' => '786789012',
                'sexe' => 'F',
                'role' => 'marchand',
                'password' => Hash::make('marchand123'),
                'solde' => 2000000, // 20000 FCFA
            ],
        ];

        foreach ($marchands as $marchandData) {
            $password = $marchandData['password'];
            unset($marchandData['password']);
            unset($marchandData['solde']);

            $marchand = User::create($marchandData);
            $marchand->password = $password;

            // Créer le compte
            Compte::create([
                'user_id' => $marchand->id,
                'solde' => $marchandData['solde'] ?? 0,
                'type' => 'marchand',
            ]);

            // Créer automatiquement le code marchand
            $codeMarchand = $this->generateUniqueMarchandCode();
            MarchandCode::create([
                'user_id' => $marchand->id,
                'code_marchand' => $codeMarchand,
                'actif' => true,
            ]);
        }

        // Créer quelques transactions de test
        $this->createTestTransactions();
    }

    /**
     * Générer un code marchand unique
     */
    private function generateUniqueMarchandCode()
    {
        do {
            $code = 'M' . rand(100000, 999999);
        } while (MarchandCode::where('code_marchand', $code)->exists());
        
        return $code;
    }

    /**
     * Créer des transactions de test
     */
    private function createTestTransactions()
    {
    $users = User::where('role', 'utilisateur')->get();
    $marchands = User::where('role', 'marchand')->get();

        if ($users->count() >= 2 && $marchands->count() >= 1) {
            $user1 = $users->first();
            $user2 = $users->skip(1)->first();
            $marchand = $marchands->first();

            // Transaction de transfert entre utilisateurs
            Transaction::create([
                'compte_source_id' => $user1->compte->id,
                'compte_dest_id' => $user2->compte->id,
                'montant' => 50000, // 500 FCFA
                'type' => 'transfert',
                'statut' => 'completed',
                'motif' => 'Test de transfert',
                'reference' => 'TRF_' . time() . '_001',
            ]);

            // Transactions de paiement vers marchands
            foreach ($users as $user) {
                Transaction::create([
                    'compte_source_id' => $user->compte->id,
                    'compte_dest_id' => $marchand->compte->id,
                    'montant' => 25000, // 250 FCFA
                    'type' => 'paiement',
                    'statut' => 'completed',
                    'motif' => 'Achat test chez ' . $marchand->nom . ' ' . $marchand->prenom,
                    'reference' => 'PAY_' . time() . '_' . rand(100, 999),
                ]);
            }
        }
    }
}
