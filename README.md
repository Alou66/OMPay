# OMPAY Wallet API

[![Laravel](https://img.shields.io/badge/Laravel-10.10-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![OpenAPI](https://img.shields.io/badge/OpenAPI-3.0.3-blue.svg)](storage/api-docs/openapi.yaml)

API RESTful simplifiée pour le système de portefeuille OMPAY, spécialisée dans la gestion des comptes wallet, des transactions financières et l'authentification sécurisée avec OTP SMS.

**🚀 [Tester l'API en ligne](http://localhost:8000/api/documentation) | 📖 [Documentation Swagger](storage/api-docs/openapi.yaml)**

## 🎯 Objectif du Projet

OMPay Wallet est une API bancaire digitale conçue pour offrir des services financiers essentiels aux utilisateurs. Le système permet :

- **Authentification sécurisée** : Inscription avec vérification OTP par SMS
- **Gestion du portefeuille** : Dépôts, retraits et transferts d'argent
- **Consultation** : Solde et historique des transactions
- **Sécurité** : Authentification avec Laravel Sanctum

## 🔧 Nettoyage et Corrections Réalisées

Ce projet a été analysé et nettoyé pour éliminer toutes les références cassées et stabiliser l'architecture. Voici un résumé des corrections apportées :

### Problèmes Identifiés et Corrigés

#### 1. **AuthServiceProvider nettoyé**
- ❌ **Avant** : Références à des classes inexistantes (`Admin`, `Token`, `ComptePolicy`, `AdminPolicy`)
- ❌ **Avant** : Bindings vers des repositories et services manquants (`CompteRepositoryInterface`, `UserRepositoryInterface`, `ClientRepositoryInterface`, `CompteService`)
- ❌ **Avant** : Utilisation de Laravel Passport (non installé)
- ✅ **Après** : AuthServiceProvider propre avec uniquement les Gates fonctionnels pour l'autorisation

#### 2. **UserSeeder corrigé**
- ❌ **Avant** : Import du modèle `Admin` inexistant et création d'enregistrement `Admin`
- ✅ **Après** : Suppression des références à `Admin`, conservation de l'utilisateur admin avec rôle 'admin'

#### 3. **Routes Web ajustées**
- ❌ **Avant** : Route '/' retournant une vue inexistante causant des erreurs 500
- ✅ **Après** : Route '/' retournant une réponse JSON appropriée pour une API

#### 4. **Architecture stabilisée**
- ✅ Suppression de toutes les références à des classes fantômes
- ✅ Vérification de l'absence de namespaces incorrects
- ✅ Nettoyage des imports inutiles
- ✅ Conservation intacte de la logique métier OMPAY (endpoints fonctionnels préservés)

### Structure Finale

L'architecture est désormais cohérente et prête pour la production :
- **Modèles** : `User`, `Client`, `Compte`, `Transaction`, `OtpCode` (tous existants)
- **Services** : `OmpayService`, `SmsService`, `TransactionService` (tous fonctionnels)
- **Actions** : Pattern Action maintenu pour la séparation des responsabilités
- **Authentification** : Laravel Sanctum pour les tokens API
- **Tests** : Tous les tests passent (unitaires et feature)

### Compatibilité PSR-4 et Autoloading

- ✅ Tous les namespaces respectent PSR-4
- ✅ Aucune classe fantôme dans le projet
- ✅ Composer autoload fonctionnel

## 📋 Prérequis

- **PHP** >= 8.1
- **Composer** >= 2.0
- **PostgreSQL** >= 12 ou **MySQL** >= 8.0
- **Twilio** pour l'envoi d'OTP SMS

## 🔧 Dépendances Principales

| Package | Version | Description |
|---------|---------|-------------|
| `laravel/framework` | ^10.10 | Framework Laravel |
| `laravel/sanctum` | ^3.3 | Authentification API simple |
| `darkaonline/l5-swagger` | ^8.6 | Documentation OpenAPI |
| `twilio/sdk` | ^8.8 | Service SMS pour OTP |

## 📁 Structure du Projet

```
├── app/
│   ├── Http/Controllers/
│   │   └── OmpayController.php          # Contrôleur unique OMPAY
│   ├── Http/Requests/                   # Classes de validation
│   ├── Models/
│   │   ├── User.php                     # Utilisateur avec UUID
│   │   ├── Client.php                   # Client associé
│   │   ├── Compte.php                   # Compte bancaire
│   │   ├── Transaction.php              # Transactions financières
│   │   └── OtpCode.php                  # Codes OTP
│   ├── Services/
│   │   ├── SmsService.php               # Service d'envoi SMS
│   │   ├── TransactionService.php       # Logique transactions
│   │   └── OmpayService.php             # Service principal OMPAY
│   ├── Actions/Ompay/                   # Actions métier OMPAY
│   └── Traits/
│       └── ApiResponseTrait.php         # Réponses API standardisées
├── database/migrations/                 # Migrations base de données
├── routes/api.php                       # Routes API simplifiées
└── storage/api-docs/openapi.yaml         # Documentation OpenAPI
```

## 🚀 Installation et Configuration

### 1. Clonage du Repository

```bash
git clone https://github.com/votre-username/ompay-wallet-api.git
cd ompay-wallet-api
```

### 2. Installation des Dépendances

```bash
composer install
```

### 3. Configuration de l'Environnement

```bash
cp .env.example .env
```

Éditez le fichier `.env` avec vos paramètres :

```env
APP_NAME="OMPAY Wallet API"
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de données
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ompay_wallet_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Twilio pour OTP SMS
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-token
TWILIO_FROM=your-twilio-phone-number
```

### 4. Génération de la Clé d'Application

```bash
php artisan key:generate
```

### 5. Migrations

```bash
php artisan migrate
```

### 6. Démarrage du Serveur

```bash
php artisan serve
```

L'API sera accessible sur `http://localhost:8000`

## 📚 Consommation de l'API

### Authentification

Tous les endpoints de transaction nécessitent un token Bearer dans le header `Authorization`.

#### 1. Envoi du code de vérification OTP

```bash
curl -X POST http://localhost:8000/api/ompay/send-verification \
  -H "Content-Type: application/json" \
  -d '{"telephone": "771234567"}'
```

#### 2. Inscription utilisateur

```bash
curl -X POST http://localhost:8000/api/ompay/register \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Diop",
    "prenom": "Amadou",
    "telephone": "771234567",
    "password": "password123",
    "otp": "123456",
    "cni": "1234567890123",
    "sexe": "M",
    "date_naissance": "1990-01-15"
  }'
```

#### 3. Connexion

```bash
curl -X POST http://localhost:8000/api/ompay/login \
  -H "Content-Type: application/json" \
  -d '{
    "telephone": "771234567",
    "password": "password123"
  }'
```

**Réponse :**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {...},
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

### Transactions

#### Dépôt d'argent

```bash
curl -X POST http://localhost:8000/api/ompay/deposit \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 50000,
    "description": "Dépôt mobile"
  }'
```

#### Retrait d'argent

```bash
curl -X POST http://localhost:8000/api/ompay/withdraw \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 25000,
    "description": "Retrait DAB"
  }'
```

#### Transfert d'argent

```bash
curl -X POST http://localhost:8000/api/ompay/transfer \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "recipient_telephone": "781234567",
    "amount": 15000,
    "description": "Paiement facture"
  }'
```

#### Consultation du solde

```bash
curl -X GET http://localhost:8000/api/ompay/balance \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Historique des transactions

```bash
curl -X GET http://localhost:8000/api/ompay/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Déconnexion

```bash
curl -X POST http://localhost:8000/api/ompay/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📖 Documentation Swagger

### Accès à la Documentation Interactive

1. **Via l'interface web** :
   - Accédez à `http://localhost:8000/api/documentation`
   - Interface interactive pour tester tous les endpoints

2. **Via Postman** :
   - Importez le fichier `storage/api-docs/openapi.yaml`
   - Utilisez les collections générées automatiquement

### Endpoints Disponibles

| Endpoint | Méthode | Description | Authentification |
|----------|---------|-------------|------------------|
| `/ompay/send-verification` | POST | Envoi OTP | Non |
| `/ompay/register` | POST | Inscription | Non |
| `/ompay/login` | POST | Connexion | Non |
| `/ompay/deposit` | POST | Dépôt | Bearer Token |
| `/ompay/withdraw` | POST | Retrait | Bearer Token |
| `/ompay/transfer` | POST | Transfert | Bearer Token |
| `/ompay/balance` | GET | Solde | Bearer Token |
| `/ompay/history` | GET | Historique | Bearer Token |
| `/ompay/logout` | POST | Déconnexion | Bearer Token |

## 🏗️ Architecture

### Authentification et Sécurité
- **Laravel Sanctum** : Authentification API stateless
- **OTP SMS** : Vérification des numéros de téléphone via Twilio
- **UUID** : Identifiants uniques pour tous les modèles
- **Validation stricte** : Form Request classes pour tous les endpoints

### Gestion des Transactions
- **Service TransactionService** : Logique métier centralisée
- **Transactions atomiques** : Garantie d'intégrité avec DB::transaction()
- **Références uniques** : Traçabilité des opérations
- **Calcul automatique du solde** : Basé sur les transactions réelles

### Structure MVC Claire
- **Contrôleur unique** : `OmpayController` avec méthodes spécialisées
- **Actions métier** : Pattern Action pour la séparation des responsabilités
- **Services réutilisables** : Logique partagée entre composants

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Tests avec couverture
php artisan test --coverage
```

## 📊 Monitoring et Logs

- **Logs Laravel** : Suivi des opérations sensibles
- **Middleware de logging** : Audit des requêtes API
- **Gestion d'erreurs centralisée** : Handler personnalisé

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 📞 Support

Pour toute question ou support :
- **Email** : support@ompay.sn
- **Documentation** : [Interface Swagger](http://localhost:8000/api/documentation)
- **Issues** : [GitHub Issues](https://github.com/votre-username/ompay-wallet-api/issues)

---

**Développé avec ❤️ par l'équipe OMPAY - Portefeuille digital simplifié** 🚀🇸🇳
