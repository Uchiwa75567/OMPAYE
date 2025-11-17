-- Script SQL pour ajouter utilisateur 761111111 avec solde 10000
-- Insertion dans la table users
INSERT INTO users (id, nom, prenom, cni, telephone, sexe, role, password, created_at, updated_at)
VALUES (
    gen_random_uuid(),
    'TestUser',
    'Solde10K',
    'TEST761111111',
    '761111111',
    'M',
    'client',
    '$2y$12$qbAlgPEdyovHl4orDLPBjeNFfGcTfZxI/VFeirIj4Gdj6PSx7t8rW', -- password123
    NOW(),
    NOW()
);

-- Récupérer l'ID de l'utilisateur et créer son compte avec solde 10000
WITH user_inserted AS (
    SELECT id FROM users WHERE cni = 'TEST761111111' LIMIT 1
)
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 10000, 'client', NOW(), NOW()
FROM user_inserted;

-- Afficher confirmation
SELECT 'Utilisateur ajouté: ' || (SELECT COUNT(*) FROM users WHERE telephone = '761111111') as result;
