# Documentation technique de l’API bancaire Laravel

## 1. Endpoints de l’API

| Méthode | URL | Description | Auth/Scope/Policy |
|---------|-----|-------------|-------------------|
| POST    | /api/auth/login         | Authentification, retourne un token d’accès | - |
| POST    | /api/auth/refresh       | Rafraîchir le token d’accès | Auth (token) |
| POST    | /api/auth/logout        | Déconnexion, révoque le token | Auth (token) |
| GET     | /api/user               | Infos utilisateur courant | Auth (token) |
| GET     | /api/v1/admin/dashboard | Statistiques admin (comptes, clients, etc.) | Auth (admin, policy: view Admin) |
| GET     | /api/v1/users           | Liste des utilisateurs (admin) | Auth (admin, policy: manageUsers) |
| GET     | /api/v1/users/{id}      | Détail utilisateur (admin) | Auth (admin, policy: manageUsers) |
| PUT     | /api/v1/users/{id}      | Modifier utilisateur (admin) | Auth (admin, policy: manageUsers) |
| DELETE  | /api/v1/users/{id}      | Supprimer utilisateur (admin) | Auth (admin, policy: manageUsers) |
| GET     | /api/v1/comptes         | Liste des comptes | Auth (policy: compte:read) |
| POST    | /api/v1/comptes         | Créer un compte | Auth (policy: compte:write) |
| PUT     | /api/v1/comptes/{id}    | Modifier infos client du compte | Auth (policy: compte:write) |
| DELETE  | /api/v1/comptes/{id}    | Supprimer/fermer un compte | Auth (policy: compte:write) |
| GET     | /api/v1/comptes/{id}/transactions | Transactions d’un compte | Auth (policy: transaction:read) |
| POST    | /api/oauth/token        | (Passport) Obtenir un token OAuth2 | - |

## 2. Architecture et explications du code

### Contrôleurs
- Gèrent la logique HTTP, valident les requêtes, délèguent au service.
- Exemples : `AuthController`, `CompteController`, `UserController`, `AdminController`.

### Services
- Encapsulent la logique métier complexe (création de compte, gestion des transactions, etc.).
- Exemple : `CompteService` (création, modification, suppression de comptes).
- Avantage : sépare la logique métier du contrôleur, facilite les tests et la maintenance.

### Repositories
- Accès aux données (base de données) via des méthodes métiers (ex : `findByNci`, `findActiveComptes`).
- Exemple : `ClientRepository`, `CompteRepository`.
- Utilisent des interfaces pour permettre l’injection de dépendances et faciliter le remplacement/mock.

### Interfaces
- Définissent les contrats des repositories/services (`ClientRepositoryInterface`, etc.).
- Permettent de changer l’implémentation sans modifier le code qui l’utilise (principe d’inversion de dépendance, SOLID).
- Favorisent les tests unitaires (mock facile).

### Providers
- Fichiers dans `app/Providers` (ex : `AuthServiceProvider`, `EventServiceProvider`).
- Enregistrent les policies, events, services, bindings d’interfaces.
- Exemple : `AuthServiceProvider` mappe les policies aux modèles.

### Events
- Permettent de déclencher des actions lors d’événements métiers (ex : création de compte, transaction).
- Découplent l’action principale des effets secondaires (ex : notification, log).
- Exemple : `CompteCreated`, `TransactionMade` (à créer si besoin).

### Claims personnalisés
- Si vous utilisez Passport/JWT, possibilité d’ajouter des claims personnalisés dans le token (ex : rôle, permissions).
- Permet d’ajouter des infos métier dans le token pour l’auth côté client.

### Policies & Scopes
- Policies : classes qui définissent les règles d’accès (ex : `ComptePolicy`).
- Scopes : méthodes sur les modèles pour filtrer les requêtes selon le contexte utilisateur.
- Utilisation : `authorize('view', $compte)` ou via middleware.

## 3. Pourquoi utiliser des interfaces ?
- **Découplage** : le contrôleur ou le service ne dépend pas d’une implémentation concrète, mais d’un contrat.
- **Testabilité** : on peut injecter un mock/fake lors des tests unitaires.
- **Évolutivité** : on peut changer la source de données (ex : passer d’Eloquent à une API externe) sans modifier le code métier.
- **Respect du principe SOLID (DIP)** : favorise un code maintenable et évolutif.

## 4. Exemples de patterns utilisés
- **Repository** : centralise l’accès aux données, encapsule les requêtes complexes.
- **Service** : logique métier réutilisable, découplée du contrôleur.
- **Event/Listener** : actions déclenchées sur des événements métiers.
- **Policy** : gestion fine des droits d’accès.
- **Provider** : configuration centralisée des bindings, policies, events.

---

Pour toute question ou besoin de détails sur un point précis, voir les fichiers dans `app/` ou demander une explication ciblée.
