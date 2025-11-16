# OMPAY - Flow d'Authentification Refactorisé

## Vue d'ensemble

Le système d'authentification OMPAY a été complètement refactorisé pour suivre une architecture propre avec séparation des responsabilités, flow OTP unifié, et sécurité renforcée.

## Architecture

### Structure des classes

```
app/
├── Controllers/
│   └── OmpayController.php          # Contrôleur léger
├── Services/
│   ├── AuthService.php              # Logique d'authentification
│   └── OTPManager.php               # Gestion centralisée des OTP
├── Repositories/
│   └── UserRepository.php           # Accès aux données utilisateurs
├── Http/
│   ├── Requests/Auth/
│   │   ├── RegisterRequest.php
│   │   ├── RequestOTPRequest.php
│   │   ├── VerifyOTPRequest.php
│   │   ├── LoginRequest.php
│   │   └── RefreshTokenRequest.php
│   └── Middleware/
│       └── OTPRateLimitMiddleware.php
```

## Flow d'authentification

### 1. Inscription (REGISTER)

**Endpoint**: `POST /api/auth/register`

**Description**: Crée un utilisateur avec compte en attente de vérification et envoie automatiquement un OTP d'activation.

**Requête**:
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

**Réponse**:
```json
{
  "success": true,
  "message": "Utilisateur créé – demande de vérification OTP",
  "data": {
    "user": { ... }
  }
}
```

### 2. Demande d'OTP (REQUEST OTP)

**Endpoint**: `POST /api/auth/request-otp`

**Description**: Envoie un OTP selon le statut du compte utilisateur.
- Compte `pending_verification` → OTP d'activation
- Compte `Actif` → OTP de connexion

**Requête**:
```json
{
  "telephone": "771234567"
}
```

**Réponse**:
```json
{
  "success": true,
  "message": "Code OTP envoyé par SMS"
}
```

### 3. Vérification d'OTP (VERIFY OTP)

**Endpoint**: `POST /api/auth/verify-otp`

**Description**: Vérifie l'OTP et gère l'activation/connexion automatique.

**Requête**:
```json
{
  "telephone": "771234567",
  "otp": "123456"
}
```

