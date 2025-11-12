<?php

/**
 * 🚀 SCRIPT DE TEST AUTOMATISÉ - MODULE OMPAY
 *
 * Ce script teste automatiquement tous les endpoints OMPAY :
 * - Envoi OTP (SMS simulé)
 * - Inscription avec validation OTP
 * - Authentification
 * - Wallet (solde, transferts, historique)
 * - Déconnexion
 *
 * Usage: php test_ompay_automated.php
 */

class OmpayAutomatedTester
{
    private $baseUrl = 'http://localhost:8000/api';
    private $testPhone;
    private $testPassword = 'TestPass123!';
    private $otpCode = null;
    private $authToken = null;

    public function __construct()
    {
        $this->testPhone = '77' . rand(1000000, 9999999); // Numéro unique généré

        echo "🚀 TEST AUTOMATISÉ MODULE OMPAY\n";
        echo "===============================\n";
        echo "📱 Téléphone de test: {$this->testPhone}\n";
        echo "🔑 Mot de passe de test: {$this->testPassword}\n\n";
    }

    public function runAllTests()
    {
        $tests = [
            'sendOtp' => '📤 Envoi OTP',
            'register' => '📝 Inscription avec OTP',
            'login' => '🔓 Connexion OMPAY',
            'getBalance' => '💰 Consultation solde',
            'transfer' => '💸 Tentative de transfert',
            'getHistory' => '📊 Historique transactions',
            'logout' => '🔒 Déconnexion'
        ];

        $results = [];

        foreach ($tests as $method => $description) {
            echo "🧪 $description...\n";
            $result = $this->$method();
            $results[$method] = $result;

            if ($result['success']) {
                echo "   ✅ SUCCÈS\n";
                if (isset($result['data'])) {
                    echo "   📄 " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
                }
            } else {
                echo "   ❌ ÉCHEC: {$result['error']}\n";
                if (isset($result['response'])) {
                    echo "   📄 Réponse: " . substr($result['response'], 0, 100) . "...\n";
                }
            }
            echo "\n";
        }

        $this->printSummary($results);
    }

    private function sendOtp()
    {
        $response = $this->makeRequest('POST', '/ompay/send-verification', [
            'telephone' => $this->testPhone
        ]);

        if ($response['http_code'] === 200) {
            // Récupérer l'OTP depuis les logs
            $this->otpCode = $this->extractOtpFromLogs($this->testPhone);
            return [
                'success' => true,
                'data' => ['otp_sent' => true, 'otp_code' => $this->otpCode]
            ];
        }

        return [
            'success' => false,
            'error' => 'Échec envoi OTP',
            'response' => $response['response']
        ];
    }

    private function register()
    {
        if (!$this->otpCode) {
            return ['success' => false, 'error' => 'OTP non disponible'];
        }

        $response = $this->makeRequest('POST', '/ompay/register', [
            'telephone' => $this->testPhone,
            'otp' => $this->otpCode,
            'nom' => 'TEST',
            'prenom' => 'AUTOMATE',
            'password' => $this->testPassword,
            'password_confirmation' => $this->testPassword,
            'cni' => 'AUTO' . rand(100000000, 999999999),
            'sexe' => 'M',
            'date_naissance' => '1990-01-01'
        ]);

        if ($response['http_code'] === 200) {
            $data = json_decode($response['response'], true);
            if (isset($data['data']['token'])) {
                $this->authToken = $data['data']['token'];
            }
            return ['success' => true, 'data' => $data['data'] ?? null];
        }

        return [
            'success' => false,
            'error' => 'Échec inscription',
            'response' => $response['response']
        ];
    }

    private function login()
    {
        $response = $this->makeRequest('POST', '/ompay/login', [
            'telephone' => $this->testPhone,
            'password' => $this->testPassword
        ]);

        if ($response['http_code'] === 200) {
            $data = json_decode($response['response'], true);
            if (isset($data['data']['token'])) {
                $this->authToken = $data['data']['token'];
            }
            return ['success' => true, 'data' => $data['data'] ?? null];
        }

        return [
            'success' => false,
            'error' => 'Échec connexion',
            'response' => $response['response']
        ];
    }

