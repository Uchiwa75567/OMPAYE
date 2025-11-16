-- Script SQL manuel pour OMPAYE - AdminUserSeeder (CORRIGÉ)
-- Exécution directe dans PostgreSQL avec les bonnes colonnes

-- Créer l'utilisateur administrateur
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (
    gen_random_uuid(),
    'Admin',
    'Système',
    'ADMIN001',
    '781111111',
    'M',
    'admin',
    '$2y$12$qbAlgPEdyovHl4orDLPBjeNFfGcTfZxI/VFeirIj4Gdj6PSx7t8rW', -- admin123
    NOW(),
    NOW()
);

-- Récupérer l'ID de l'admin pour créer son compte
WITH admin_user AS (
    SELECT id FROM users WHERE cni = 'ADMIN001' LIMIT 1
)
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 0, 'admin', NOW(), NOW()
FROM admin_user;

-- Créer quelques utilisateurs normaux
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at) VALUES
(gen_random_uuid(), 'Dupont', 'Jean', '123456789', '782345678', 'M', 'utilisateur', '$2y$12$qbAlgPEdyovHl4orDLPBjeNFfGcTfZxI/VFeirIj4Gdj6PSx7t8rW', NOW(), NOW()),
(gen_random_uuid(), 'Martin', 'Marie', '987654321', '783456789', 'F', 'utilisateur', '$2y$12$qbAlgPEdyovHl4orDLPBjeNFfGcTfZxI/VFeirIj4Gdj6PSx7t8rW', NOW(), NOW()),
(gen_random_uuid(), 'Dia', 'Amadou', '555666777', '784567890', 'M', 'utilisateur', '$2y$12$qbAlgPEdyovHl4orDLPBjeNFfGcTfZxI/VFeirIj4Gdj6PSx7t8rW', NOW(), NOW());

-- Créer les comptes pour les utilisateurs normaux
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), u.id, 
    CASE 
        WHEN u.nom = 'Dupont' THEN 500000
        WHEN u.nom = 'Martin' THEN 250000
        WHEN u.nom = 'Dia' THEN 750000
    END,
    'utilisateur',
    NOW(), NOW()
FROM users u 
WHERE u.cni IN ('123456789', '987654321', '555666777');

-- Créer quelques marchands
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at) VALUES
(gen_random_uuid(), 'Boutique', 'Youssou', 'M123456789', '785678901', 'M', 'marchand', '$2y$12$qbAlgPEdyovHl4orDLPBjeNFfGcTfZxI/VFeirIj4Gdj6PSx7t8rW', NOW(), NOW()),
(gen_random_uuid(), 'Restaurant', 'Fatou', 'M987654321', '786789012', 'F', 'marchand', '$2y$12$qbAlgPEdyovHl4orDLPBjeNFfGcTfZxI/VFeirIj4Gdj6PSx7t8rW', NOW(), NOW());

-- Créer les comptes pour les marchands
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), u.id, 
    CASE 
        WHEN u.nom = 'Boutique' THEN 1000000
        WHEN u.nom = 'Restaurant' THEN 2000000
    END,
    'marchand',
    NOW(), NOW()
FROM users u 
WHERE u.cni IN ('M123456789', 'M987654321');

-- Créer les codes marchands
INSERT INTO marchand_codes (id, user_id, code_marchand, actif, created_at, updated_at)
SELECT gen_random_uuid(), u.id, 'M' || LPAD((RANDOM() * 900000 + 100000)::INTEGER::TEXT, 6, '0'), true, NOW(), NOW()
FROM users u 
WHERE u.role = 'marchand';

-- Vérification
SELECT 'Utilisateurs créés: ' || (SELECT count(*) FROM users) as result
UNION ALL
SELECT 'Comptes créés: ' || (SELECT count(*) FROM comptes) as result
UNION ALL  
SELECT 'Codes marchands créés: ' || (SELECT count(*) FROM marchand_codes) as result;