# 🧪 Guide Complet de Test - API OMPAY Laravel

## 📋 Vue d'ensemble

Ce guide vous permet de tester complètement votre API OMPAY refactorisée avec l'architecture Action-based. Tous les endpoints sont couverts avec des exemples pratiques.

## 🚀 Prérequis

### 1. Environnement
```bash
# Démarrer le serveur Laravel
php artisan serve

# Le serveur sera accessible sur http://localhost:8000
```

### 2. Outils Requis
- **cURL** ou **Postman** pour les requêtes HTTP
- **Base de données** configurée et migrée
- **Variables d'environnement** Twilio (optionnel pour SMS)

### 3. Données de Test
```bash
# Générer des données de test
php artisan db:seed

# Ou créer manuellement un admin pour les tests
php artisan tinker
>>> User::create(['nom'=>'Admin','prenom'=>'Test','login'=>'admin','password'=>Hash::make('password'),'role'=>'Admin'])
```

---

## 🔐 Flux d'Authentification

### **Étape 1 : Inscription Utilisateur OMPAY**

#### 1.1 Envoyer Code de Vérification
```bash
curl -X POST http://localhost:8000/api/ompay/send-verification \
  -H "Content-Type: application/json" \
  -d '{"telephone": "771234567"}'
```

**Réponse Attendue :**
```json
{
  "success": true,
  "message": "Code de vérification envoyé par SMS"
}
```

#### 1.2 Récupérer l'OTP (pour test)
```bash
# Depuis Tinker ou base de données
php artisan tinker
>>> \App\Models\OtpCode::where('telephone', '771234567')->latest()->first()->otp_code
```

#### 1.3 S'inscrire avec OTP
```bash
curl -X POST http://localhost:8000/api/ompay/register \
  -H "Content-Type: application/json" \
  -d '{
    "telephone": "771234567",
    "otp": "XXXXXX",
    "nom": "DUPONT",
    "prenom": "Jean",
    "password": "TestPass123!",
    "password_confirmation": "TestPass123!",
    "cni": "AB123456789",
    "sexe": "M",
    "date_naissance": "1990-01-01"
  }'
```

**Réponse Attendue :**
```json
{
  "success": true,
  "message": "Inscription réussie",
  "data": {
    "user": {...},
    "token": "1|xxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

**💡 Note :** Sauvegardez le token pour les prochaines requêtes !

### **Étape 2 : Connexion**

```bash
curl -X POST http://localhost:8000/api/ompay/login \
  -H "Content-Type: application/json" \
  -d '{
    "telephone": "771234567",
    "password": "TestPass123!"
  }'
```

**Réponse Attendue :**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {...},
    "compte": {...},
    "token": "2|xxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

---

## 💰 Opérations Bancaires (OMPAY)

### **Toutes les requêtes nécessitent :**
```bash
-H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### **1. Consulter le Solde**

```bash
# Solde du compte principal
curl -X GET http://localhost:8000/api/ompay/balance \
  -H "Authorization: Bearer YOUR_TOKEN"

# Solde d'un compte spécifique
curl -X GET "http://localhost:8000/api/ompay/balance?compteId=uuid-du-compte" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Réponse :**
```json
{
  "success": true,
  "message": "Solde récupéré avec succès",
  "data": {
    "compte_id": "uuid",
    "numero_compte": "C12345678",
    "solde": 50000,
    "devise": "FCFA",
    "date_consultation": "2025-11-13T..."
  }
}
```

### **2. Effectuer un Dépôt**

```bash
curl -X POST http://localhost:8000/api/ompay/deposit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "amount": 25000,
    "description": "Dépôt test"
  }'
```

**Réponse :**
```json
{
  "success": true,
  "message": "Dépôt effectué avec succès",
  "data": {
    "transaction": {...},
    "reference": "TXN202511131200000001"
  }
}
```

### **3. Effectuer un Retrait**

```bash
curl -X POST http://localhost:8000/api/ompay/withdraw \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "amount": 10000,
    "description": "Retrait test"
  }'
```

### **4. Effectuer un Transfert**

```bash
curl -X POST http://localhost:8000/api/ompay/transfer \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "recipient_telephone": "772345678",
    "amount": 5000,
    "description": "Transfert test"
  }'