    private function getBalance()
    {
        if (!$this->authToken) {
            return ['success' => false, 'error' => 'Token non disponible'];
        }

        $response = $this->makeRequest('GET', '/ompay/wallet/balance', null, [
            "Authorization: Bearer {$this->authToken}"
        ]);

        if ($response['http_code'] === 200) {
            $data = json_decode($response['response'], true);
            return ['success' => true, 'data' => $data['data'] ?? null];
        }

        return [
            'success' => false,
            'error' => 'Échec récupération solde',
            'response' => $response['response']
        ];
    }

    private function transfer()
    {
        if (!$this->authToken) {
            return ['success' => false, 'error' => 'Token non disponible'];
        }

        // Tentative de transfert avec montant élevé (devrait échouer)
        $response = $this->makeRequest('POST', '/ompay/wallet/transfer', [
            'recipient_telephone' => '779876543',
            'amount' => 100000 // Montant élevé pour tester validation
        ], ["Authorization: Bearer {$this->authToken}"]);

        // On s'attend à un échec (solde insuffisant) donc 400 est un succès
        if ($response['http_code'] === 400) {
            return ['success' => true, 'data' => ['validation_ok' => true]];
        }

        return [
            'success' => false,
            'error' => 'Validation transfert défaillante',
            'response' => $response['response']
        ];
    }

    private function getHistory()
    {
        if (!$this->authToken) {
            return ['success' => false, 'error' => 'Token non disponible'];
        }

        $response = $this->makeRequest('GET', '/ompay/wallet/history', null, [
            "Authorization: Bearer {$this->authToken}"
        ]);

        if ($response['http_code'] === 200) {
            $data = json_decode($response['response'], true);
            return ['success' => true, 'data' => $data['data'] ?? null];
        }

        return [
            'success' => false,
            'error' => 'Échec récupération historique',
            'response' => $response['response']
        ];
    }

    private function logout()
    {
        if (!$this->authToken) {
            return ['success' => false, 'error' => 'Token non disponible'];
        }

        $response = $this->makeRequest('POST', '/ompay/logout', null, [
            "Authorization: Bearer {$this->authToken}"
        ]);

        if ($response['http_code'] === 200) {
            $this->authToken = null; // Invalider le token localement
            return ['success' => true, 'data' => ['logged_out' => true]];
        }

        return [
            'success' => false,
            'error' => 'Échec déconnexion',
            'response' => $response['response']
        ];
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

    private function extractOtpFromLogs($telephone)
    {
        $logFile = __DIR__ . '/storage/logs/laravel.log';
        if (!file_exists($logFile)) {
            return null;
        }

        $logs = file($logFile);
        foreach (array_reverse($logs) as $line) {
            if (strpos($line, 'SMS OMPAY') !== false &&
                strpos($line, $telephone) !== false) {
                // Extraire l'OTP du JSON
                if (preg_match('/"message":"Votre code de vérification OMPAY est : (\d{6})"/', $line, $matches)) {
                    return $matches[1];
                }
            }
        }
        return null;
    }

    private function printSummary($results)
    {
        echo "📊 RÉSULTATS FINAUX\n";
        echo "===================\n";

        $totalTests = count($results);
        $passedTests = count(array_filter($results, fn($r) => $r['success']));

        echo "✅ Tests réussis: $passedTests/$totalTests\n";

        if ($passedTests === $totalTests) {
            echo "🎉 TOUS LES TESTS SONT RÉUSSIS !\n";
            echo "🚀 Le module OMPAY est prêt pour la production.\n";
        } else {
            echo "⚠️  Certains tests ont échoué. Vérifiez les logs.\n";
        }

        echo "\n🔍 DÉTAIL PAR TEST:\n";
        foreach ($results as $test => $result) {
            $status = $result['success'] ? '✅' : '❌';
            echo "   $status $test\n";
        }

        echo "\n📝 RECOMMANDATIONS:\n";
        if ($passedTests < $totalTests) {
            echo "   - Vérifiez les logs Laravel pour les erreurs détaillées\n";
            echo "   - Assurez-vous que le serveur est démarré\n";
            echo "   - Vérifiez la connectivité à la base de données\n";
        }
        echo "   - Pour les tests en production, remplacez SmsService par une vraie API SMS\n";
        echo "   - Ajoutez des tests PHPUnit pour l'intégration continue\n";
    }
}

// Exécuter les tests
echo "Démarrage des tests dans 3 secondes...\n";
sleep(3);

$tester = new OmpayAutomatedTester();
$tester->runAllTests();