# OMPAY API - Guide de Test Complet

## Vue d'ensemble

L'API OMPAY est une API REST sécurisée pour les opérations financières FinTech au Sénégal. Elle inclut l'authentification OTP, la gestion des comptes et les transactions (dépôt, retrait, transfert).

## Prérequis

- PHP 8.1+
- Laravel 10+
- PostgreSQL
- Composer
- Node.js (pour assets, optionnel)

## Installation et Configuration

### 1. Cloner le projet
```bash
git clone <repository-url>
cd teamLaravel
```

### 2. Installer les dépendances
```bash
composer install
npm install
```

### 3. Configuration de l'environnement
```bash
cp .env.example .env
```

Configurer `.env` :
```env
APP_NAME=OMPAY
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ompay
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Sanctum pour l'authentification
SANCTUM_STATEFUL_DOMAINS=localhost:8000
```

### 4. Générer la clé d'application
```bash
php artisan key:generate
```

### 5. Migrations et seeders
```bash
php artisan migrate
php artisan db:seed
```

### 6. Générer la documentation Swagger
```bash
php artisan l5-swagger:generate
```

### 7. Lancer le serveur
```bash
php artisan serve
```

L'API sera accessible sur `http://localhost:8000/api`

## Endpoints API

### Authentification

#### 1. Inscription (Register)
**Endpoint:** `POST /api/auth/register`

**Description:** Crée un nouvel utilisateur avec compte en attente de vérification OTP.

**Paramètres requis:**
- `nom` (string, max 255)
- `prenom` (string, max 255)
- `telephone` (string, format sénégalais: +221XXXXXXXXX ou 77XXXXXXX)
- `password` (string, min 8, confirmé)
- `cni` (string, unique, format sénégalais)
- `sexe` (string: Homme/Femme)
- `date_naissance` (date, avant aujourd'hui)
- `type_compte` (string: cheque/epargne, optionnel)

**Exemple de requête:**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Diop",
    "prenom": "Amadou",
    "telephone": "771234567",
    "password": "password123",
    "password_confirmation": "password123",
    "cni": "AB123456789",
    "sexe": "Homme",
    "date_naissance": "1990-01-01",
    "type_compte": "cheque"
  }'
```

**Réponse de succès (200):**
```json
{
  "success": true,
  "message": "Utilisateur créé – demande de vérification OTP",
  "data": {
    "user": {
      "nom": "Diop",
      "prenom": "Amadou",
      "telephone": "771234567",
      "status": "pending_verification",
      "cni": "AB123456789",
      "sexe": "Homme",
      "role": "client",
      "is_verified": false
    }
  }
}
```

#### 2. Demander OTP (Request OTP)
**Endpoint:** `POST /api/auth/request-otp`

**Description:** Envoie un OTP par SMS. Pour comptes en attente → OTP d'activation. Pour comptes actifs → OTP de connexion.

**Paramètres requis:**
- `telephone` (string, doit exister dans users)

**Exemple:**
```bash
curl -X POST http://localhost:8000/api/auth/request-otp \
  -H "Content-Type: application/json" \
  -d '{"telephone": "771234567"}'
```

**Réponse (200):**
```json
{
  "success": true,
  "message": "Code OTP envoyé par SMS"
}
```

**Rate limiting:** 3 demandes par heure par utilisateur.

#### 3. Vérifier OTP (Verify OTP)
**Endpoint:** `POST /api/auth/verify-otp`

**Description:** Vérifie l'OTP et active le compte si nécessaire, retourne les tokens.

**Paramètres requis:**
- `telephone` (string)
- `otp` (string, 6 chiffres)

**Exemple:**
```bash
curl -X POST http://localhost:8000/api/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"telephone": "771234567", "otp": "123456"}'
```

**Réponse (200):**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {...},
    "tokens": {
      "access_token": "1|hCrUqzgS8DhPIk3CLIaV1gsvtEmGrKn9IWxsoxkD04360b9a",
      "refresh_token": "tXV9BauXVgz7NElE7bF4NcM2hqSdFCKDn8kV11oaUn4czTroQSnQUoPGkPWMgN8a",
      "token_type": "Bearer",
      "expires_in": 900
    }
  }
}
```

