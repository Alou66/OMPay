# 🚀 OMPAY API - Documentation Professionnelle

## 📋 Vue d'ensemble

OMPAY est une API RESTful moderne pour un système de portefeuille électronique fintech sénégalais. L'API permet la gestion complète des utilisateurs, comptes bancaires et transactions financières avec un système d'authentification sécurisé basé sur OTP.

### ✨ Fonctionnalités Clés
- 🔐 Authentification multi-niveaux (OTP + Password)
- 💰 Gestion complète des transactions (dépôt, retrait, transfert)
- 📊 Consultation de soldes et historiques paginés
- 🛡️ Sécurité renforcée avec JWT et rotation des tokens
- 📱 Intégration SMS Twilio pour les notifications
- 📚 Documentation OpenAPI 3.0 complète

---

## 🔐 Flux d'Authentification

### 1. Inscription (REGISTER)
```http
POST /api/auth/register
Content-Type: application/json

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

**Réponse :**
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

### 2. Vérification OTP (VERIFY OTP)
```http
POST /api/auth/verify-otp
Content-Type: application/json

{
  "telephone": "771234567"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Code OTP envoyé par SMS"
}
```

### 3. Activation du Compte (ACTIVATE OTP)
```http
POST /api/auth/activate-otp
Content-Type: application/json

{
  "telephone": "771234567",
  "otp": "123456"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Compte activé avec succès",
  "data": {
    "user": { /* user data */ },
    "compte": { /* account data */ }
  }
}
```

### 4. Connexion OTP (LOGIN OTP)
```http
POST /api/auth/login-otp
Content-Type: application/json

