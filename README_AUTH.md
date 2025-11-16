# OMPAY Authentication System

## Vue d'ensemble du Flow d'Authentification

Le système d'authentification OMPAY suit un flow sécurisé en 4 étapes :

### 1. Inscription (Register)
- **Endpoint**: `POST /api/auth/register`
- **Description**: Création d'un compte utilisateur avec statut `pending_verification`
- **Input**:
  ```json
  {
    "nom": "Diop",
    "prenom": "Amadou",
    "telephone": "771234567",
    "password": "password123",
    "password_confirmation": "password123",
    "cni": "1234567890123",
    "sexe": "Homme",
    "date_naissance": "1990-01-01",
    "type_compte": "cheque"
  }
  ```
- **Output**: Confirmation d'inscription, instruction de demander OTP

### 2. Demande OTP (Request OTP)
- **Endpoint**: `POST /api/auth/request-otp`
- **Description**: Génération et envoi d'un code OTP par SMS
- **Input**:
  ```json
  {
    "telephone": "771234567"
  }
  ```
- **Output**: Confirmation d'envoi du code

### 3. Connexion OTP (OTP Login)
- **Endpoint**: `POST /api/auth/login-otp`
- **Description**: Vérification OTP, activation du compte, génération des tokens
- **Input**:
  ```json
  {
    "telephone": "771234567",
    "otp": "123456"
  }
  ```
- **Output**:
  ```json
  {
    "success": true,
    "message": "Connexion réussie",
    "data": {
      "user": {...},
      "compte": {...},
      "access_token": "token...",
      "refresh_token": "refresh_token...",
      "token_type": "Bearer",
      "expires_in": 900
    }
  }
  ```

### 4. Connexion normale (Normal Login)
- **Endpoint**: `POST /api/auth/login`
- **Description**: Connexion avec téléphone + mot de passe
- **Input**:
  ```json
  {
    "telephone": "771234567",
    "password": "password123"
  }
  ```
- **Output**: Même format que OTP Login

### 5. Rafraîchissement du Token (Refresh Token)
- **Endpoint**: `POST /api/auth/refresh`
- **Description**: Rafraîchir le token d'accès avec le refresh token
- **Input**:
  ```json
  {
    "refresh_token": "refresh_token..."
  }
  ```
- **Output**: Nouveaux tokens

## Schéma de Base de Données

### Table `users`
```sql
- id (uuid, primary)
- nom (string)
- prenom (string)
- login (string, unique) -- utilisé pour l'authentification
- telephone (string, unique)
- status (enum: 'Actif', 'Inactif') -- 'pending_verification' initialement
- cni (string, unique)
- code (string)
- sexe (enum: 'Homme', 'Femme')
- role (enum: 'admin', 'client')
- is_verified (integer: 0/1)
- date_naissance (date)
- password (string, hashed)
- permissions (json)
- timestamps
```

### Table `refresh_tokens`
```sql
- id (bigInt, primary)
- user_id (uuid, foreign key)
- token (string 64, unique)
- expires_at (timestamp)
- revoked (boolean, default false)
- timestamps
- index: user_id + revoked
```

### Table `otp_codes`
```sql
- id (bigInt, primary)
- telephone (string)
- otp_code (string 6)
- expires_at (timestamp)
- used (boolean, default false)
- timestamps
- index: telephone + used
```

### Table `clients`
```sql
- id (uuid, primary)
- user_id (uuid, foreign key)
- profession (string, nullable)
- timestamps
```

### Table `comptes`
```sql
- id (uuid, primary)
- client_id (uuid, foreign key)
- numero_compte (string)
- type (enum: 'cheque', 'epargne')
- statut (enum: 'actif', 'inactif', 'bloqué', 'fermé')
- motif_blocage (string, nullable)
- date_fermeture (timestamp, nullable)
- soft deletes
- timestamps
```

## Sécurité

### Tokens
- **Access Token**: Expire en 15 minutes
- **Refresh Token**: Expire en 30 jours, stocké en base avec révocation possible
- Utilise Laravel Sanctum pour la gestion des tokens

### OTP
- Code de 6 chiffres généré aléatoirement
- Expiration: 5 minutes
- Un seul OTP valide par numéro de téléphone
- Rate limiting: 3 demandes max par heure

### Password
- Hashé avec bcrypt
- Validation: minimum 8 caractères, confirmation requise

### Rate Limiting
- OTP: 3 demandes par heure par numéro
- Utilise Laravel Cache pour le tracking

## Architecture

### Services
- **AuthService**: Gestion de l'authentification, tokens, activation compte
- **OTPService**: Gestion des codes OTP, envoi SMS, vérification
- **OmpayService**: Logique métier OMPAY (hérité)
- **SmsService**: Envoi des SMS (simulation en développement)

### Controllers
- **OmpayController**: Gère toutes les routes auth et OMPAY

### Models
- **User**: Utilisateur avec relations client/comptes
- **RefreshToken**: Gestion des refresh tokens
- **OtpCode**: Codes OTP
- **Client**: Client OMPAY
- **Compte**: Compte bancaire

## Testing avec Swagger

1. **Accéder à la documentation**: `http://localhost:8000/api/documentation`
2. **Tester l'inscription**: Utiliser `/api/auth/register`
3. **Demander OTP**: `/api/auth/request-otp`
4. **Se connecter avec OTP**: `/api/auth/login-otp`
5. **Utiliser les tokens** pour les autres endpoints

## Erreurs Possibles

### Inscription
- Téléphone déjà utilisé
- CNI déjà utilisé
- Données invalides

### OTP
- Numéro non trouvé ou compte déjà activé
- Trop de tentatives (rate limit)
- Code OTP invalide/expiré

### Connexion
- Identifiants invalides
- Compte non activé

### Refresh Token
- Token invalide/expiré/révoqué

## Commandes Utiles

```bash
# Générer la documentation Swagger
php artisan l5-swagger:generate

# Lancer les migrations
php artisan migrate

# Démarrer le serveur
php artisan serve
```

## Notes de Développement

- En développement, les SMS sont loggés au lieu d'être envoyés
- Les tokens sont générés avec Sanctum
- Le refresh token est stocké en base pour permettre la révocation
- L'architecture suit les principes SOLID et PSR