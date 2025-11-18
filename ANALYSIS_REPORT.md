# OMPAY API - Rapport d'Analyse et Corrections

## 1. ANALYSE DES PROBLÈMES IDENTIFIÉS

### 1.1 Flow OTP/Inscription/Login

**Problèmes identifiés :**
- ✅ **Register → User pending + envoi OTP** : Correct, OTP envoyé automatiquement après inscription.
- ✅ **Verify OTP → Activation + tokens JWT** : Correct, activation si compte pending.
- ✅ **Login (password OU OTP)** : Login password séparé, OTP pour activation/connexion.
- ✅ **Refresh → Rotation token + blacklist** : Refresh tokens révoqués lors de rotation.
- ✅ **Logout → Invalidation tokens** : Tous les tokens révoqués.

**Issues mineures :**
- Pas de vérification si utilisateur déjà actif lors de verifyOTP (mais pas critique).
- OTP rate limit double (par téléphone + IP) - bon pour sécurité.

### 1.2 Sécurité JWT et Refresh Tokens

**Problèmes identifiés :**
- ✅ **Expiration tokens** : Access 15min, Refresh 30 jours - approprié.
- ✅ **Rotation refresh tokens** : Ancien révoqué lors de refresh.
- ❌ **Blacklist tokens** : Pas de blacklist au-delà de revoked. Tokens volés utilisables jusqu'à expiration.
- ✅ **Logout complet** : Tous tokens révoqués.
- ✅ **Rate limiting OTP** : 3/h par téléphone + 5/min par IP.

**Recommandations :**
- Implémenter blacklist JWT pour révocation immédiate.
- Ajouter rate limiting sur tous endpoints auth/transaction.

### 1.3 Logique Métier (Soldes, Transactions)

**Problèmes identifiés :**
- ✅ **Calcul soldes** : Correct (dépôts + transferts reçus) - (retraits + transferts envoyés).
- ✅ **Transferts** : Création transactions débit/crédit avec référence unique.
- ❌ **Vérification compte destinataire** : Pas de vérification si compte actif/bloqué.
- ❌ **Concurrence** : Pas de verrouillage pessimiste, risque race conditions sur soldes.
- ❌ **Limites transactionnelles** : Pas de limites journalières/mensuelles.
- ❌ **Frais transactions** : Non implémentés.

**Recommandations :**
- Ajouter vérifications compte destinataire.
- Implémenter locking pessimiste pour transactions.
- Ajouter limites et frais.

### 1.4 Architecture (Services, Actions, Validations)

**Problèmes identifiés :**
- ✅ **Services propres** : Bonne séparation des responsabilités.
- ✅ **Actions** : Wrappers fins autour des services.
- ✅ **Injection dépendances** : Correct dans controller.
- ❌ **Services dupliqués** : OTPManager et OTPService font pareil.
- ❌ **Gestion erreurs** : Exceptions génériques, pas standardisées.
- ❌ **Événements** : Pas d'événements pour actions importantes.
- ❌ **Queues** : SMS non queued, risque timeout.
- ❌ **Cache** : Pas de cache pour soldes/fréquentes requêtes.
- ❌ **N+1 queries** : Risque dans calcul soldes si pas eager loaded.

**Recommandations :**
- Consolider services OTP.
- Standardiser gestion erreurs avec custom exceptions.
- Ajouter événements et queues.
- Implémenter cache Redis.

### 1.5 Risques de Sécurité

**Problèmes identifiés :**
- ✅ **Validation input** : Règles custom pour téléphone/CNI.
- ✅ **Hashing passwords** : Bcrypt par défaut.
- ✅ **Rate limiting** : Présent sur OTP.
- ❌ **Rate limiting général** : Manque sur login/register/refresh/transactions.
- ❌ **Account lockout** : Pas de blocage après échecs répétés.
- ❌ **Audit logging** : Pas de logs détaillés pour actions sensibles.
- ❌ **IP blocking** : Seulement rate limit IP sur OTP.
- ❌ **2FA** : Seulement OTP SMS, pas de backup.

**Recommandations :**
- Rate limiting global avec middleware.
- Account lockout après X échecs.
- Audit logs pour transactions/auth.
- IP whitelist/blacklist.

## 2. PLAN DE CORRECTIONS

### 2.1 Corrections Prioritaires (Sécurité)

1. **Blacklist JWT** : Implémenter cache Redis pour tokens révoqués.
2. **Rate limiting global** : Middleware sur tous endpoints sensibles.
3. **Account lockout** : Compteur échecs login.
4. **Vérifications transferts** : Compte destinataire actif.

### 2.2 Corrections Architecture

1. **Consolider OTP services** : Merger OTPManager et OTPService.
2. **Standardiser erreurs** : Custom exceptions avec codes.
3. **Ajouter événements** : Pour transactions, auth.
4. **Queues SMS** : Async sending.
5. **Cache soldes** : Redis avec invalidation.

### 2.3 Optimisations Performance

1. **Eager loading** : Éviter N+1 dans historiques.
2. **Database indexes** : Sur champs fréquemment query.
3. **Pessimistic locking** : Pour transactions concurrentes.

### 2.4 Nouvelles Fonctionnalités

1. **Limites transactions** : Journalières/mensuelles.
2. **Frais** : Configuration frais par type.
3. **Audit trail** : Logs détaillés.

## 3. IMPACT ET TESTS

- **Tests unitaires** : Services, models.
- **Tests feature** : Endpoints API.
- **Tests sécurité** : Rate limiting, auth.
- **Tests performance** : Charge, concurrence.

## 4. DÉPLOIEMENT

- **Migration données** : Ajout colonnes si nécessaire.
- **Cache warmup** : Précharger soldes.
- **Monitoring** : Logs, métriques.
- **Rollback** : Plan en cas de problème.



 @OA\Property(property="data", type="object",
 *                 @OA\Property(property="user", ref="#/components/schemas/User"),
 *                 @OA\Property(property="compte", ref="#/components/schemas/Compte")