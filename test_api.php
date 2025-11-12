<?php

/**
 * Script de test complet de l'API OMPAY
 * Teste tous les endpoints de façon systématique
 */

class ApiTester
{
    private $baseUrl = 'http://localhost:8000/api';
    private $tokens = [];

    public function __construct()
    {
        echo "🚀 Démarrage des tests API OMPAY\n";
        echo "=====================================\n\n";
    }

    private function makeRequest($method, $endpoint, $data = null, $headers = [])
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        return [
            'response' => $response,
            'http_code' => $httpCode,
            'error' => $error
        ];
    }

    private function logTest($testName, $method, $endpoint, $data = null, $expectedCode = null)
    {
        echo "🧪 Test: $testName\n";
        echo "📡 $method $endpoint\n";

        if ($data) {
            echo "📤 Body: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }

        $result = $this->makeRequest($method, $endpoint, $data);

        echo "📥 Status: {$result['http_code']}\n";

        if ($result['error']) {
            echo "❌ Error: {$result['error']}\n";
            return false;
        }

        $responseData = json_decode($result['response'], true);

        if ($expectedCode && $result['http_code'] !== $expectedCode) {
            echo "❌ FAIL - Expected $expectedCode, got {$result['http_code']}\n";
            echo "📄 Response: " . substr($result['response'], 0, 200) . "...\n";
            return false;
        }

        echo "✅ PASS\n";

        if ($responseData && isset($responseData['data']['token'])) {
            $this->tokens[$testName] = $responseData['data']['token'];
            echo "🔑 Token saved: " . substr($responseData['data']['token'], 0, 20) . "...\n";
        }

        echo "\n";
        return $responseData;
    }

    public function runAllTests()
    {
        // 1. Tests d'authentification classiques
        $this->testAuthEndpoints();

        // 2. Tests des endpoints utilisateurs
        $this->testUserEndpoints();

        // 3. Tests des endpoints comptes
        $this->testAccountEndpoints();

        // 4. Tests du module OMPAY
        $this->testOmpayEndpoints();

        echo "🎉 Tests terminés!\n";
    }

    private function testAuthEndpoints()
    {
        echo "🔐 TESTS AUTHENTIFICATION CLASSIQUE\n";
        echo "===================================\n";

        // Test login avec données invalides
        $this->logTest(
            "Login invalide",
            "POST",
            "/auth/login",
            ["login" => "invalid", "password" => "invalid"],
            401
        );

        // Test login avec données valides (utiliser un user existant)
        $loginResult = $this->logTest(
            "Login valide",
            "POST",
            "/auth/login",
            ["login" => "776543210", "password" => "password"],
            200
        );

        if ($loginResult && isset($loginResult['data']['token'])) {
            $token = $loginResult['data']['token'];

            // Test accès à endpoint protégé
            $this->logTest(
                "Accès endpoint protégé",
                "GET",
                "/user",
                null,
                200,
                ["Authorization: Bearer $token"]
            );

            // Test logout
            $this->logTest(
                "Logout",
                "POST",
                "/auth/logout",
                null,
                200,
                ["Authorization: Bearer $token"]
            );

            // Test accès après logout (devrait échouer)
            $this->logTest(
                "Accès après logout",
                "GET",
                "/user",
                null,
                401,
                ["Authorization: Bearer $token"]
            );
        }
    }

    private function testUserEndpoints()
    {
        echo "👥 TESTS ENDPOINTS UTILISATEURS\n";
        echo "===============================\n";

        // Test accès sans authentification
        $this->logTest(
            "Liste users sans auth",
            "GET",
            "/v1/users",
            null,
            401
        );

        // Login admin d'abord (si disponible)
        // Pour l'instant, test avec un user normal
        $loginResult = $this->logTest(
            "Login pour tests users",
            "POST",
            "/auth/login",
            ["login" => "776543210", "password" => "password"],
            200
        );

        if ($loginResult && isset($loginResult['data']['token'])) {
            $token = $loginResult['data']['token'];

            // Test liste users (devrait échouer car pas admin)
            $this->logTest(
                "Liste users avec user normal",
                "GET",
                "/v1/users",
                null,
                403,
                ["Authorization: Bearer $token"]
            );
        }
    }

    private function testAccountEndpoints()
    {
        echo "🏦 TESTS ENDPOINTS COMPTES\n";
        echo "==========================\n";

        // Login d'abord
        $loginResult = $this->logTest(
            "Login pour tests comptes",
            "POST",
            "/auth/login",
            ["login" => "776543210", "password" => "password123"],
            200
        );

        if ($loginResult && isset($loginResult['data']['token'])) {
            $token = $loginResult['data']['token'];

            // Test liste comptes
            $this->logTest(
                "Liste comptes",
                "GET",
                "/v1/comptes",
                null,
                200,
                ["Authorization: Bearer $token"]
            );
        }
    }

    private function testOmpayEndpoints()
    {
        echo "🟠 TESTS MODULE OMPAY\n";
        echo "=====================\n";

        // 1. Test envoi OTP
        $otpResult = $this->logTest(
            "Envoi OTP",
            "POST",
            "/ompay/send-verification",
            ["telephone" => "771234567"],
            200
        );

        // 2. Récupérer l'OTP depuis les logs Laravel
        $otpCode = $this->getOtpFromLogs("771234567");

        if ($otpCode) {
            echo "🔍 OTP récupéré depuis DB: $otpCode\n";

            // 3. Test inscription avec OTP valide
            $registerResult = $this->logTest(
                "Inscription avec OTP valide",
                "POST",
                "/ompay/register",
                [
                    "telephone" => "771234567",
                    "otp" => $otpCode,
                    "nom" => "TEST",
                    "prenom" => "API",
                    "password" => "password123",
                    "password_confirmation" => "password123",
                    "cni" => "1234567890123",
                    "sexe" => "M",
                    "date_naissance" => "1990-01-01"
                ],
                200
            );

            if ($registerResult && isset($registerResult['data']['token'])) {
                $ompayToken = $registerResult['data']['token'];

                // 4. Test login OMPAY
                $this->logTest(
                    "Login OMPAY",
                    "POST",
                    "/ompay/login",
                    ["telephone" => "771234567", "password" => "password123"],
                    200
                );

                // 5. Test récupération solde
                $this->logTest(
                    "Récupération solde",
                    "GET",
                    "/ompay/wallet/balance",
                    null,
                    200,
                    ["Authorization: Bearer $ompayToken"]
                );

                // 6. Test historique (vide au début)
                $this->logTest(
                    "Historique transactions",
                    "GET",
                    "/ompay/wallet/history",
                    null,
                    200,
                    ["Authorization: Bearer $ompayToken"]
                );

                // 7. Test transfert (devrait échouer car solde insuffisant)
                $this->logTest(
                    "Transfert avec solde insuffisant",
                    "POST",
                    "/ompay/wallet/transfer",
                    [
                        "recipient_telephone" => "776543210",
                        "amount" => 100000
                    ],
                    400,
                    ["Authorization: Bearer $ompayToken"]
                );

                // 8. Test logout OMPAY
                $this->logTest(
                    "Logout OMPAY",
                    "POST",
                    "/ompay/logout",
                    null,
                    200,
                    ["Authorization: Bearer $ompayToken"]
                );
            }

            // 9. Test inscription avec OTP invalide
            $this->logTest(
                "Inscription avec OTP invalide",
                "POST",
                "/ompay/register",
                [
                    "telephone" => "771234567",
                    "otp" => "000000",
                    "nom" => "TEST",
                    "prenom" => "API",
                    "password" => "password123",
                    "password_confirmation" => "password123",
                    "cni" => "1234567890123",
                    "sexe" => "M",
                    "date_naissance" => "1990-01-01"
                ],
                400
            );

        } else {
            echo "❌ Impossible de récupérer l'OTP depuis la base de données\n";
        }
    }

    private function getOtpFromDatabase($telephone)
    {
        // Lire l'OTP depuis les logs Laravel (simulation SMS)
        $logFile = '/home/alassane/Bureau/teamLaravel/storage/logs/laravel.log';
        if (file_exists($logFile)) {
            $logs = file($logFile);
            foreach (array_reverse($logs) as $line) {
                if (strpos($line, 'SMS OMPAY') !== false && strpos($line, $telephone) !== false) {
                    // Extraire l'OTP du message JSON
                    if (preg_match('/"message":"Votre code de vérification OMPAY est : (\d{6})"/', $line, $matches)) {
                        return $matches[1];
                    }
                }
            }
        }
        return null;
    }
}

// Exécuter les tests
$tester = new ApiTester();
$tester->runAllTests();