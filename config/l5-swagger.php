<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Orange Money API',
                'version' => '1.0.0',
                'description' => "# Orange Money API - Senegal 🟠\n\n## 📱 **Application de Paiement Mobile Orange**\n\nCette API fournit un système complet de paiement mobile inspiré d'Orange Money pour le marché sénégalais. Elle permet aux utilisateurs de gérer leurs comptes, effectuer des transactions, et utiliser les services de paiement via QR codes.\n\n## 🏗️ **Architecture du Système**\n\n### **Types d'Utilisateurs**\n- **👤 Clients** : Utilisateurs finaux avec numéros Orange (77xxxxxxx/78xxxxxxx)\n- **🏪 Marchands** : Commerçants pour paiements QR codes\n- **👥 Distributeurs** : Agents pour dépôts/retraits en espèces\n- **👑 Administrateurs** : Gestion du système\n\n### **Fonctionnalités Principales**\n- ✅ **Auto-registration** : Inscription automatique avec numéros Orange\n- ✅ **Authentification SMS** : Code par SMS avec fallbacks multi-providers\n- ✅ **Gestion Comptes** : Solde, historique, transactions\n- ✅ **Transactions** : Dépôt, retrait, transfert, paiement QR\n- ✅ **JWT Tokens** : Authentification sécurisée Bearer\n- ✅ **PIN Sécurité** : Système de codes PIN pour transactions sensibles\n\n## 🔐 **Authentification**\n\n**🔒 CADENAS EN HAUT** : Cliquez pour entrer votre token Bearer\n**Header requis** : `Authorization: Bearer YOUR_JWT_TOKEN`\n\n## 📊 **Système d'Unités**\n- **💰 Unité** : XOF (Franc CFA)\n- **📝 Exemple** : 5000 = 5000 XOF\n- **🗄️ Stockage** : Automatiquement converti en centimes pour la BDD\n\n## 🧪 **Valeurs de Test**\n\n### **Agent Distributeur**\n- **ID** : `a056e54a-4828-4160-a97c-9ab67a7e9116`\n- **Nom** : Diallo Ali\n- **Téléphone** : 789876543\n- **Rôle** : distributeur\n- **PIN** : 1234\n\n### **Utilisateur Test**\n- **Téléphone** : 785052217\n- **Auto-enregistrement** : Possible via SMS\n- **Code SMS** : Affiché dans la réponse (mode simulation)\n\n## 🏃‍♂️ **Guide de Démarrage Rapide**\n\n1. **Connexion** : `POST /api/auth/login` avec numéro Orange\n2. **Vérification** : `POST /api/auth/verify-sms` avec code reçu\n3. **Token JWT** : Utiliser le token pour les appels suivants\n4. **Dépôt** : `POST /api/transactions/depot` avec agent_id\n5. **Solde** : `GET /api/compte` pour voir le solde\n6. **Historique** : `GET /api/historique` pour voir les transactions\n\n## 📋 **Endpoints Principaux**\n\n### **🔐 Authentification**\n- `POST /api/auth/login` - Demande code SMS\n- `POST /api/auth/verify-sms` - Vérification et token\n- `POST /api/auth/set-pin` - Définir PIN sécurité\n\n### **💰 Transactions**\n- `POST /api/transactions/depot` - Dépôt avec agent\n- `POST /api/transactions/retrait` - Retrait avec agent + PIN\n- `POST /api/transactions/transfert` - Transfert vers autre utilisateur\n- `POST /api/transactions/paiement` - Paiement QR code\n\n### **👤 Gestion Compte**\n- `GET /api/compte` - Solde et type de compte\n- `GET /api/historique` - Historique des transactions\n\n## 🌐 **URLs de Base**\n- **API** : http://localhost:8083\n- **Documentation** : http://localhost:8083/api/documentation\n\n---\n*Développé pour Orange Money Sénégal 🇸🇳*",
                'contact' => [
                    'name' => 'OM Paye',
                    'email' => 'support@ompaye.com'
                ]
            ],
            'routes' => [
                'api' => 'enabled',
                'docs' => 'disabled',
                'oauth2_callback' => 'api/oauth2-callback',
            ],
            'paths' => [
                'docs_json' => 'api-docs.json',
                'docs_yaml' => null,
                'annotations' => [
                    app_path('Http/Controllers/Api'),
                ],
            ],
            'generate_always' => false,
            'swagger_version' => '3.0',
            'proxy' => false,
            'additional_config_url' => null,
            'operations_sort' => null,
            'validator_url' => null,
            'ui' => [
                'display' => [
                    'dark_mode' => false,
                    'doc_expansion' => 'none',
                    'filter' => true,
                ],
                'authorization' => [
                    'persist_authorization' => true,
                    'oauth2' => [
                        'use_pkce_with_authorization_code_grant' => false,
                    ],
                    'basic' => [
                        'display' => false,
                        'basic_authentication_button_text' => 'Authentification Básicaire',
                        'basic_authentication_button_color' => 'violet',
                    ],
                    'bearer' => [
                        'display' => true,
                        'bearer_authentication_button_text' => 'Bearer Token',
                        'bearer_authentication_button_color' => 'green',
                    ]
                ],
                'urls' => [
                    'api_json' => '/api-docs.json',
                ]
            ],
            'constants' => [
                'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost:8081'),
            ],
            'scanOptions' => [
                'default_processors_configuration' => [],
                'analyser' => null,
                'analysis' => null,
                'processors' => [],
                'pattern' => null,
                'exclude' => [],
                'open_api_spec_version' => '3.0.0',
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            'docs' => 'disabled',
            'oauth2_callback' => 'disabled',
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
            'group_options' => [],
        ],
        'paths' => [
            'docs' => public_path(),
            'excludes' => [],
            'base' => env('L5_SWAGGER_BASE_PATH', null),
            'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
            'annotations' => [],
            'docs_json' => 'api-docs.json',
            'docs_yaml' => null,
            'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
            'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', false),
        ],
        'generate_always' => false,
        'generate_yaml_copy' => false,
        'swagger_version' => '3.0',
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => null,
        'validator_url' => null,
        'ui' => [
            'display' => [
                'dark_mode' => false,
                'doc_expansion' => 'none',
                'filter' => true,
            ],
            'authorization' => [
                'persist_authorization' => false,
                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
        ],
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', env('APP_URL', 'http://localhost:8081')),
        ],
        'scanOptions' => [
            'default_processors_configuration' => [],
            'analyser' => null,
            'analysis' => null,
            'processors' => [],
            'pattern' => null,
            'exclude' => [],
            'open_api_spec_version' => '3.0.0',
        ],
    ],
];
