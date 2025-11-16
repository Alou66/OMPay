# OMPAY API - Checklist de Tests

## 🔐 Tests Authentification

### Inscription
- [ ] Inscription avec données valides → Status pending + OTP envoyé
- [ ] Inscription téléphone existant → Erreur 409
- [ ] Inscription CNI dupliqué → Erreur 400
- [ ] Validation champs requis manquants
- [ ] Validation format téléphone invalide
- [ ] Validation mot de passe trop court
- [ ] Validation date naissance future

### OTP
- [ ] Demande OTP compte pending → OTP activation envoyé
- [ ] Demande OTP compte actif → OTP connexion envoyé
- [ ] Rate limit 3 demandes/h → Erreur après limite
- [ ] Rate limit IP 5/min → Erreur après limite
- [ ] OTP expiré (6min) → Vérification échoue
- [ ] OTP invalide → Vérification échoue
- [ ] OTP utilisé → Plus valide

### Vérification OTP
- [ ] OTP valide compte pending → Activation + tokens
- [ ] OTP valide compte actif → Tokens seulement
- [ ] OTP invalide → Erreur
- [ ] OTP expiré → Erreur

### Connexion Password
- [ ] Identifiants corrects → Tokens
- [ ] Mot de passe incorrect → Erreur
- [ ] Compte non activé → Erreur
- [ ] 5 échecs → Compte verrouillé 15min
- [ ] Connexion pendant lockout → Erreur

### Refresh Token
- [ ] Refresh valide → Nouveaux tokens + ancien révoqué
- [ ] Refresh expiré → Erreur
- [ ] Refresh révoqué → Erreur
- [ ] Refresh invalide → Erreur

### Déconnexion
- [ ] Logout → Tous tokens révoqués
- [ ] Accès avec token révoqué → 401

## 💸 Tests Transactions

### Solde
- [ ] Consultation solde correct
- [ ] Solde avec transactions multiples
- [ ] Solde compte inexistant → 404

### Dépôt
- [ ] Dépôt montant valide → Transaction créée
- [ ] Dépôt montant 0 → Erreur validation
- [ ] Dépôt montant négatif → Erreur validation
- [ ] Dépôt montant > limite → Erreur validation
- [ ] Event TransactionCreated dispatché

### Retrait
- [ ] Retrait fonds suffisants → Transaction créée
- [ ] Retrait fonds insuffisants → Erreur
- [ ] Retrait montant invalide → Erreur validation
- [ ] Event TransactionCreated dispatché

### Transfert
- [ ] Transfert valide → 2 transactions créées
- [ ] Transfert vers soi → Erreur
- [ ] Transfert compte destinataire inactif → Erreur
- [ ] Transfert fonds insuffisants → Erreur
- [ ] Concurrence : 2 transferts simultanés → 1 réussi, 1 échoue
- [ ] Events TransactionCreated dispatchés

### Historique
- [ ] Historique paginé correct
- [ ] Filtrage par type
- [ ] Tri par date décroissant
- [ ] Pagination metadata correct

## 🔒 Tests Sécurité

### Rate Limiting
- [ ] Auth endpoints : 60/min
- [ ] OTP : 3/h par user + 5/min par IP
- [ ] Transactions : 60/min

### Validation
- [ ] Injection SQL → Échappé
- [ ] XSS → Sanitizé
- [ ] Input malformé → Erreur validation

### Tokens
- [ ] Access token expiré → 401
- [ ] Refresh token rotation
- [ ] Token blacklist immédiat

### Audit
- [ ] Toutes transactions loggées
- [ ] Auth failures loggés
- [ ] Events dispatchés

## 🏗️ Tests Architecture

### Events
- [ ] TransactionCreated dispatché
- [ ] Listener LogTransaction exécuté
- [ ] Queue job SendSms

### Jobs
- [ ] SendSms job queued
- [ ] Job exécuté avec retry
- [ ] Échec job loggé

### Exceptions
- [ ] ApiException rendue correctement
- [ ] Http status codes appropriés
- [ ] Messages d'erreur en français

### Performance
- [ ] N+1 queries évitées
- [ ] Eager loading utilisé
- [ ] Cache hit/miss
- [ ] Database locks pour concurrence

## 🌐 Tests Intégration

### Flow Complet
- [ ] Inscription → OTP → Activation → Login → Transaction → Logout
- [ ] Échec à chaque étape géré

### Concurrence
- [ ] Multiples utilisateurs simultanés
- [ ] Transactions parallèles
- [ ] Rate limits respectés

### External Services
- [ ] Twilio SMS envoyé
- [ ] Queue processing
- [ ] Cache Redis
- [ ] Database connections

## 📊 Tests Métriques

### Coverage
- [ ] Unit tests : 80%+
- [ ] Feature tests : 90%+
- [ ] Controllers, Services, Models couverts

### Performance
- [ ] Response time < 200ms
- [ ] Throughput > 100 req/sec
- [ ] Memory usage stable

### Fiabilité
- [ ] 99.9% uptime
- [ ] Error rate < 0.1%
- [ ] Recovery automatique