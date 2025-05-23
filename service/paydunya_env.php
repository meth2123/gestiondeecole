<?php
// Configuration de l'environnement PayDunya pour Render.com
// Les variables d'environnement peuvent être configurées dans le dashboard Render.com
// sous Settings > Environment Variables

// Récupération des variables d'environnement si définies sur Render.com
$base_url = getenv('APP_URL') ?: 'https://schoolmanager.sn';
$master_key = getenv('PAYDUNYA_MASTER_KEY') ?: 'J8Bk1t8t-AWZp-kVD1-WbjB-CndDy4hrVS7J';
$public_key = getenv('PAYDUNYA_PUBLIC_KEY') ?: 'live_public_sSLcfppVXgj8EPvJejPaJQ3p577';
$private_key = getenv('PAYDUNYA_PRIVATE_KEY') ?: 'live_private_c79m7kcs9viYYMKyDXTHPwLfjk0';
$token = getenv('PAYDUNYA_TOKEN') ?: 'DIjlzayBLdsFdtqYXZ2v';

// Configuration de base de PayDunya
$paydunya_config = [
    'mode' => 'live', // 'test' ou 'live'
    'api_keys' => [
        'master_key' => $master_key,
        'public_key' => $public_key,
        'private_key' => $private_key,
        'token' => $token
    ],
    'store' => [
        'name' => 'SchoolManager',
        'tagline' => 'Système de Gestion Scolaire',
        'postal_address' => 'Dakar, Sénégal',
        'phone_number' => '+221 77 807 25 70',
        'website_url' => $base_url,
        'logo_url' => $base_url . '/source/logo.jpg'
    ],
    'payment_methods' => [
        'orange-money-senegal' => true,
        'wave-senegal' => true,
        'free-money-senegal' => true
    ],
    'subscription' => [
        'amount' => 15000.00, // 15 000 FCFA
        'description' => 'Abonnement mensuel à SchoolManager - Système de Gestion Scolaire'
    ]
];

// Fonction pour obtenir le mode PayDunya
function getPayDunyaMode() {
    global $paydunya_config;
    return $paydunya_config['mode'] ?? 'live';
}

// Fonction pour obtenir les URLs de callback
function getPayDunyaUrls() {
    global $paydunya_config, $base_url;
    $mode = getPayDunyaMode();
    
    return [
        // URLs de l'API PayDunya
        'base_url' => $mode === 'live' 
            ? 'https://app.paydunya.com/api/v1' 
            : 'https://app.paydunya.com/sandbox-api/v1',
        'checkout_url' => $mode === 'live'
            ? 'https://app.paydunya.com/checkout'
            : 'https://app.paydunya.com/sandbox-checkout',
        
        // URLs de callback pour notre application
        'website_url' => $base_url,
        'ipn_url' => $base_url . '/service/paydunya_ipn.php',
        'callback_url' => $base_url . '/module/subscription/callback.php',
        'return_url' => $base_url . '/module/subscription/success.php',
        'cancel_url' => $base_url . '/module/subscription/cancel.php'
    ];
}

// Fonction pour obtenir les headers PayDunya
function getPayDunyaHeaders() {
    global $paydunya_config;
    return [
        'PAYDUNYA-MASTER-KEY: ' . ($paydunya_config['api_keys']['master_key'] ?? ''),
        'PAYDUNYA-PUBLIC-KEY: ' . ($paydunya_config['api_keys']['public_key'] ?? ''),
        'PAYDUNYA-PRIVATE-KEY: ' . ($paydunya_config['api_keys']['private_key'] ?? ''),
        'PAYDUNYA-TOKEN: ' . ($paydunya_config['api_keys']['token'] ?? '')
    ];
}

// Fonction pour obtenir les informations du store
function getPayDunyaStore() {
    global $paydunya_config;
    return $paydunya_config['store'] ?? [
        'name' => 'SchoolManager',
        'tagline' => 'Système de Gestion Scolaire',
        'postal_address' => 'Dakar, Sénégal',
        'phone_number' => '+221 77 807 25 70',
        'website_url' => 'https://schoolmanager.sn',
        'logo_url' => 'https://schoolmanager.sn/source/logo.jpg'
    ];
}

// Log de la configuration
error_log("Configuration PayDunya chargée - Mode: " . getPayDunyaMode());
error_log("Base URL: " . $base_url);
error_log("Callback URL: " . $base_url . "/module/subscription/callback.php");

// Vérification de la sécurité
if (strpos($base_url, 'https://') !== 0) {
    error_log("ATTENTION: L'URL de base doit utiliser HTTPS pour PayDunya");
}

// Ne pas retourner la configuration directement
// Les fonctions ci-dessus doivent être utilisées pour accéder aux valeurs 