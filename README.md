# OMPAY API - FinTech Sénégalaise

API REST Laravel pour les opérations financières OMPAY - Dépôts, retraits, transferts avec authentification OTP.

## 🚀 Installation

### Prérequis
- PHP 8.1+
- Composer
- MySQL/PostgreSQL
- Redis (optionnel pour cache/queue)
- Twilio account pour SMS

### Étapes d'installation

1. **Cloner le projet**
```bash
git clone <repository-url>
cd ompay-api
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurer l'environnement**
```env
# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ompay
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Twilio pour SMS
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=your_twilio_number

# Cache/Queue (Redis recommandé)
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# JWT (si utilisé)
JWT_SECRET=your_jwt_secret
```

5. **Migrations et seeders**
```bash
php artisan migrate
php artisan db:seed
```

6. **Démarrer le serveur**
```bash
php artisan serve
```

7. **Queues (pour SMS asynchrones)**
```bash
php artisan queue:work
```

## 📚 API Documentation

### Authentification
L'API utilise Sanctum pour l'authentification avec tokens Bearer.

#### Endpoints Auth

##### 1. Inscription
**POST** `/api/auth/register`

**Request Body:**
```json
{
  "nom": "Diop",
  "prenom": "Amadou",
  "telephone": "771234567",
  "password": "password123",
  "password_confirmation": "password123",
  "cni": "AB123456789",
  "sexe": "Homme",
  "date_naissance": "1990-01-01",
  "type_compte": "cheque"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Utilisateur créé – demande de vérification OTP",
  "data": {
    "user": {
      "id": "uuid",
      "nom": "Diop",
      "prenom": "Amadou",
      "telephone": "771234567",
      "status": "pending_verification"
    }
  }
}
```

##### 2. Demander OTP
**POST** `/api/auth/request-otp`

**Request Body:**
```json
{
  "telephone": "771234567"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Code OTP envoyé par SMS"
}
```

##### 3. Vérifier OTP
**POST** `/api/auth/verify-otp`

**Request Body:**
```json
{
  "telephone": "771234567",
  "otp": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {...},
    "tokens": {
      "access_token": "bearer_token",
      "refresh_token": "refresh_token",
      "token_type": "Bearer",
      "expires_in": 900
    }
  }
}
```

##### 4. Connexion avec mot de passe
**POST** `/api/auth/login`

**Request Body:**
```json
{
  "telephone": "771234567",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "access_token": "bearer_token",
    "refresh_token": "refresh_token",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

##### 5. Rafraîchir token
**POST** `/api/auth/refresh`

**Request Body:**
```json
{
  "refresh_token": "refresh_token_here"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Token rafraîchi",
  "data": {
    "access_token": "new_token",
    "refresh_token": "new_refresh",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

##### 6. Déconnexion
**POST** `/api/ompay/logout`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response:**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

### Transactions

#### 1. Consulter solde
**GET** `/api/ompay/balance`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response:**
```json
{
  "success": true,
  "message": "Solde récupéré avec succès",
  "data": {
    "compte_id": "uuid",
    "numero_compte": "OM12345678",
    "solde": 1500.50,
    "devise": "FCFA",
    "date_consultation": "2025-11-16T20:00:00Z"
  }
}
```

#### 2. Dépôt
**POST** `/api/ompay/deposit`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "amount": 1000.00,
  "description": "Dépôt espèces"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Dépôt effectué avec succès",
  "data": {
    "transaction": {
      "id": 1,
      "type": "depot",
      "montant": 1000.00,
      "statut": "reussi",
      "reference": "TXN202511152258103440",
      "date_operation": "2025-11-16T20:00:00Z"
    },
    "reference": "TXN202511152258103440"
  }
}
```

#### 3. Retrait
**POST** `/api/ompay/withdraw`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "amount": 500.00,
  "description": "Retrait DAB"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Retrait effectué avec succès",
  "data": {
    "transaction": {...},
    "reference": "TXN..."
  }
}
```

#### 4. Transfert
**POST** `/api/ompay/transfer`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "recipient_telephone": "772345678",
  "amount": 500.00,
  "description": "Paiement facture"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Transfert effectué avec succès",
  "data": {
    "debit_transaction": {...},
    "credit_transaction": {...},
    "reference": "TXN202511152302356175"
  }
}
```

#### 5. Historique des transactions
**GET** `/api/ompay/history?page=1&per_page=20&type=transfert`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response:**
```json
{
  "success": true,
  "message": "Historique récupéré avec succès",
  "data": {
    "transactions": [
      {
        "id": 1,
        "type": "transfert",
        "montant": 500.00,
        "statut": "reussi",
        "description": "Paiement facture",
        "reference": "TXN...",
        "date_operation": "2025-11-16T20:00:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 50,
      "last_page": 3
    }
  }
}
```

## 🧪 Tests

### Tests unitaires
```bash
php artisan test --testsuite=Unit
```

### Tests fonctionnels
```bash
php artisan test --testsuite=Feature
```

### Tests OTP/Auth
- OTP expiré
- Tentatives multiples
- Compte verrouillé
- Tokens invalides

### Tests Transactions
- Fonds insuffisants
- Transfert vers compte inactif
- Concurrence (race conditions)
- Limites de montant

## 🔒 Sécurité

- **Rate limiting** : 60 req/min authenticated, OTP rate limited
- **Account lockout** : 5 échecs login = 15min lock
- **OTP sécurisé** : 6 chiffres, 5min expiry, 3/h limit
- **Tokens rotation** : Refresh tokens rotated
- **Audit logging** : Toutes transactions loggées
- **Validation** : Règles strictes téléphone/CNI

## 🏗️ Architecture

- **Actions** : Logique métier isolée
- **Services** : Injection dépendances
- **Events** : Découplage (transactions)
- **Jobs** : SMS asynchrones
- **DTOs** : Requests validation
- **Exceptions** : Gestion erreurs standardisée

## 📊 Monitoring

- Logs Laravel
- Audit trail séparé
- Métriques performance
- Health checks

## 🚀 Production

- **Queue workers** : Pour SMS/jobs
- **Cache Redis** : Soldes, sessions
- **Database indexes** : Optimisations requêtes
- **SSL/TLS** : Chiffrement en transit
- **Backups** : Base données automatisée
- **Monitoring** : Sentry/New Relic

## 📝 Changelog

### v1.0.0
- Authentification OTP complète
- Transactions CRUD
- Sécurité renforcée
- Events et jobs
- Tests complets
