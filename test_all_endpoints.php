<?php

/**
 * Script de test complet pour tous les endpoints OMPAY API
 * Teste tous les endpoints documentés dans le Swagger
 */

class ApiTester
{
    private string $baseUrl = 'http://localhost:8000/api';
    private array $tokens = [];
    private array $testResults = [];

    public function __construct()
    {
        echo "🚀 Démarrage des tests complets de l'API OMPAY\n";
        echo "📍 Base URL: {$this->baseUrl}\n";
        echo str_repeat("=", 60) . "\n\n";
    }

    private function log(string $message, string $status = 'INFO'): void
    {
        $timestamp = date('H:i:s');
        $statusIcon = match($status) {
            'SUCCESS' => '✅',
            'ERROR' => '❌',
            'WARNING' => '⚠️',
            default => 'ℹ️'
        };

        echo "[{$timestamp}] {$statusIcon} {$message}\n";
    }

    private function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error,
            'data' => json_decode($response, true)
        ];
    }

    private function testEndpoint(string $name, string $method, string $endpoint, array $data = [], array $headers = [], int $expectedCode = 200): bool
    {
        echo "\n🧪 Test: {$name}\n";
        echo "📍 {$method} {$endpoint}\n";

        if (!empty($data)) {
            echo "📤 Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }

        $result = $this->makeRequest($method, $endpoint, $data, $headers);

        $this->testResults[] = [
            'name' => $name,
            'method' => $method,
            'endpoint' => $endpoint,
            'expected_code' => $expectedCode,
            'actual_code' => $result['http_code'],
            'success' => $result['success'] && $result['http_code'] === $expectedCode,
            'response' => $result['data'],
            'error' => $result['error']
        ];

        if ($result['success'] && $result['http_code'] === $expectedCode) {
            $this->log("✅ {$name} - SUCCESS ({$result['http_code']})", 'SUCCESS');
            return true;
        } else {
            $this->log("❌ {$name} - FAILED (Expected: {$expectedCode}, Got: {$result['http_code']})", 'ERROR');
            if ($result['error']) {
                $this->log("Erreur cURL: {$result['error']}", 'ERROR');
            }
            if ($result['data'] && isset($result['data']['message'])) {
                $this->log("Message API: {$result['data']['message']}", 'WARNING');
            }
            return false;
        }
    }

    public function runAllTests(): void
    {
        // Test 1: Vérification endpoint de base
        $this->testEndpoint("Endpoint de base Laravel", "GET", "/user", [], [], 401); // Devrait être 401 sans auth

        // Test 2: Connexion OMPAY avec utilisateur existant
        $loginResult = $this->testEndpoint(
            "Connexion OMPAY",
            "POST",
            "/ompay/login",
            [
                "telephone" => "772345678", // Utilisateur seeder
                "password" => "password"
            ]
        );

        if ($loginResult && isset($this->testResults[count($this->testResults)-1]['response']['data']['token'])) {
            $this->tokens['ompay'] = $this->testResults[count($this->testResults)-1]['response']['data']['token'];
            $this->log("Token OMPAY mis à jour", 'SUCCESS');
        }

        // Tests avec authentification OMPAY
        if (isset($this->tokens['ompay'])) {
            $authHeaders = ["Authorization: Bearer {$this->tokens['ompay']}"];

            // Test 5: Consultation du solde
            $this->testEndpoint(
                "Consultation solde",
                "GET",
                "/ompay/balance/550e8400-e29b-41d4-a716-446655440000",
                [],
                $authHeaders,
                404 // Compte probablement inexistant
            );

            // Test 6: Dépôt d'argent
            $this->testEndpoint(
                "Dépôt d'argent",
                "POST",
                "/ompay/deposit",
                [
                    "amount" => 50000,
                    "description" => "Test deposit"
                ],
                $authHeaders
            );

            // Test 7: Retrait d'argent
            $this->testEndpoint(
                "Retrait d'argent",
                "POST",
                "/ompay/withdraw",
                [
                    "amount" => 25000,
                    "description" => "Test withdrawal"
                ],
                $authHeaders
            );

            // Test 8: Transfert d'argent
            $this->testEndpoint(
                "Transfert d'argent",
                "POST",
                "/ompay/transfer",
                [
                    "recipient_telephone" => "+221781234567",
                    "amount" => 15000,
                    "description" => "Test transfer"
                ],
                $authHeaders,
                400 // Destinataire probablement inexistant
            );

            // Test 9: Historique des transactions
            $this->testEndpoint(
                "Historique transactions",
                "GET",
                "/ompay/transactions/550e8400-e29b-41d4-a716-446655440000",
                [],
                $authHeaders,
                404 // Compte probablement inexistant
            );

            // Test 10: Déconnexion OMPAY
            $this->testEndpoint(
                "Déconnexion OMPAY",
                "POST",
                "/ompay/logout",
                [],
                $authHeaders
            );
        }

        // Test 11: Connexion administrateur
        $adminLoginResult = $this->testEndpoint(
            "Connexion administrateur",
            "POST",
            "/auth/login",
            [
                "telephone" => "771234567", // Admin seeder
                "password" => "password"
            ]
        );

        if ($adminLoginResult && isset($this->testResults[count($this->testResults)-1]['response']['data']['token'])) {
            $this->tokens['admin'] = $this->testResults[count($this->testResults)-1]['response']['data']['token'];
            $this->log("Token Admin sauvegardé", 'SUCCESS');
        }

        // Tests administrateur
        if (isset($this->tokens['admin'])) {
            $adminHeaders = ["Authorization: Bearer {$this->tokens['admin']}"];

            // Test 12: Dashboard administrateur
            $this->testEndpoint(
                "Dashboard admin",
                "GET",
                "/v1/admin/dashboard",
                [],
                $adminHeaders
            );

            // Test 13: Liste des utilisateurs
            $this->testEndpoint(
                "Liste utilisateurs",
                "GET",
                "/v1/users",
                [],
                $adminHeaders
            );

            // Test 14: Création d'utilisateur
            $this->testEndpoint(
                "Création utilisateur",
                "POST",
                "/v1/users",
                [
                    "nom" => "Admin",
                    "prenom" => "Test",
                    "telephone" => "+221791234567",
                    "email" => "admin.test@example.com",
                    "password" => "password123",
                    "role" => "Client"
                ],
                $adminHeaders
            );

            // Test 15: Liste des comptes
            $this->testEndpoint(
                "Liste comptes",
                "GET",
                "/v1/comptes",
                [],
                $adminHeaders
            );

            // Test 16: Création de compte
            $this->testEndpoint(
                "Création compte",
                "POST",
                "/v1/comptes",
                [
                    "type" => "cheque",
                    "soldeInitial" => 100000,
                    "devise" => "FCFA",
                    "solde" => 100000,
                    "client" => [
                        "titulaire" => "Test Client",
                        "nci" => "9876543210987",
                        "email" => "test.client@example.com",
                        "telephone" => "+221761234567",
                        "adresse" => "Dakar, Sénégal"
                    ]
                ],
                $adminHeaders
            );

            // Test 17: Déconnexion admin
            $this->testEndpoint(
                "Déconnexion admin",
                "POST",
                "/auth/logout",
                [],
                $adminHeaders
            );
        }

        // Test 18: Token OAuth (sans données valides)
        $this->testEndpoint(
            "Token OAuth",
            "POST",
            "/oauth/token",
            [
                "grant_type" => "password",
                "client_id" => "1",
                "client_secret" => "test",
                "username" => "test",
                "password" => "test"
            ],
            [],
            401 // Devrait échouer sans credentials valides
        );

        $this->generateReport();
    }

    private function generateReport(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 RAPPORT DE TEST COMPLET\n";
        echo str_repeat("=", 60) . "\n";

        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($test) => $test['success']));
        $failedTests = $totalTests - $passedTests;

        echo "📈 Statistiques générales:\n";
        echo "   • Total des tests: {$totalTests}\n";
        echo "   • Tests réussis: {$passedTests}\n";
        echo "   • Tests échoués: {$failedTests}\n";
        echo "   • Taux de succès: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";

        echo "📋 Détail des tests:\n";
        foreach ($this->testResults as $i => $test) {
            $status = $test['success'] ? '✅ PASS' : '❌ FAIL';
            $code = $test['actual_code'];
            $expected = $test['expected_code'];

            echo "   " . ($i + 1) . ". {$test['name']} - {$status}";
            if (!$test['success']) {
                echo " (Attendu: {$expected}, Reçu: {$code})";
            }
            echo "\n";
        }

        echo "\n🎯 Résumé:\n";
        if ($failedTests === 0) {
            echo "   ✅ Tous les tests sont passés ! L'API fonctionne parfaitement.\n";
        } elseif ($passedTests > $failedTests) {
            echo "   ⚠️ La plupart des tests sont passés. Quelques ajustements nécessaires.\n";
        } else {
            echo "   ❌ Plusieurs tests ont échoué. Vérifications requises.\n";
        }

        echo "\n💡 Recommandations:\n";
        echo "   • Vérifiez que la base de données est migrée et seedée\n";
        echo "   • Assurez-vous que les services (Redis, etc.) sont démarrés\n";
        echo "   • Consultez les logs Laravel pour plus de détails\n";
        echo "   • Utilisez Swagger UI pour tester interactivement\n";

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🏁 Tests terminés à " . date('H:i:s') . "\n";
    }
}

// Exécution des tests
$tester = new ApiTester();
$tester->runAllTests();

?>