```

**Note :** Le destinataire doit exister dans la base de données.

### **5. Consulter l'Historique**

```bash
curl -X GET http://localhost:8000/api/ompay/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Réponse :**
```json
{
  "success": true,
  "message": "Historique récupéré avec succès",
  "data": {
    "compte_id": "uuid",
    "numero_compte": "C12345678",
    "transactions": [
      {
        "id": 1,
        "type": "depot",
        "montant": "25000.00",
        "description": "Dépôt test",
        "reference": "TXN...",
        "date_operation": "2025-11-13T...",
        "user": {
          "nom": "DUPONT",
          "prenom": "Jean",
          "telephone": "771234567"
        }
      }
    ],
    "total": 1
  }
}
```

### **6. Consulter Transactions d'un Compte Spécifique**

```bash
curl -X GET "http://localhost:8000/api/ompay/transactions/uuid-du-compte" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### **7. Se Déconnecter**

```bash
curl -X POST http://localhost:8000/api/ompay/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 👨‍💼 Administration (Admin Endpoints)

### **Authentification Admin**

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "admin",
    "password": "password"
  }'
```

### **Gestion des Utilisateurs**

#### Lister les Utilisateurs
```bash
curl -X GET http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

#### Créer un Utilisateur
```bash
curl -X POST http://localhost:8000/api/v1/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{
    "nom": "MARTIN",
    "prenom": "Marie",
    "login": "mmartin",
    "password": "SecurePass123!",
    "telephone": "773456789",
    "role": "Client",
    "status": "Actif",
    "cni": "CD987654321",
    "sexe": "F",
    "date_naissance": "1985-05-15"
  }'
```

#### Voir un Utilisateur
```bash
curl -X GET http://localhost:8000/api/v1/users/uuid-utilisateur \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

#### Modifier un Utilisateur
```bash
curl -X PUT http://localhost:8000/api/v1/users/uuid-utilisateur \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{
    "nom": "MARTIN",
    "prenom": "Marie",
    "status": "Actif"
  }'
```

#### Supprimer un Utilisateur
```bash
curl -X DELETE http://localhost:8000/api/v1/users/uuid-utilisateur \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

---

## 🏦 Gestion des Comptes (Admin)

### **Lister les Comptes**
```bash
curl -X GET "http://localhost:8000/api/v1/comptes?limit=10" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### **Voir un Compte**
```bash
curl -X GET http://localhost:8000/api/v1/comptes/uuid-compte \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### **Créer un Compte**
```bash
curl -X POST http://localhost:8000/api/v1/comptes \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{
    "client_id": "uuid-client",
    "type": "cheque",
    "statut": "actif"
  }'
```

### **Modifier les Infos Client**
```bash
curl -X PUT http://localhost:8000/api/v1/comptes/uuid-compte \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{
    "profession": "Développeur",
    "adresse": "Dakar, Sénégal"
  }'
```

### **Fermer un Compte**
```bash
curl -X DELETE http://localhost:8000/api/v1/comptes/uuid-compte \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### **Voir les Transactions d'un Compte**
```bash
curl -X GET http://localhost:8000/api/v1/comptes/uuid-compte/transactions \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

---

## 🧪 Scénarios de Test Complets

### **Scénario 1 : Workflow Complet OMPAY**

```bash
# 1. Inscription
curl -X POST http://localhost:8000/api/ompay/send-verification \
  -H "Content-Type: application/json" \
  -d '{"telephone": "771111111"}'

# Récupérer OTP depuis DB
OTP=$(php artisan tinker --execute="echo \App\Models\OtpCode::where('telephone', '771111111')->latest()->first()->otp_code;")

# 2. S'inscrire
RESPONSE=$(curl -X POST http://localhost:8000/api/ompay/register \
  -H "Content-Type: application/json" \
  -d "{\"telephone\": \"771111111\", \"otp\": \"$OTP\", \"nom\": \"TEST\", \"prenom\": \"USER\", \"password\": \"TestPass123!\", \"password_confirmation\": \"TestPass123!\", \"cni\": \"EF111111111\", \"sexe\": \"M\", \"date_naissance\": \"1990-01-01\"}")

# Extraire le token
TOKEN=$(echo $RESPONSE | jq -r '.data.token')

# 3. Consulter solde (devrait être 0)
curl -X GET http://localhost:8000/api/ompay/balance \
  -H "Authorization: Bearer $TOKEN"

# 4. Faire un dépôt
curl -X POST http://localhost:8000/api/ompay/deposit \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"amount": 100000, "description": "Dépôt initial"}'

# 5. Vérifier solde (devrait être 100000)
curl -X GET http://localhost:8000/api/ompay/balance \
  -H "Authorization: Bearer $TOKEN"

# 6. Faire un retrait
curl -X POST http://localhost:8000/api/ompay/withdraw \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"amount": 25000, "description": "Retrait test"}'

# 7. Consulter historique
curl -X GET http://localhost:8000/api/ompay/history \
  -H "Authorization: Bearer $TOKEN"

# 8. Se déconnecter
curl -X POST http://localhost:8000/api/ompay/logout \
  -H "Authorization: Bearer $TOKEN"
```

