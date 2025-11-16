<?php

// Simple script to test OMPAY API endpoints
// Run with: php test_endpoints.php

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$baseUrl = 'http://127.0.0.1:8000';
$client = new Client(['base_uri' => $baseUrl]);

$results = [];

function testEndpoint($method, $endpoint, $data = null, $headers = [], $expectedStatus = 200) {
    global $client, $results;

    try {
        $options = [];
        if ($data) $options['json'] = $data;
        if ($headers) $options['headers'] = $headers;

        $response = $client->request($method, $endpoint, $options);
        $status = $response->getStatusCode();
        $body = json_decode($response->getBody(), true);

        $results[] = [
            'endpoint' => $endpoint,
            'method' => $method,
            'expected_status' => $expectedStatus,
            'actual_status' => $status,
            'success' => $status === $expectedStatus,
            'response' => $body
        ];

        echo "✓ $method $endpoint - Status: $status\n";

    } catch (RequestException $e) {
        $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'N/A';
        $body = $e->getResponse() ? json_decode($e->getResponse()->getBody(), true) : null;

        $results[] = [
            'endpoint' => $endpoint,
            'method' => $method,
            'expected_status' => $expectedStatus,
            'actual_status' => $status,
            'success' => $status === $expectedStatus,
            'error' => $e->getMessage(),
            'response' => $body
        ];

        echo "✗ $method $endpoint - Status: $status - Error: " . $e->getMessage() . "\n";
    }
}

// Test Auth Endpoints
echo "Testing Auth Endpoints:\n";

// Register
testEndpoint('POST', '/api/auth/register', [
    'nom' => 'Test',
    'prenom' => 'User',
    'telephone' => '771234568',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'cni' => 'AB123456788',
    'sexe' => 'Homme',
    'date_naissance' => '1990-01-01',
    'type_compte' => 'cheque'
], [], 200);

// Request OTP
testEndpoint('POST', '/api/auth/request-otp', [
    'telephone' => '771234568'
], [], 200);

// Verify OTP (assuming OTP is 123456 for test)
testEndpoint('POST', '/api/auth/verify-otp', [
    'telephone' => '771234568',
    'otp' => '123456'
], [], 200);

// Login
testEndpoint('POST', '/api/auth/login', [
    'telephone' => '771234568',
    'password' => 'password123'
], [], 200);

// Refresh (would need token)

// Test OMPAY Endpoints (would need auth token)
// For now, test without auth to see 401
echo "\nTesting OMPAY Endpoints (without auth):\n";

testEndpoint('POST', '/api/ompay/deposit', ['amount' => 1000], [], 401);
testEndpoint('POST', '/api/ompay/withdraw', ['amount' => 500], [], 401);
testEndpoint('POST', '/api/ompay/transfer', ['recipient_telephone' => '772345678', 'amount' => 200], [], 401);
testEndpoint('GET', '/api/ompay/balance', [], [], 401);
testEndpoint('GET', '/api/ompay/history', [], [], 401);
testEndpoint('GET', '/api/ompay/transactions/123', [], [], 401);
testEndpoint('POST', '/api/ompay/logout', [], [], 401);

// Output results
echo "\n\nSummary:\n";
$passed = 0;
$total = count($results);
foreach ($results as $result) {
    if ($result['success']) $passed++;
}

echo "Passed: $passed/$total\n";

file_put_contents('endpoint_test_results.json', json_encode($results, JSON_PRETTY_PRINT));
echo "Detailed results saved to endpoint_test_results.json\n";