**Réponse**:
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": { ... },
    "tokens": {
      "access_token": "...",
      "refresh_token": "...",
      "token_type": "Bearer",
      "expires_in": 900
    }
  }
}
```

### 4. Connexion avec mot de passe (LOGIN)

**Endpoint**: `POST /api/auth/login`

**Description**: Authentification classique pour comptes déjà activés.

**Requête**:
```json
{
  "telephone": "771234567",
  "password": "password123"
}
```

**Réponse**:
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

### 5. Rafraîchissement de token (REFRESH)

**Endpoint**: `POST /api/auth/refresh`

**Description**: Génère un nouveau token d'accès avec rotation du refresh token.

**Requête**:
```json
{
  "refresh_token": "refresh_token_here"
}
```

**Réponse**:
```json
{
  "success": true,
  "message": "Token rafraîchi",
  "data": {
    "access_token": "...",
    "refresh_token": "...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

## Sécurité

### Rate Limiting
- **OTP**: Maximum 5 demandes par minute par IP
- **Utilisateur**: Maximum 3 OTP par heure par numéro de téléphone

### Expiration des tokens
- **Access Token**: 15 minutes
- **Refresh Token**: 30 jours avec rotation

### OTP
- **Longueur**: 6 chiffres
- **Expiration**: 5 minutes
- **Usage unique**: Chaque OTP ne peut être utilisé qu'une fois
- **Un seul actif**: Un seul OTP actif par utilisateur à la fois

## Gestion d'erreurs

### Codes de statut HTTP

| Code | Description |
|------|-------------|
| 200 | Succès |
| 400 | Erreur de validation / OTP invalide |
| 401 | Identifiants invalides |
| 422 | Erreur de validation des données |
| 429 | Rate limiting (trop de tentatives) |

### Messages d'erreur courants

```json
{
  "success": false,
  "message": "Code OTP invalide ou expiré."
}
```

```json
{
  "success": false,
  "message": "Trop de tentatives. Veuillez réessayer dans une heure."
}
```

```json
{
  "success": false,
  "message": "Identifiants invalides."
}
```

## Tests automatisés

### Exécution des tests

```bash
# Tous les tests d'authentification
php artisan test tests/Feature/AuthFlowTest.php

# Test spécifique
php artisan test tests/Feature/AuthFlowTest.php --filter="user_can_register_with_valid_data"
```

### Couverture des tests

- ✅ Inscription avec données valides
- ✅ Échec d'inscription avec téléphone dupliqué
- ✅ Demande d'OTP pour compte en attente
- ✅ Demande d'OTP pour compte actif
- ✅ Échec de demande d'OTP pour utilisateur inexistant
- ✅ Vérification d'OTP et activation de compte
- ✅ Vérification d'OTP pour connexion
- ✅ Échec de vérification avec code invalide
- ✅ Connexion avec mot de passe
- ✅ Échec de connexion avec mauvais mot de passe
- ✅ Échec de connexion pour compte non vérifié
- ✅ Rate limiting des OTP
- ✅ Expiration des OTP

## Utilisation avec Postman

### Collection Postman

Importez la collection suivante dans Postman :

```json
{
  "info": {
    "name": "OMPAY Auth Flow",
    "description": "Flow d'authentification refactorisé"
  },
  "item": [
    {
      "name": "Register",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{ \"nom\": \"Diop\", \"prenom\": \"Amadou\", \"telephone\": \"771234567\", \"password\": \"password123\", \"password_confirmation\": \"password123\", \"cni\": \"AB123456789\", \"sexe\": \"Homme\", \"date_naissance\": \"1990-01-01\", \"type_compte\": \"cheque\" }"
        },
        "url": {
          "raw": "{{base_url}}/api/auth/register",
          "host": ["{{base_url}}"],
          "path": ["api", "auth", "register"]
        }
      }
    },
    {
      "name": "Request OTP",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{ \"telephone\": \"771234567\" }"
        },
        "url": {
          "raw": "{{base_url}}/api/auth/request-otp",
          "host": ["{{base_url}}"],
          "path": ["api", "auth", "request-otp"]
        }
      }
    },
    {
      "name": "Verify OTP",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{ \"telephone\": \"771234567\", \"otp\": \"123456\" }"
        },
        "url": {
          "raw": "{{base_url}}/api/auth/verify-otp",
          "host": ["{{base_url}}"],
          "path": ["api", "auth", "verify-otp"]
        }
      }
    },
    {
      "name": "Login",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{ \"telephone\": \"771234567\", \"password\": \"password123\" }"
        },
        "url": {
          "raw": "{{base_url}}/api/auth/login",
          "host": ["{{base_url}}"],
          "path": ["api", "auth", "login"]
        }
      }
    },
    {
      "name": "Refresh Token",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{ \"refresh_token\": \"your_refresh_token_here\" }"
        },
        "url": {
          "raw": "{{base_url}}/api/auth/refresh",
          "host": ["{{base_url}}"],
          "path": ["api", "auth", "refresh"]
        }
      }
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:8000"
    }
  ]
}
```

### Variables d'environnement

Définissez `base_url` comme `http://localhost:8000` ou l'URL de votre environnement.

## Bonnes pratiques

### Gestion des tokens

1. **Stockage sécurisé**: Stockez les tokens dans le localStorage ou secure storage
2. **Rafraîchissement automatique**: Implémentez un mécanisme de rafraîchissement automatique avant expiration
3. **Nettoyage**: Supprimez les tokens lors de la déconnexion

### Gestion des OTP

1. **Ne jamais logger les OTP** en production
2. **Rate limiting** pour éviter les attaques par déni de service
3. **Expiration courte** pour limiter la fenêtre d'attaque

### Validation

1. **Côté client**: Validation basique pour UX
2. **Côté serveur**: Validation stricte avec règles personnalisées
3. **Sanitisation**: Nettoyez toujours les entrées utilisateur

## Monitoring et logging

### Logs importants

- Tentatives de connexion échouées
- OTP expirés ou invalides
- Rate limiting déclenché
- Erreurs d'envoi SMS

### Métriques à surveiller

- Taux de succès des inscriptions
- Taux d'échec des OTP
- Fréquence des demandes de rafraîchissement
- Taux de rate limiting

## Support et dépannage

### Problèmes courants

**OTP non reçu**
- Vérifier le numéro de téléphone
- Vérifier la couverture SMS
- Contacter le support avec le numéro concerné

**Token expiré**
- Utiliser le refresh token pour obtenir un nouveau token
- Vérifier la validité du refresh token

**Rate limiting**
- Attendre la période spécifiée
- Réduire la fréquence des demandes

### Commandes utiles

```bash
# Générer la documentation Swagger
php artisan l5-swagger:generate

# Nettoyer les OTP expirés
php artisan tinker
>>> app(OTPManager::class)->cleanupExpiredOTPs()

# Vérifier les migrations
php artisan migrate:status

# Lancer les tests
php artisan test