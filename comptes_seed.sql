-- Script SQL pour créer les comptes (CORRIGÉ avec les bonnes contraintes)
-- Maintenant que les utilisateurs existent, créons leurs comptes

-- Créer le compte pour l'admin (utilisons 'client' pour l'admin)
WITH admin_user AS (
    SELECT id FROM users WHERE cni = 'ADMIN001' LIMIT 1
)
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), id, 0, 'client', NOW(), NOW()
FROM admin_user;

-- Créer les comptes pour les utilisateurs normaux (utilisons 'client')
INSERT INTO comptes (id, user_id, solde, type, created_at, updated_at)
SELECT gen_random_uuid(), u.id, 
    CASE 
        WHEN u.nom = 'Dupont' THEN 500000
        WHEN u.nom = 'Martin' THEN 250000
        WHEN u.nom = 'Dia' THEN 750000
    END,
    'client',
    NOW(), NOW()
FROM users u 
WHERE u.cni IN ('123456789', '987654321', '555666777');

-- Créer les comptes pour les marchands (utilisons 'marchand')
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

-- Vérification finale
SELECT 'Utilisateurs créés: ' || (SELECT count(*) FROM users) as result
UNION ALL
SELECT 'Comptes créés: ' || (SELECT count(*) FROM comptes) as result
UNION ALL  
SELECT 'Codes marchands créés: ' || (SELECT count(*) FROM marchand_codes) as result;