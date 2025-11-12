# Guide Complet d'Utilisation de Swagger pour l'API OMPAY

Ce guide vous explique étape par étape comment utiliser Swagger UI pour tester toutes les fonctionnalités de l'API OMPAY : inscription, connexion, dépôt, retrait et transfert.

## Prérequis

1. **Serveur Laravel en cours d'exécution** :
   ```bash
   php artisan serve
   ```

2. **Accès à Swagger UI** :
   - Ouvrez votre navigateur à l'adresse : `http://localhost:8000/api/documentation`

## 1. Inscription (Registration)

L'inscription se déroule en **2 étapes** :

### Étape 1 : Envoi du code de vérification SMS

1. Dans Swagger UI, trouvez l'endpoint **`/ompay/send-verification`** (POST)
2. Cliquez sur **"Try it out"**
3. Dans le corps de la requête, entrez :
   ```json
   {
     "telephone": "+221771234567"
   }
   ```
4. Cliquez sur **"Execute"**
5. **Résultat attendu** : Code de vérification envoyé par SMS

### Étape 2 : Inscription complète avec OTP

1. Trouvez l'endpoint **`/ompay/register`** (POST)
2. Cliquez sur **"Try it out"**
3. Remplissez le corps de la requête avec :
   ```json
   {
     "nom": "Diop",
     "prenom": "Amadou",
     "telephone": "+221771234567",
     "password": "password123",
     "password_confirmation": "password123",
     "otp": "123456",
     "cni": "1234567890123",
     "sexe": "M",
     "date_naissance": "1990-01-15"
   }
   ```
4. Cliquez sur **"Execute"**
5. **Résultat attendu** : Inscription réussie avec token d'accès

**Note** : Le numéro de téléphone doit être au format sénégalais valide (+221XXXXXXXXX)

## 2. Connexion (Login)

1. Trouvez l'endpoint **`/ompay/login`** (POST)
2. Cliquez sur **"Try it out"**
3. Dans le corps de la requête, entrez :
   ```json
   {
     "telephone": "+221771234567",
     "password": "password123"
   }
   ```
4. Cliquez sur **"Execute"**
5. **Résultat attendu** : Connexion réussie avec token Bearer

### Configuration de l'authentification dans Swagger

Après la connexion réussie :

1. Cliquez sur le bouton **"Authorize"** en haut de la page Swagger
2. Dans le champ "Value", entrez : `Bearer VOTRE_TOKEN_ICI`
3. Cliquez sur **"Authorize"**

Tous les endpoints suivants utiliseront automatiquement ce token.

## 3. Dépôt (Deposit)

1. Trouvez l'endpoint **`/ompay/deposit`** (POST)
2. Cliquez sur **"Try it out"**
3. Dans le corps de la requête, entrez :
   ```json
   {
     "amount": 50000,
     "description": "Dépôt via mobile"
   }
   ```
4. Cliquez sur **"Execute"**
5. **Résultat attendu** : Dépôt effectué avec succès

**Règles de validation** :
- Montant minimum : 100 FCFA
- Montant maximum : 5 000 000 FCFA
- Description optionnelle (max 255 caractères)

## 4. Retrait (Withdrawal)

1. Trouvez l'endpoint **`/ompay/withdraw`** (POST)
2. Cliquez sur **"Try it out"**
3. Dans le corps de la requête, entrez :
   ```json
   {
     "amount": 25000,
     "description": "Retrait DAB"
   }
   ```
4. Cliquez sur **"Execute"**
5. **Résultat attendu** : Retrait effectué avec succès

**Règles de validation** :
- Montant minimum : 100 FCFA
- Montant maximum : 1 000 000 FCFA
- Solde suffisant requis
- Description optionnelle (max 255 caractères)

## 5. Transfert (Transfer)

1. Trouvez l'endpoint **`/ompay/transfer`** (POST)
2. Cliquez sur **"Try it out"**
3. Dans le corps de la requête, entrez :
   ```json
   {
     "recipient_telephone": "+221781234567",
     "amount": 15000,
     "description": "Paiement facture"
   }
   ```
4. Cliquez sur **"Execute"**
5. **Résultat attendu** : Transfert effectué avec succès

**Règles de validation** :
- Numéro de téléphone destinataire valide
- Montant minimum : 100 FCFA
- Montant maximum : 1 000 000 FCFA
- Destinataire doit exister dans le système
- Solde suffisant requis
- Description optionnelle (max 255 caractères)

## 6. Consultations Supplémentaires

### Vérifier le solde

1. Trouvez l'endpoint **`/ompay/balance/{compteId}`** (GET)
2. Cliquez sur **"Try it out"**
3. Remplissez le paramètre `compteId` avec l'UUID de votre compte
4. Cliquez sur **"Execute"**

### Historique des transactions

1. Trouvez l'endpoint **`/ompay/transactions/{compteId}`** (GET)
2. Cliquez sur **"Try it out"**
3. Remplissez le paramètre `compteId` avec l'UUID de votre compte
4. Cliquez sur **"Execute"**

## 7. Déconnexion (Logout)

1. Trouvez l'endpoint **`/ompay/logout`** (POST)
2. Cliquez sur **"Try it out"**
3. Cliquez sur **"Execute"**
4. **Résultat attendu** : Déconnexion réussie

## Codes d'Erreur Courants

### 400 Bad Request
- **OTP invalide** : Code de vérification incorrect ou expiré
- **Solde insuffisant** : Tentative de retrait/transfert avec fonds insuffisants
- **Destinataire introuvable** : Numéro de téléphone destinataire inexistant

### 401 Unauthorized
- **Token manquant** : Endpoint protégé appelé sans authentification
- **Token expiré** : Token d'accès périmé
- **Identifiants invalides** : Téléphone ou mot de passe incorrect

### 404 Not Found
- **Compte introuvable** : UUID de compte inexistant

## Conseils pour les Tests

1. **Testez dans l'ordre** : Inscription → Connexion → Dépôt/Retrait/Transfert
2. **Gardez le token** : Notez le token après connexion pour éviter de vous reconnecter
3. **Vérifiez les soldes** : Utilisez l'endpoint balance pour confirmer les transactions
4. **Testez les limites** : Essayez les montants minimum/maximum et les cas d'erreur
5. **Utilisez des numéros différents** : Pour tester les transferts, créez plusieurs comptes

## Format des Réponses

Toutes les réponses API suivent ce format :

**Succès** :
```json
{
  "success": true,
  "message": "Opération réussie",
  "data": { ... },
  "status_code": 200
}
```

**Erreur** :
```json
{
  "success": false,
  "message": "Description de l'erreur",
  "errors": { ... },
  "status_code": 400
}
```

## Support

Si vous rencontrez des problèmes :
1. Vérifiez que le serveur Laravel fonctionne (`php artisan serve`)
2. Assurez-vous que la base de données est configurée correctement
3. Consultez les logs Laravel dans `storage/logs/laravel.log`
4. Vérifiez la configuration Swagger dans `config/l5-swagger.php`

---

**Note** : Ce guide est basé sur l'API OMPAY version 1.0. Les spécifications peuvent évoluer.