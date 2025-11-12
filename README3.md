# 🧪 GUIDE COMPLET DE TEST DE L'API OMPAY

Ce guide fournit tous les exemples de requêtes curl pour tester votre API Laravel OMPAY.

## 🚀 Configuration

- **URL de base** : `http://localhost:8000/api`
- **Serveur** : Assurez-vous que `php artisan serve` est en cours d'exécution
- **Base de données** : PostgreSQL avec les migrations exécutées

## 📋 Liste complète des endpoints testés

### 🔐 Endpoints OMPAY (Portefeuille mobile)

#### 1. Envoi du code de vérification (OTP)
```bash
curl -X POST http://localhost:8000/api/ompay/send-verification \
  -H "Content-Type: application/json" \
  -d '{
    "telephone": "771234567"
  }'
```
**Résultat attendu** : Status 200, OTP envoyé par SMS (vérifiez les logs Laravel)

#### 2. Inscription avec validation OTP
```bash
# Remplacez 123456 par l'OTP réel des logs
curl -X POST http://localhost:8000/api/ompay/register \
  -H "Content-Type: application/json" \
  -d '{
    "telephone": "771234567",
    "otp": "123456",
    "nom": "DUPONT",
    "prenom": "Jean",
    "password": "password123",
    "password_confirmation": "password123",
    "cni": "AB123456789",
    "sexe": "F",
    "date_naissance": "1990-01-01"
  }'
```
**Résultat attendu** : Status 200, utilisateur créé avec token

#### 3. Connexion OMPAY
```bash
curl -X POST http://localhost:8000/api/ompay/login \
  -H "Content-Type: application/json" \
  -d '{
    "telephone": "771234567",
    "password": "password123"
  }'
```
**Résultat attendu** : Status 200 avec token d'accès

#### 4. Consultation du solde
```bash
curl -X GET http://localhost:8000/api/ompay/wallet/balance \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI"
```
**Résultat attendu** : Status 200 avec solde du compte

#### 5. Transfert d'argent
```bash
curl -X POST http://localhost:8000/api/ompay/wallet/transfer \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI" \
  -d '{
    "recipient_telephone": "776543210",
    "amount": 5000
  }'
```
**Résultat attendu** : Status 200 ou 400 (si solde insuffisant)

#### 6. Historique des transactions
```bash
curl -X GET http://localhost:8000/api/ompay/wallet/history \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI"
```
**Résultat attendu** : Status 200 avec liste des transactions

#### 7. Déconnexion
```bash
curl -X POST http://localhost:8000/api/ompay/logout \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI"
```
**Résultat attendu** : Status 200

### 🔑 Endpoints d'authentification classiques

#### 8. Connexion utilisateur/admin
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "776543210",
    "password": "password"
  }'
```
**Résultat attendu** : Status 200 avec token ou 401 (identifiants invalides)

#### 9. Déconnexion
```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI"
```
**Résultat attendu** : Status 200

### 🏦 Endpoints Comptes

#### 10. Lister tous les comptes
```bash
curl -X GET "http://localhost:8000/api/v1/comptes?limit=10" \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI"
```
**Résultat attendu** : Status 200 avec liste paginée des comptes

#### 11. Créer un nouveau compte
```bash
curl -X POST http://localhost:8000/api/v1/comptes \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI" \
  -d '{
    "type": "cheque",
    "soldeInitial": 15000,
    "devise": "FCFA",
    "solde": 15000,
    "client": {
      "titulaire": "John Doe",
      "nci": "AB123456789",
      "email": "john@example.com",
      "telephone": "771234567",
      "adresse": "Dakar, Senegal",
      "profession": "Developpeur"
    }
  }'