#### 4. Connexion classique (Login)
**Endpoint:** `POST /api/auth/login`

**Description:** Authentification avec téléphone et mot de passe pour comptes déjà activés.

**Paramètres requis:**
- `telephone` (string)
- `password` (string)

**Exemple:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"telephone": "771234567", "password": "password123"}'
```

**Réponse (200):**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "access_token": "...",
    "refresh_token": "...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

#### 5. Rafraîchir Token (Refresh Token)
**Endpoint:** `POST /api/auth/refresh`

**Description:** Génère un nouveau token d'accès avec rotation des tokens.

**Paramètres requis:**
- `refresh_token` (string)

**Exemple:**
```bash
curl -X POST http://localhost:8000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "your_refresh_token"}'
```

### Transactions (Requière Authentification)

Tous les endpoints de transaction nécessitent un header `Authorization: Bearer {access_token}`

#### 6. Dépôt (Deposit)
**Endpoint:** `POST /api/ompay/deposit`

**Paramètres requis:**
- `amount` (number, min 100, max 5,000,000)
- `description` (string, max 255, optionnel)

**Exemple:**
```bash
curl -X POST http://localhost:8000/api/ompay/deposit \
  -H "Authorization: Bearer your_access_token" \
  -H "Content-Type: application/json" \
  -d '{"amount": 1000, "description": "Dépôt espèces"}'
```

**Réponse (200):**
```json
{
  "success": true,
  "message": "Dépôt effectué avec succès",
  "data": {
    "transaction": {
      "id": 1,
      "type": "depot",
      "montant": 1000,
      "statut": "reussi",
      "reference": "TXN202511161234567890",
      "date_operation": "2025-11-16T12:34:56.000000Z"
    },
    "reference": "TXN202511161234567890"
  }
}
```

#### 7. Retrait (Withdraw)
**Endpoint:** `POST /api/ompay/withdraw`

**Paramètres similaires au dépôt.**

#### 8. Transfert (Transfer)
**Endpoint:** `POST /api/ompay/transfer`

**Paramètres requis:**
- `recipient_telephone` (string, numéro sénégalais)
- `amount` (number, min 100, max 1,000,000)
- `description` (string, max 255, optionnel)

**Exemple:**
```bash
curl -X POST http://localhost:8000/api/ompay/transfer \
  -H "Authorization: Bearer your_access_token" \
  -H "Content-Type: application/json" \
  -d '{
    "recipient_telephone": "772345678",
    "amount": 500,
    "description": "Paiement facture"
  }'
```

#### 9. Solde (Balance)
**Endpoint:** `GET /api/ompay/balance`

**Paramètres optionnels:**
- `compteId` (string, UUID, compte principal par défaut)

**Exemple:**
```bash
curl -X GET http://localhost:8000/api/ompay/balance \
  -H "Authorization: Bearer your_access_token"
```

**Réponse (200):**
```json
{
  "success": true,
  "message": "Solde récupéré avec succès",
  "data": {
    "compte_id": "uuid",
    "numero_compte": "OM12345678",
    "solde": 1500.50,
    "devise": "FCFA",
    "date_consultation": "2025-11-16T12:34:56.000000Z"
  }
}
```

#### 10. Historique (History)
**Endpoint:** `GET /api/ompay/history`

**Paramètres optionnels:**
- `page` (integer, default 1)
- `per_page` (integer, 1-100, default 20)
- `type` (string: depot/retrait/transfert)

**Exemple:**
```bash
curl -X GET "http://localhost:8000/api/ompay/history?page=1&per_page=10&type=depot" \
  -H "Authorization: Bearer your_access_token"
```

#### 11. Transactions par compte (Transactions by Account)
**Endpoint:** `GET /api/ompay/transactions/{compteId}`

**Paramètres d'URL:**
- `compteId` (string, UUID)

#### 12. Déconnexion (Logout)
**Endpoint:** `POST /api/ompay/logout`

**Exemple:**
```bash
curl -X POST http://localhost:8000/api/ompay/logout \
  -H "Authorization: Bearer your_access_token"