### **Scénario 2 : Tests d'Erreurs**

#### OTP Expiré
```bash
# Attendre 6 minutes, puis essayer de s'inscrire
curl -X POST http://localhost:8000/api/ompay/register \
  -H "Content-Type: application/json" \
  -d '{"telephone": "771111111", "otp": "000000", ...}'
# Devrait retourner : "Code OTP invalide ou expiré"
```

#### Solde Insuffisant
```bash
curl -X POST http://localhost:8000/api/ompay/withdraw \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"amount": 1000000, "description": "Retrait trop élevé"}'
# Devrait retourner erreur 400
```

#### Token Invalide
```bash
curl -X GET http://localhost:8000/api/ompay/balance \
  -H "Authorization: Bearer invalid_token"
# Devrait retourner 401 Unauthorized
```

---

## 🔧 Commandes Utiles pour les Tests

### **Base de Données**
```bash
# Vider et recharger les données de test
php artisan migrate:fresh --seed

# Voir les OTP en cours
php artisan tinker
>>> \App\Models\OtpCode::active()->get()

# Voir les utilisateurs
>>> \App\Models\User::all()

# Voir les comptes
>>> \App\Models\Compte::with('client.user')->get()
```

### **Logs**
```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Chercher des erreurs spécifiques
grep "ERROR" storage/logs/laravel.log
```

### **Cache et Optimisation**
```bash
# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Générer les routes
php artisan route:list --path=api
```

---

## 📊 Codes de Réponse API

| Code | Signification | Description |
|------|---------------|-------------|
| 200 | OK | Requête réussie |
| 201 | Created | Ressource créée |
| 400 | Bad Request | Données invalides |
| 401 | Unauthorized | Token manquant/invalide |
| 403 | Forbidden | Permissions insuffisantes |
| 404 | Not Found | Ressource inexistante |
| 422 | Unprocessable Entity | Validation échouée |
| 500 | Internal Server Error | Erreur serveur |

---

## 🎯 Checklist de Test

### **OMPAY Module**
- [ ] Envoi OTP
- [ ] Inscription avec OTP valide
- [ ] Inscription avec OTP invalide
- [ ] Connexion réussie
- [ ] Connexion échouée
- [ ] Consultation solde
- [ ] Dépôt d'argent
- [ ] Retrait d'argent
- [ ] Transfert entre comptes
- [ ] Historique des transactions
- [ ] Déconnexion

### **Admin Module**
- [ ] Authentification admin
- [ ] CRUD utilisateurs
- [ ] CRUD comptes
- [ ] Consultation transactions
- [ ] Autorisations respectées

### **Sécurité**
- [ ] Routes protégées inaccessibles sans token
- [ ] Permissions admin respectées
- [ ] Validation des données
- [ ] Protection contre injection SQL

---

## 🚨 Dépannage

### **Problème : "Method does not exist"**
**Solution :** Redémarrer le serveur Laravel
```bash
php artisan serve
```

### **Problème : Token expiré**
**Solution :** Se reconnecter pour obtenir un nouveau token

### **Problème : OTP non reçu**
**Solution :** Vérifier la configuration Twilio ou consulter la base de données directement

### **Problème : Erreur 500**
**Solution :** Consulter les logs Laravel
```bash
tail -f storage/logs/laravel.log
```

---

## 📞 Support

Si vous rencontrez des problèmes lors des tests :

1. **Vérifiez les logs** : `tail -f storage/logs/laravel.log`
2. **Validez la base de données** : `php artisan tinker`
3. **Testez les routes** : `php artisan route:list --path=api`
4. **Vérifiez la configuration** : `.env` et `config/`

**Votre API est maintenant complètement testée et fonctionnelle ! 🎉**