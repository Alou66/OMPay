# 🚀 **GUIDE COMPLET DE TEST - API OMPAY**

## 📋 **Table des Matières**
- [Configuration](#configuration)
- [Authentification OMPAY](#authentification-ompay)
- [Transactions Financières](#transactions-financières)
- [Consultation des Données](#consultation-des-données)
- [Administration (Admin)](#administration-admin)
- [Gestion des Comptes](#gestion-des-comptes)
- [Scripts de Test Automatisés](#scripts-de-test-automatisés)

---

## ⚙️ **Configuration**

### **Prérequis**
- Laravel 10.x
- PHP 8.1+
- PostgreSQL/MySQL
- Serveur en cours d'exécution : `php artisan serve`

### **URL de Base**
```bash
BASE_URL="http://localhost:8000/api"
```

### **Variables de Test**
```bash
# Utilisateur de test
TEST_PHONE="771234567"
TEST_PASSWORD="TestPass123"
TEST_CNI="AB123456789"

# Admin (si configuré)
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="admin123"
```

---

## 🔐 **Authentification OMPAY**

### **1. Envoi du Code de Vérification (OTP)**

**Endpoint:** `POST /ompay/send-verification`

**Description:** Envoie un SMS avec un code OTP de 6 chiffres

**Requête:**
```bash
curl -X POST http://localhost:8000/api/ompay/send-verification \
  -H "Content-Type: application/json" \
  -d '{
    "telephone": "771234567"
  }'
```

**Réponse de Succès:**
```json
{
  "success": true,
  "message": "Code de vérification envoyé par SMS",
  "data": null
}
```

**📍 Récupération de l'OTP:**
```bash
# Dans les logs Laravel
tail -1 storage/logs/laravel.log

# Ou avec grep
grep "Votre code de vérification OMPAY est" storage/logs/laravel.log
```

### **2. Inscription avec OTP**

**Endpoint:** `POST /ompay/register`

**Description:** Crée un compte utilisateur avec vérification OTP

**Requête:**
```bash
curl -X POST http://localhost:8000/api/ompay/register \
  -H "Content-Type: application/json" \
  -d '{
    "telephone": "771234567",
    "otp": "123456",
    "nom": "DUPONT",
    "prenom": "Jean",
    "password": "TestPass123",
    "password_confirmation": "TestPass123",
    "cni": "AB123456789",
    "sexe": "M",
    "date_naissance": "1990-01-01"
  }'
```

**Réponse de Succès:**
```json
{
  "success": true,
  "message": "Inscription réussie",
  "data": {
    "user": {
      "nom": "DUPONT",
      "prenom": "Jean",
      "telephone": "771234567",
      "sexe": "Homme",
      "role": "client"
    },
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

### **3. Connexion OMPAY**

**Endpoint:** `POST /ompay/login`

**Description:** Authentification d'un utilisateur existant

**Requête:**
```bash
curl -X POST http://localhost:8000/api/ompay/login \
  -H "Content-Type: application/json" \
  -d '{
    "telephone": "771234567",
    "password": "TestPass123"
  }'
```

**Réponse de Succès:**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {...},
    "token": "2|def456...",
    "token_type": "Bearer"
  }
}
```

---

## 💰 **Transactions Financières**

> **⚠️ Tous les endpoints ci-dessous nécessitent une authentification Bearer Token**

### **4. Effectuer un Dépôt**

**Endpoint:** `POST /ompay/deposit`

**Description:** Ajoute de l'argent sur le compte de l'utilisateur

**Requête:**
```bash
curl -X POST http://localhost:8000/api/ompay/deposit \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 50000,
    "description": "Dépôt depuis mobile"
  }'
```

**Réponse de Succès:**
```json
{
  "success": true,
  "message": "Dépôt effectué avec succès",
  "data": {
    "transaction": {
      "type": "depot",
      "montant": "50000.00",
      "reference": "TXN202511111600000001",
      "statut": "reussi"
    },
    "reference": "TXN202511111600000001"
  }
}
```

### **5. Effectuer un Retrait**

**Endpoint:** `POST /ompay/withdraw`

**Description:** Retire de l'argent du compte (vérification du solde)

**Requête:**
```bash
curl -X POST http://localhost:8000/api/ompay/withdraw \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 10000,
    "description": "Retrait DAB"
  }'
```

**Réponse de Succès:**
```json
{
  "success": true,
  "message": "Retrait effectué avec succès",
  "data": {
    "transaction": {
      "type": "retrait",
      "montant": "10000.00",
      "reference": "TXN202511111600000002"
    },
    "reference": "TXN202511111600000002"
  }
}
```

### **6. Effectuer un Transfert**

**Endpoint:** `POST /ompay/transfer`

**Description:** Transfère de l'argent vers un autre utilisateur

**Requête:**
```bash
curl -X POST http://localhost:8000/api/ompay/transfer \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "recipient_telephone": "772345678",
    "amount": 5000,
    "description": "Paiement loyer"
  }'
```

**Réponse de Succès:**
```json
{
  "success": true,
  "message": "Transfert effectué avec succès",
  "data": {
    "debit_transaction": {...},
    "credit_transaction": {...},
    "reference": "TXN202511111600000003"
  }
}
```

---

## 📊 **Consultation des Données**

### **7. Consulter le Solde**

**Endpoint:** `GET /ompay/wallet/balance`

**Description:** Récupère le solde actuel du compte

**Requête:**
```bash
curl -X GET http://localhost:8000/api/ompay/wallet/balance \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Réponse de Succès:**
```json
{
  "success": true,
  "message": "Solde récupéré avec succès",
  "data": {
    "compte_id": "uuid-compte",
    "numero_compte": "C123456789",
    "solde": 45000,
    "devise": "FCFA",
    "date_consultation": "2025-11-11T16:00:00.000000Z"
  }
}
```

### **8. Historique des Transactions**

**Endpoint:** `GET /ompay/wallet/history`

**Description:** Liste des 50 dernières transactions

**Requête:**
```bash
curl -X GET http://localhost:8000/api/ompay/wallet/history \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Réponse de Succès:**
```json
{
  "success": true,
  "message": "Historique récupéré avec succès",
  "data": {
    "compte_id": "uuid-compte",
    "numero_compte": "C123456789",
    "transactions": [
      {
        "id": 1,
        "type": "depot",
        "montant": "50000.00",
        "statut": "reussi",
        "date_operation": "2025-11-11T15:30:00.000000Z",
        "description": "Dépôt initial",
        "reference": "TXN202511111530000001"
      }
    ],
    "total": 1
  }
}
```

### **9. Déconnexion**

**Endpoint:** `POST /ompay/logout`

**Description:** Invalide le token d'accès actuel

**Requête:**
```bash
curl -X POST http://localhost:8000/api/ompay/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Réponse de Succès:**
```json
{
  "success": true,
  "message": "Déconnexion réussie",
  "data": null
}
```

---

## 👑 **Administration (Admin)**

> **⚠️ Nécessite un compte avec rôle 'admin'**

### **10. Connexion Admin**

**Endpoint:** `POST /auth/login`

**Requête:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "admin@example.com",
    "password": "admin123"
  }'
```

### **11. Dashboard Admin**

**Endpoint:** `GET /v1/admin/dashboard`

**Requête:**
```bash
curl -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer ADMIN_TOKEN_HERE"
```

### **12. Gestion des Utilisateurs**

**Endpoints:**
- `GET /v1/users` - Lister tous les utilisateurs
- `POST /v1/users` - Créer un utilisateur
- `GET /v1/users/{id}` - Détails d'un utilisateur
- `PUT /v1/users/{id}` - Modifier un utilisateur
- `DELETE /v1/users/{id}` - Supprimer un utilisateur

**Exemple - Lister les utilisateurs:**
```bash
curl -X GET http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer ADMIN_TOKEN_HERE"
```

---

## 🏦 **Gestion des Comptes**

### **13. CRUD des Comptes**

**Endpoints:**
- `GET /v1/comptes` - Lister tous les comptes
- `POST /v1/comptes` - Créer un compte
- `GET /v1/comptes/{id}` - Détails d'un compte
- `PUT /v1/comptes/{id}` - Modifier un compte
- `DELETE /v1/comptes/{id}` - Supprimer un compte

**Exemple - Créer un compte:**
```bash
curl -X POST http://localhost:8000/api/v1/comptes \
  -H "Authorization: Bearer ADMIN_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "cheque",
    "soldeInitial": 100000,
    "devise": "FCFA",
    "client": {
      "titulaire": "Marie Dupont",
      "nci": "CD987654321",
      "email": "marie@example.com",
      "telephone": "773456789",
      "adresse": "Dakar, Sénégal",
      "profession": "Enseignante"
    }
  }'
```

### **14. Transactions d'un Compte**

**Endpoint:** `GET /v1/comptes/{compte}/transactions`

**Requête:**
```bash
curl -X GET http://localhost:8000/api/v1/comptes/uuid-compte/transactions \
  -H "Authorization: Bearer ADMIN_TOKEN_HERE"
```

---

## 🤖 **Scripts de Test Automatisés**

### **Script Complet de Test**

Créez un fichier `test_complete.sh` :

```bash
#!/bin/bash

BASE_URL="http://localhost:8000/api"
TEST_PHONE="77$(shuf -i 1000000-9999999 -n 1)"
TEST_PASSWORD="TestPass123"

echo "🧪 DÉBUT DES TESTS COMPLÈTS"
echo "📱 Téléphone de test: $TEST_PHONE"

# 1. Envoi OTP
echo "📤 Envoi OTP..."
OTP_RESPONSE=$(curl -s -X POST $BASE_URL/ompay/send-verification \
  -H "Content-Type: application/json" \
  -d "{\"telephone\": \"$TEST_PHONE\"}")

if [[ $OTP_RESPONSE == *"success"* ]]; then
  echo "✅ OTP envoyé"
else
  echo "❌ Échec envoi OTP"
  exit 1
fi

# 2. Récupération OTP
echo "🔍 Récupération OTP..."
OTP=$(tail -1 storage/logs/laravel.log | grep -o '"Votre code de vérification OMPAY est : [0-9]*"' | grep -o '[0-9]*')
echo "🔑 OTP trouvé: $OTP"

# 3. Inscription
echo "📝 Inscription..."
REGISTER_RESPONSE=$(curl -s -X POST $BASE_URL/ompay/register \
  -H "Content-Type: application/json" \
  -d "{
    \"telephone\": \"$TEST_PHONE\",
    \"otp\": \"$OTP\",
    \"nom\": \"TEST\",
    \"prenom\": \"AUTO\",
    \"password\": \"$TEST_PASSWORD\",
    \"password_confirmation\": \"$TEST_PASSWORD\",
    \"cni\": \"AB$(shuf -i 100000000-999999999 -n 1)\",
    \"sexe\": \"M\",
    \"date_naissance\": \"1995-05-15\"
  }")

if [[ $REGISTER_RESPONSE == *"Inscription réussie"* ]]; then
  echo "✅ Inscription réussie"
  TOKEN=$(echo $REGISTER_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
  echo "🔑 Token: $TOKEN"
else
  echo "❌ Échec inscription"
  exit 1
fi

# 4. Test dépôt
echo "💰 Test dépôt..."
DEPOSIT_RESPONSE=$(curl -s -X POST $BASE_URL/ompay/deposit \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 25000, "description": "Test automatique"}')

if [[ $DEPOSIT_RESPONSE == *"Dépôt effectué"* ]]; then
  echo "✅ Dépôt réussi"
else
  echo "❌ Échec dépôt"
fi

# 5. Test solde
echo "📊 Test consultation solde..."
BALANCE_RESPONSE=$(curl -s -X GET $BASE_URL/ompay/wallet/balance \
  -H "Authorization: Bearer $TOKEN")

if [[ $BALANCE_RESPONSE == *"Solde récupéré"* ]]; then
  echo "✅ Solde consulté"
else
  echo "❌ Échec consultation solde"
fi

echo "🎉 TESTS TERMINÉS AVEC SUCCÈS !"
```

**Exécution:**
```bash
chmod +x test_complete.sh
./test_complete.sh
```

---

## 📋 **Codes d'Erreur Courants**

| Code HTTP | Signification | Solution |
|-----------|---------------|----------|
| `400` | Données invalides | Vérifier les champs requis |
| `401` | Non authentifié | Ajouter le header Authorization |
| `403` | Accès refusé | Vérifier les permissions |
| `404` | Ressource introuvable | Vérifier l'URL et les IDs |
| `422` | Erreur de validation | Corriger les données envoyées |
| `500` | Erreur serveur | Vérifier les logs Laravel |

---

## 🔧 **Dépannage**

### **OTP non reçu**
```bash
# Vérifier les logs
tail -20 storage/logs/laravel.log | grep SMS

# Vérifier la configuration SMS
php artisan tinker
>>> config('services.sms')
```

### **Token expiré**
```bash
# Se reconnecter
curl -X POST http://localhost:8000/api/ompay/login \
  -H "Content-Type: application/json" \
  -d '{"telephone": "771234567", "password": "TestPass123"}'
```

### **Erreur de base de données**
```bash
# Vérifier les migrations
php artisan migrate:status

# Relancer les migrations si nécessaire
php artisan migrate:fresh --seed
```

---

## 🎯 **Résumé des Endpoints Prioritaires**

### **Flux Utilisateur Standard :**
1. `POST /ompay/send-verification` → OTP
2. `POST /ompay/register` → Inscription
3. `POST /ompay/login` → Connexion
4. `GET /ompay/wallet/balance` → Solde
5. `POST /ompay/deposit` → Dépôt
6. `POST /ompay/transfer` → Transfert
7. `GET /ompay/wallet/history` → Historique

### **Administration :**
- `POST /auth/login` → Connexion admin
- `GET /v1/admin/dashboard` → Dashboard
- `GET /v1/users` → Gestion utilisateurs
- `GET /v1/comptes` → Gestion comptes

---

**🚀 Votre API OMPay est maintenant prête pour tous les tests ! Bonne découverte ! 🎉**