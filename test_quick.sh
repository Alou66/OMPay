#!/bin/bash

# 🚀 SCRIPT DE TEST RAPIDE - API OMPAY
# Usage: ./test_quick.sh

BASE_URL="http://localhost:8000/api"
TEST_PHONE="77$(shuf -i 1000000-9999999 -n 1)"
TEST_PASSWORD="TestPass123"

echo "🧪 TEST RAPIDE API OMPAY"
echo "=========================="
echo "📱 Téléphone de test: $TEST_PHONE"
echo "🔑 Mot de passe: $TEST_PASSWORD"
echo ""

# Fonction pour vérifier la réponse
check_response() {
    local response="$1"
    local expected="$2"
    local test_name="$3"

    if [[ $response == *"$expected"* ]]; then
        echo "✅ $test_name - SUCCÈS"
        return 0
    else
        echo "❌ $test_name - ÉCHEC"
        echo "   Réponse: $response"
        return 1
    fi
}

echo "1️⃣ Test: Envoi OTP"
OTP_RESPONSE=$(curl -s -X POST $BASE_URL/ompay/send-verification \
  -H "Content-Type: application/json" \
  -d "{\"telephone\": \"$TEST_PHONE\"}")

check_response "$OTP_RESPONSE" "Code de vérification envoyé" "Envoi OTP"

echo "2️⃣ Test: Récupération OTP"
sleep 1
OTP=$(tail -1 storage/logs/laravel.log 2>/dev/null | grep -o '"Votre code de vérification OMPAY est : [0-9]*"' | grep -o '[0-9]*' || echo "")

if [[ -n "$OTP" ]]; then
    echo "✅ Récupération OTP - SUCCÈS (OTP: $OTP)"
else
    echo "❌ Récupération OTP - ÉCHEC"
    echo "   Vérifiez: tail -1 storage/logs/laravel.log"
    exit 1
fi

echo "3️⃣ Test: Inscription"
REGISTER_RESPONSE=$(curl -s -X POST $BASE_URL/ompay/register \
  -H "Content-Type: application/json" \
  -d "{
    \"telephone\": \"$TEST_PHONE\",
    \"otp\": \"$OTP\",
    \"nom\": \"TEST\",
    \"prenom\": \"SCRIPT\",
    \"password\": \"$TEST_PASSWORD\",
    \"password_confirmation\": \"$TEST_PASSWORD\",
    \"cni\": \"AB$(shuf -i 100000000-999999999 -n 1)\",
    \"sexe\": \"M\",
    \"date_naissance\": \"1995-05-15\"
  }")

check_response "$REGISTER_RESPONSE" "Inscription réussie" "Inscription"

# Extraire le token
TOKEN=$(echo $REGISTER_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4 2>/dev/null || echo "")

if [[ -z "$TOKEN" ]]; then
    echo "❌ Extraction token - ÉCHEC"
    exit 1
fi

echo "4️⃣ Test: Connexion"
LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/ompay/login \
  -H "Content-Type: application/json" \
  -d "{\"telephone\": \"$TEST_PHONE\", \"password\": \"$TEST_PASSWORD\"}")

check_response "$LOGIN_RESPONSE" "Connexion réussie" "Connexion"

echo "5️⃣ Test: Dépôt"
DEPOSIT_RESPONSE=$(curl -s -X POST $BASE_URL/ompay/deposit \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 30000, "description": "Test dépôt"}')

check_response "$DEPOSIT_RESPONSE" "Dépôt effectué" "Dépôt"

echo "6️⃣ Test: Consultation solde"
BALANCE_RESPONSE=$(curl -s -X GET $BASE_URL/ompay/wallet/balance \
  -H "Authorization: Bearer $TOKEN")

check_response "$BALANCE_RESPONSE" "Solde récupéré" "Consultation solde"

echo "7️⃣ Test: Historique"
HISTORY_RESPONSE=$(curl -s -X GET $BASE_URL/ompay/wallet/history \
  -H "Authorization: Bearer $TOKEN")

check_response "$HISTORY_RESPONSE" "Historique récupéré" "Historique"

echo "8️⃣ Test: Déconnexion"
LOGOUT_RESPONSE=$(curl -s -X POST $BASE_URL/ompay/logout \
  -H "Authorization: Bearer $TOKEN")

check_response "$LOGOUT_RESPONSE" "Déconnexion réussie" "Déconnexion"

echo ""
echo "🎉 TESTS TERMINÉS !"
echo "📋 Consultez README_TESTING.md pour tous les détails"
echo "🔗 Token utilisé: $TOKEN"
echo "📱 Utilisateur créé: $TEST_PHONE"