```

**Réponse (200):**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

## Codes de Réponse HTTP

- `200` : Succès
- `400` : Erreur de validation ou logique métier
- `401` : Non authentifié
- `404` : Ressource non trouvée
- `422` : Erreur de validation des données
- `429` : Rate limiting dépassé
- `500` : Erreur serveur

## Sécurité

- **Bearer Token** : Utilisation de Laravel Sanctum
- **Rate Limiting** : 3 demandes OTP par heure par utilisateur
- **Validation stricte** : Formats sénégalais pour téléphone et CNI
- **Rotation des tokens** : Refresh tokens renouvelés automatiquement
- **Chiffrement des mots de passe** : Bcrypt

## Flow OTP Unifié

1. **Inscription** → Compte `pending_verification` → OTP d'activation
2. **Vérification OTP** → Compte `Actif` + Tokens
3. **Connexion future** → OTP de connexion ou login classique

## Tests Automatisés

### Lancer les tests
```bash
php artisan test
```

### Tests disponibles
- AuthFlowTest : Test complet du flow d'authentification
- Validation des données
- Rate limiting
- Gestion des erreurs

## Documentation Swagger

Accessible via : `http://localhost:8000/api/documentation`

Générée automatiquement depuis les annotations PHP.

## Debugging

### Erreurs courantes

1. **"Code OTP invalide ou expiré"**
   - Vérifier le code reçu par SMS
   - OTP expire après 5 minutes

2. **"Trop de tentatives. Veuillez réessayer dans une heure."**
   - Rate limiting activé (3 demandes/heure/utilisateur)

3. **"Solde insuffisant"**
   - Vérifier le solde avant retrait/transfert

4. **"Utilisateur destinataire introuvable"**
   - Vérifier le numéro de téléphone du destinataire

### Logs
```bash
tail -f storage/logs/laravel.log
```

## Architecture

- **Contrôleur léger** : OmpayController délègue aux Actions
- **Services métier** : AuthService, OTPManager, TransactionService
- **Actions** : Logique spécifique par endpoint
- **Repositories** : Accès aux données
- **Requests** : Validation des entrées
- **Traits** : Réponses API standardisées

## Corrections Apportées

1. **SQL Error corrigé** : Utilisation de `whereRaw` pour comparaisons boolean PostgreSQL
2. **Rate limiting unifié** : Suppression du middleware redondant
3. **Tests mis à jour** : Alignés sur le comportement réel de l'API
4. **Swagger généré** : Documentation à jour

## Liste des Endpoints Testés

| Endpoint | Méthode | Statut | Description |
|----------|---------|--------|-------------|
| `/api/auth/register` | POST | ✅ Fonctionnel | Inscription utilisateur |
| `/api/auth/request-otp` | POST | ✅ Fonctionnel | Demande OTP |
| `/api/auth/verify-otp` | POST | ✅ Fonctionnel | Vérification OTP |
| `/api/auth/login` | POST | ✅ Fonctionnel | Connexion classique |
| `/api/auth/refresh` | POST | ✅ Fonctionnel | Rafraîchissement token |
| `/api/ompay/deposit` | POST | ✅ Protégé | Dépôt d'argent |
| `/api/ompay/withdraw` | POST | ✅ Protégé | Retrait d'argent |
| `/api/ompay/transfer` | POST | ✅ Protégé | Transfert d'argent |
| `/api/ompay/balance` | GET | ✅ Protégé | Consultation solde |
| `/api/ompay/history` | GET | ✅ Protégé | Historique transactions |
| `/api/ompay/transactions/{id}` | GET | ✅ Protégé | Transactions par compte |
| `/api/ompay/logout` | POST | ✅ Protégé | Déconnexion |

Tous les endpoints respectent les standards REST, incluent la validation appropriée, et retournent des réponses JSON standardisées.