{
  "telephone": "771234567",
  "otp": "123456"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1Qi...",
    "refresh_token": "refresh_token_here",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

### 5. Connexion Password (LOGIN PASSWORD)
```http
POST /api/auth/login
Content-Type: application/json

{
  "telephone": "771234567",
  "password": "password123"
}
```

**Réponse :** Identique à LOGIN OTP

### 6. Rafraîchissement des Tokens (REFRESH)
```http
POST /api/auth/refresh
Content-Type: application/json

{
  "refresh_token": "your_refresh_token_here"
}
```

---

## 💰 Opérations Financières

### Dépôt d'Argent
```http
POST /api/ompay/deposit
Authorization: Bearer your_access_token
Content-Type: application/json

{
  "amount": 50000,
  "description": "Dépôt espèces"
}
```

### Retrait d'Argent
```http
POST /api/ompay/withdraw
Authorization: Bearer your_access_token
Content-Type: application/json

{
  "amount": 25000,
  "description": "Retrait DAB"
}
```

### Transfert d'Argent
```http
POST /api/ompay/transfer
Authorization: Bearer your_access_token
Content-Type: application/json

{
  "recipient_telephone": "781234567",
  "amount": 15000,
  "description": "Paiement facture"
}
```

### Consultation du Solde
```http
GET /api/ompay/balance?compteId=uuid
Authorization: Bearer your_access_token
```

### Historique des Transactions (Paginé)
```http
GET /api/ompay/history?page=1&per_page=20&type=depot
Authorization: Bearer your_access_token
```

---

## 📊 Pagination et Filtrage

### Paramètres de Pagination
- `page` : Numéro de page (défaut: 1)
- `per_page` : Éléments par page (défaut: 20, max: 100)

### Filtres Disponibles
- `type` : Filtrer par type de transaction (`depot`, `retrait`, `transfert`)

### Exemple de Réponse Paginée
```json
{
  "success": true,
  "data": {
    "transactions": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 150,
      "last_page": 8,
      "from": 1,
      "to": 20
    }
  }
}
```

---

## 🔧 Utilisation de Swagger

### Accès à la Documentation
1. Démarrer le serveur Laravel
2. Accéder à : `http://localhost:8000/api/documentation`
3. La documentation interactive OpenAPI 3.0 se charge automatiquement

### Fonctionnalités Swagger
- ✅ Interface interactive
- ✅ Test des endpoints en temps réel
- ✅ Authentification Bearer token
- ✅ Exemples de requêtes/réponses
- ✅ Validation des schémas
- ✅ Téléchargement du fichier `openapi.yaml`

### Authentification dans Swagger
1. Cliquez sur "Authorize"
2. Entrez : `Bearer your_access_token`
3. Les endpoints protégés seront automatiquement authentifiés

---

## 📱 Fonctionnement du Système OTP

### Architecture OTP
- **Génération** : Code 6 chiffres aléatoire
- **Validité** : 5 minutes
- **Stockage** : Base de données chiffrée
- **Livraison** : SMS via Twilio
- **Rate limiting** : 3 tentatives par heure par numéro

### Modes de Fonctionnement
```env
# Mode développement (logs seulement)
TWILIO_ENABLED=false

# Mode production (SMS réels)
TWILIO_ENABLED=true
TWILIO_SID=your_sid
TWILIO_TOKEN=your_token
TWILIO_FROM=+221XXXXXXXXX
```

### Sécurité OTP
- ✅ Codes à usage unique
- ✅ Expiration automatique
- ✅ Invalidation après utilisation
- ✅ Rate limiting anti-brute force
- ✅ Logs détaillés pour audit

---

## 🔄 Gestion des Tokens de Rafraîchissement

### Principe de Fonctionnement
1. **Access Token** : Valide 15 minutes, utilisé pour l'API
2. **Refresh Token** : Valide 30 jours, utilisé pour renouveler l'access token
3. **Rotation** : À chaque refresh, nouveaux tokens sont générés
4. **Invalidation** : Anciens tokens deviennent inutilisables

### Avantages
- 🔒 Sécurité renforcée (tokens courts)
- 🔄 Reconduction automatique de session
- 🚫 Prévention des attaques par vol de token
- 📊 Traçabilité des sessions

### Utilisation
```javascript
// Vérifier expiration de l'access token
if (isTokenExpired(accessToken)) {
  // Utiliser refresh token pour obtenir de nouveaux tokens
  const newTokens = await refreshTokens(refreshToken);

  // Mettre à jour le stockage local
  localStorage.setItem('access_token', newTokens.access_token);
  localStorage.setItem('refresh_token', newTokens.refresh_token);
}
```

---

## 🧪 Guide de Test des Endpoints

### Outils Recommandés
- **Postman** : Interface graphique
- **Insomnia** : Alternative moderne
- **cURL** : Tests en ligne de commande
- **Swagger UI** : Tests intégrés

### Collection Postman
```json
{
  "info": {
    "name": "OMPAY API Collection",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:8000/api"
    },
    {
      "key": "access_token",
      "value": ""
    }
  ]
}
```

### Tests Automatisés
```bash
# Installation des dépendances de test
composer install

# Exécution des tests
php artisan test

# Tests spécifiques
php artisan test --filter=AuthTest
php artisan test --filter=TransactionTest
```

### Scénarios de Test Courants

#### Test d'Inscription Complet
```bash
# 1. Inscription
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"nom":"Test","prenom":"User","telephone":"771234567","password":"password123","password_confirmation":"password123","cni":"AB123456789","sexe":"Homme","date_naissance":"1990-01-01"}'

# 2. Vérification OTP (regarder les logs pour le code)
curl -X POST http://localhost:8000/api/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"telephone":"771234567"}'

# 3. Activation avec OTP
curl -X POST http://localhost:8000/api/auth/activate-otp \
  -H "Content-Type: application/json" \
  -d '{"telephone":"771234567","otp":"123456"}'

# 4. Connexion OTP
curl -X POST http://localhost:8000/api/auth/login-otp \
  -H "Content-Type: application/json" \
  -d '{"telephone":"771234567","otp":"123456"}'
```

---

## 🚀 Déploiement en Production

### Prérequis Serveur
- PHP 8.1+
- Composer
- Node.js & NPM
- MySQL/PostgreSQL
- Redis (recommandé)

### Variables d'Environnement
```env
APP_NAME=OMPAY
APP_ENV=production
APP_KEY=base64_generated_key
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ompay_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

TWILIO_ENABLED=true
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=+221XXXXXXXXX

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Commandes de Déploiement
```bash
# Installation des dépendances
composer install --no-dev --optimize-autoloader

# Génération de la clé d'application
php artisan key:generate

# Configuration pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations de base de données
php artisan migrate --force

# Seeders (si nécessaire)
php artisan db:seed --force

# Génération de la documentation API
php artisan l5-swagger:generate

# Permissions des dossiers
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Monitoring et Maintenance
```bash
# Tâches planifiées (scheduler)
php artisan schedule:work

# Files d'attente (si utilisées)
php artisan queue:work

# Nettoyage des logs
php artisan log:clear

# Optimisation des performances
php artisan optimize
```

---

## 🔒 Sécurité

### Mesures Implémentées
- ✅ Hashage des mots de passe (bcrypt)
- ✅ JWT avec expiration courte
- ✅ Rotation des refresh tokens
- ✅ Rate limiting sur les endpoints sensibles
- ✅ Validation stricte des entrées
- ✅ Protection CSRF
- ✅ Sanitisation des données

### Recommandations Additionnelles
- 🔐 Utiliser HTTPS en production
- 🔐 Implémenter des Web Application Firewalls (WAF)
- 🔐 Monitorer les logs de sécurité
- 🔐 Mettre à jour régulièrement les dépendances
- 🔐 Utiliser des certificats SSL valides

---

## 📈 Performance et Optimisation

### Optimisations Implémentées
- ✅ Pagination sur les gros volumes de données
- ✅ Index DB optimisés
- ✅ Cache des configurations Laravel
- ✅ Lazy loading des relations Eloquent
- ✅ Transactions DB atomiques

### Métriques à Surveiller
- Temps de réponse des endpoints
- Utilisation mémoire
- Taux d'erreur des transactions
- Performances de la base de données

---

## 🐛 Dépannage

### Problèmes Courants

#### Erreur "Unauthenticated"
```json
{
  "message": "Unauthenticated."
}
```
**Solution** : Vérifier le token Bearer dans l'en-tête Authorization

#### Erreur OTP
```json
{
  "message": "Code OTP invalide ou expiré."
}
```
**Solution** : Regénérer un nouveau code OTP, vérifier le numéro de téléphone

#### Erreur Solde Insuffisant
```json
{
  "message": "Solde insuffisant pour effectuer cette transaction."
}
```
**Solution** : Vérifier le solde avant la transaction

### Logs et Debugging
```bash
# Consulter les logs Laravel
tail -f storage/logs/laravel.log

# Logs des SMS
grep "SMS OMPAY" storage/logs/laravel.log

# Logs des transactions
grep "Transaction" storage/logs/laravel.log
```

---

## 📞 Support et Contact

### Équipe Technique
- **Lead Developer** : [Votre nom]
- **Email** : support@ompay.sn
- **Documentation** : [Lien vers la doc complète]

### Signalement de Bugs
1. Vérifier les logs applicatifs
2. Reproduire le problème
3. Ouvrir une issue avec :
   - Description détaillée
   - Steps to reproduce
   - Logs pertinents
   - Version de l'API

---

## 🎯 Roadmap

### Version 2.0 (Prochaines Fonctionnalités)
- [ ] Application mobile native
- [ ] Intégration carte bancaire
- [ ] Notifications push
- [ ] API webhooks
- [ ] Multi-devises
- [ ] Analytics avancés

---

*OMPAY API v1.0 - Développé avec ❤️ pour la fintech sénégalaise*