<?php

/**
 * Test simple pour diagnostiquer les problèmes
 */

echo "🔍 Test de diagnostic de l'API OMPAY\n";
echo "=====================================\n\n";

// Test 1: Vérifier que le serveur répond
echo "1. Test de connectivité serveur:\n";
$result = shell_exec('curl -s -w "HTTP_CODE:%{http_code}" http://localhost:8000/api/user 2>/dev/null');
if (strpos($result, 'HTTP_CODE:401') !== false) {
    echo "✅ Serveur répond correctement (401 attendu pour /user sans auth)\n";
} else {
    echo "❌ Serveur ne répond pas correctement\n";
    echo "Réponse: $result\n";
}

// Test 2: Vérifier la base de données
echo "\n2. Test de la base de données:\n";
$result = shell_exec('php artisan tinker --execute="echo \'Users: \' . App\\\Models\\\User::count() . PHP_EOL; echo \'Comptes: \' . App\\\Models\\\Compte::count() . PHP_EOL;" 2>/dev/null');
if ($result) {
    echo "✅ Base de données accessible\n";
    echo "Données: $result";
} else {
    echo "❌ Problème d'accès à la base de données\n";
}

// Test 3: Test manuel d'un endpoint simple
echo "\n3. Test manuel d'endpoint:\n";
$result = shell_exec('curl -s -X POST http://localhost:8000/api/ompay/login -H "Content-Type: application/json" -d \'{"telephone":"772345678","password":"password"}\' 2>/dev/null');
$data = json_decode($result, true);
if ($data && isset($data['success'])) {
    if ($data['success']) {
        echo "✅ Connexion réussie\n";
    } else {
        echo "❌ Connexion échouée: " . ($data['message'] ?? 'Erreur inconnue') . "\n";
    }
} else {
    echo "❌ Réponse malformée\n";
    echo "Raw: $result\n";
}

echo "\n=====================================\n";
echo "🏁 Diagnostic terminé\n";

?>