```
**Résultat attendu** : Status 200 avec compte créé

#### 12. Consulter les transactions d'un compte
```bash
curl -X GET http://localhost:8000/api/v1/comptes/{id-compte}/transactions \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI"
```
**Résultat attendu** : Status 200 avec transactions du compte

### 👥 Endpoints Utilisateurs (Admin seulement)

#### 13. Lister les utilisateurs
```bash
curl -X GET http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer VOTRE_TOKEN_ADMIN"
```
**Résultat attendu** : Status 200 (admin) ou 403 (utilisateur normal)

#### 14. Créer un utilisateur
```bash
curl -X POST http://localhost:8000/api/v1/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer VOTRE_TOKEN_ADMIN" \
  -d '{
    "nom": "Admin",
    "prenom": "Test",
    "login": "admin_test",
    "telephone": "770000000",
    "password": "password123",
    "status": "Actif",
    "cni": "AB000000000",
    "code": "ADMIN001",
    "sexe": "Homme",
    "role": "admin",
    "is_verified": 1,
    "date_naissance": "1980-01-01"
  }'
```
**Résultat attendu** : Status 200 avec utilisateur créé

### 🛡️ Endpoints OAuth2 (Passport)

#### 15. Obtenir un token d'accès
```bash
curl -X POST http://localhost:8000/api/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "password",
    "client_id": "votre_client_id",
    "client_secret": "votre_client_secret",
    "username": "login_utilisateur",
    "password": "mot_de_passe",
    "scope": "*"
  }'
```
**Résultat attendu** : Status 200 avec access_token

### 📊 Endpoints Dashboard Admin

#### 16. Dashboard administrateur
```bash
curl -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer VOTRE_TOKEN_ADMIN"
```
**Résultat attendu** : Status 200 avec statistiques

### 🌐 Endpoints publics

#### 17. Informations utilisateur (authentifié)
```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI"
```
**Résultat attendu** : Status 200 avec informations utilisateur

## 🧪 Scripts de test automatisés

### Test complet OMPAY
```bash
php test_ompay_automated.php
```

### Test général de l'API
```bash
php test_api.php
```

## 📝 Extraction automatique de l'OTP

Pour automatiser les tests, vous pouvez extraire l'OTP des logs :

```bash
# Après avoir envoyé l'OTP
OTP=$(tail -1 storage/logs/laravel.log | grep -o '"Votre code de vérification OMPAY est : [0-9]*"' | grep -o '[0-9]*')
echo "OTP: $OTP"
```

## ⚠️ Codes d'erreur courants

- **200** : Succès
- **400** : Données invalides / OTP incorrect
- **401** : Authentification requise / Token invalide
- **403** : Permissions insuffisantes
- **404** : Ressource non trouvée
- **422** : Erreur de validation
- **500** : Erreur serveur (vérifiez les logs)

## 🔧 Dépannage

### Problèmes courants

1. **Erreur PostgreSQL avec boolean** : Résolu avec raw SQL
2. **UUID non généré** : Ajout de boot() dans les modèles
3. **Sanctum + UUID** : Problème connu, nécessite configuration spéciale

### Logs de debug

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Voir les requêtes SQL
php artisan tinker --execute="DB::listen(function(\$query) { echo \$query->sql . PHP_EOL; });"
```

## 📊 Résumé des tests

| Endpoint | Méthode | Auth | Status | Fonctionnel |
|----------|---------|------|--------|-------------|
| OMPAY Send OTP | POST | Non | ✅ | Oui |
| OMPAY Register | POST | Non | ✅ | Oui |
| OMPAY Login | POST | Non | ⚠️ | Partiellement |
| OMPAY Balance | GET | Oui | ❓ | Non testé |
| OMPAY Transfer | POST | Oui | ❓ | Non testé |
| OMPAY History | GET | Oui | ❓ | Non testé |
| Auth Login | POST | Non | ✅ | Oui |
| Comptes List | GET | Oui | ✅ | Oui |
| Admin Dashboard | GET | Oui | ✅ | Oui |
| OAuth Token | POST | Non | ⚠️ | Configuration requise |

## 🎯 Recommandations

1. **Pour les tests complets** : Utilisez les scripts PHP automatisés
2. **Pour le développement** : Importez ces requêtes dans Postman
3. **Pour la production** : Configurez correctement Sanctum avec UUID
4. **Sécurité** : Tous les endpoints sensibles nécessitent une authentification

---

*Testé le 11 novembre 2025 - API OMPAY Laravel*