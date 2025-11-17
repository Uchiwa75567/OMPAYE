-- Script SQL pour créer des marchands avec codes marchands
-- et des clients

-- ===== CRÉER DES MARCHANDS =====

-- Marchand 1: Boutique Orange
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Boutique Orange', 'Dakar', 'MARCHAND_001', '781234560', 'M', 'marchand', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_001')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 500000, 'marchand', NOW(), NOW() FROM marchand;

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_001')
INSERT INTO marchand_codes (id, user_id, code_marchand, actif, created_at, updated_at)
SELECT gen_random_uuid(), id, 'MBO001', true, NOW(), NOW() FROM marchand;

-- Marchand 2: Restaurant Thieboudienne
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Restaurant Thieboudienne', 'Thies', 'MARCHAND_002', '771234560', 'M', 'marchand', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_002')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 300000, 'marchand', NOW(), NOW() FROM marchand;

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_002')
INSERT INTO marchand_codes (id, user_id, code_marchand, actif, created_at, updated_at)
SELECT gen_random_uuid(), id, 'MRT002', true, NOW(), NOW() FROM marchand;

-- Marchand 3: Pharmacie Centrale
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Pharmacie Centrale', 'Rufisque', 'MARCHAND_003', '781234561', 'M', 'marchand', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_003')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 200000, 'marchand', NOW(), NOW() FROM marchand;

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_003')
INSERT INTO marchand_codes (id, user_id, code_marchand, actif, created_at, updated_at)
SELECT gen_random_uuid(), id, 'MPC003', true, NOW(), NOW() FROM marchand;

-- Marchand 4: Supermarché Carrefour
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Supermarché Carrefour', 'Kaolack', 'MARCHAND_004', '771234561', 'M', 'marchand', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_004')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 750000, 'marchand', NOW(), NOW() FROM marchand;

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_004')
INSERT INTO marchand_codes (id, user_id, code_marchand, actif, created_at, updated_at)
SELECT gen_random_uuid(), id, 'MSC004', true, NOW(), NOW() FROM marchand;

-- Marchand 5: Station Essence Shell
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Station Essence Shell', 'Kolda', 'MARCHAND_005', '781234562', 'M', 'marchand', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_005')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 1000000, 'marchand', NOW(), NOW() FROM marchand;

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_005')
INSERT INTO marchand_codes (id, user_id, code_marchand, actif, created_at, updated_at)
SELECT gen_random_uuid(), id, 'MSE005', true, NOW(), NOW() FROM marchand;

-- Marchand 6: Hôtel Le Sine
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Hôtel Le Sine', 'Kaolack', 'MARCHAND_006', '771234562', 'M', 'marchand', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_006')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 600000, 'marchand', NOW(), NOW() FROM marchand;

WITH marchand AS (SELECT id FROM users WHERE cni = 'MARCHAND_006')
INSERT INTO marchand_codes (id, user_id, code_marchand, actif, created_at, updated_at)
SELECT gen_random_uuid(), id, 'MHS006', true, NOW(), NOW() FROM marchand;

-- ===== CRÉER DES CLIENTS =====

-- Client 1
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Diallo', 'Amadou', 'CLIENT_001', '781234563', 'M', 'client', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH client AS (SELECT id FROM users WHERE cni = 'CLIENT_001')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 150000, 'client', NOW(), NOW() FROM client;

-- Client 2
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Ba', 'Fatou', 'CLIENT_002', '771234563', 'F', 'client', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH client AS (SELECT id FROM users WHERE cni = 'CLIENT_002')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 200000, 'client', NOW(), NOW() FROM client;

-- Client 3
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Traore', 'Moussa', 'CLIENT_003', '781234564', 'M', 'client', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH client AS (SELECT id FROM users WHERE cni = 'CLIENT_003')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 100000, 'client', NOW(), NOW() FROM client;

-- Client 4
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Sow', 'Aissatou', 'CLIENT_004', '771234564', 'F', 'client', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH client AS (SELECT id FROM users WHERE cni = 'CLIENT_004')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 250000, 'client', NOW(), NOW() FROM client;

-- Client 5
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Toure', 'Ismaïl', 'CLIENT_005', '781234565', 'M', 'client', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH client AS (SELECT id FROM users WHERE cni = 'CLIENT_005')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 300000, 'client', NOW(), NOW() FROM client;

-- Client 6
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Diop', 'Aminata', 'CLIENT_006', '771234565', 'F', 'client', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH client AS (SELECT id FROM users WHERE cni = 'CLIENT_006')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 175000, 'client', NOW(), NOW() FROM client;

-- Client 7
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Ndiaye', 'Cheikh', 'CLIENT_007', '781234566', 'M', 'client', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH client AS (SELECT id FROM users WHERE cni = 'CLIENT_007')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 500000, 'client', NOW(), NOW() FROM client;

-- Client 8
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (gen_random_uuid(), 'Sarr', 'Khady', 'CLIENT_008', '771234566', 'F', 'client', '$2y$12$PL0Gi66k0Nz0o806O5w8lOPVp2TtQ7BTUkUGbhXKsMORhQvsPcIa6', NOW(), NOW());

WITH client AS (SELECT id FROM users WHERE cni = 'CLIENT_008')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 225000, 'client', NOW(), NOW() FROM client;

-- Afficher le résumé
SELECT '=== RÉSUMÉ ===' as info;
SELECT COUNT(*) as total_marchands, 'Marchands créés' as type FROM users WHERE role = 'marchand' AND cni LIKE 'MARCHAND_%'
UNION ALL
SELECT COUNT(*) as total_clients, 'Clients créés' as type FROM users WHERE role = 'client' AND cni LIKE 'CLIENT_%'
UNION ALL
SELECT COUNT(*) as total_codes, 'Codes marchands créés' as type FROM marchand_codes;

-- Afficher les marchands avec leurs codes
SELECT '=== MARCHANDS AVEC CODES ===' as info;
SELECT u.telephone, u.nom, c.solde, mc.code_marchand FROM users u 
LEFT JOIN comptes c ON u.id = c.user_id 
LEFT JOIN marchand_codes mc ON u.id = mc.user_id 
WHERE u.role = 'marchand' AND u.cni LIKE 'MARCHAND_%'
ORDER BY u.telephone;
