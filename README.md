## wr506d - Backend
API Backend développée avec Symfony 7 et API Platform, propulsée par GraphQL et conteneurisée avec Docker.

## Prérequis
Docker et Docker Compose

Symfony CLI (optionnel)

PHP 8.3.11 (en local ou via Docker)

## Installation rapide
### 1. Clonage et Environnement

```bash

git clone git@github.com:ewenlogiciel/wr506d.git

cd wr506d

# Passage sur la branche develop
git checkout develop

# Configuration des variables d'environnement
cp .env .env.local
```

## 2. Lancement de l'infrastructure Docker

```bash

docker compose up -d --build
```

## 3. Initialisation complète (One-Liner)

```bash
# Installe les dépendances, génère les clés JWT, crée la BDD, joue les migrations et les fixtures
docker compose exec php sh -c "
composer install &&
bin/console lexik:jwt:generate-keypair --skip-if-exists &&
bin/console doctrine:database:create --if-not-exists &&
bin/console doctrine:migrations:migrate --no-interaction &&
bin/console doctrine:fixtures:load --no-interaction
"
```
Note : Si la commande ci-dessus échoue ou si vous préférez le faire manuellement, connectez-vous au conteneur :

```bash

docker exec -ti symfony-web-2025 /bin/bash
```

   Une fois dans le conteneur (symfony-web-2025), lancez ces commandes :

```bash

composer install

docker compose exec php bin/console lexik:jwt:generate-keypair

bin/console doctrine:database:create

bin/console doctrine:migrations:migrate --no-interaction

bin/console doctrine:fixtures:load --no-interaction
```


## Accès aux services
Interface REST : http://localhost:8319/api

Interface GraphQL : http://localhost:8319/api/graphql

## Endpoints de l'API

### Authentification

L'API utilise JWT (JSON Web Tokens) pour l'authentification.

#### Obtenir un token
```http
POST /auth
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

Réponse :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

#### Utiliser le token
Ajouter le header Authorization à chaque requête authentifiée :
```
Authorization: Bearer <token>
```

---

### Inscription

#### Créer un nouvel utilisateur
```http
POST http://localhost:8319/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```
Retourne un token JWT et les informations de l'utilisateur créé.

#### Récupérer l'utilisateur actuel
```http
GET http://localhost:8319/api/me
Authorization: Bearer {token}
```
Retourne les informations de l'utilisateur connecté.

### Authentification à deux facteurs (2FA)

#### Configuration du 2FA
```http
POST http://localhost:8319/api/2fa/setup
Authorization: Bearer {token}
```
Génère un secret 2FA et retourne le QR code à scanner avec une application d'authentification.

#### Activation du 2FA
```http
POST http://localhost:8319/api/2fa/enable
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "123456"
}
```
Active le 2FA après vérification du code et retourne des codes de secours.

### Ressources API Platform (REST)

Toutes les ressources ci-dessous nécessitent un token JWT.

#### Movies (Films)
```http
GET    http://localhost:8319/api/movies         # Liste tous les films (300 par page)
GET    http://localhost:8319/api/movies/{id}    # Récupère un film par son ID
POST   http://localhost:8319/api/movies         # Crée un nouveau film
PUT    http://localhost:8319/api/movies/{id}    # Met à jour un film
PATCH  http://localhost:8319/api/movies/{id}    # Met à jour partiellement un film
DELETE http://localhost:8319/api/movies/{id}    # Supprime un film
```

Filtres disponibles:
- `?name=` - Recherche partielle par nom
- `?director=` - Filtre par réalisateur (ID exact)
- `?order[releaseData]=asc|desc` - Tri par date de sortie

#### Actors (Acteurs)
```http
GET    http://localhost:8319/api/actors         # Liste tous les acteurs
GET    http://localhost:8319/api/actors/{id}    # Récupère un acteur par son ID
POST   http://localhost:8319/api/actors         # Crée un nouvel acteur
PUT    http://localhost:8319/api/actors/{id}    # Met à jour un acteur
PATCH  http://localhost:8319/api/actors/{id}    # Met à jour partiellement un acteur
DELETE http://localhost:8319/api/actors/{id}    # Supprime un acteur
```

Filtres disponibles:
- `?lastname=` - Recherche par nom (commence par)
- `?firstname=` - Recherche par prénom (commence par)
- `?dob=` - Filtre par date de naissance
- `?exists[dod]=true|false` - Filtre les acteurs décédés/vivants

#### Directors (Réalisateurs)
```http
GET    http://localhost:8319/api/directors         # Liste tous les réalisateurs (200 par page)
GET    http://localhost:8319/api/directors/{id}    # Récupère un réalisateur par son ID
POST   http://localhost:8319/api/directors         # Crée un nouveau réalisateur
PUT    http://localhost:8319/api/directors/{id}    # Met à jour un réalisateur
PATCH  http://localhost:8319/api/directors/{id}    # Met à jour partiellement un réalisateur
DELETE http://localhost:8319/api/directors/{id}    # Supprime un réalisateur
```

#### Categories (Catégories)
```http
GET    http://localhost:8319/api/categories         # Liste toutes les catégories
GET    http://localhost:8319/api/categories/{id}    # Récupère une catégorie par son ID
POST   http://localhost:8319/api/categories         # Crée une nouvelle catégorie
PUT    http://localhost:8319/api/categories/{id}    # Met à jour une catégorie
PATCH  http://localhost:8319/api/categories/{id}    # Met à jour partiellement une catégorie
DELETE http://localhost:8319/api/categories/{id}    # Supprime une catégorie
```

#### Comments (Commentaires)
```http
GET    http://localhost:8319/api/comments         # Liste tous les commentaires
GET    http://localhost:8319/api/comments/{id}    # Récupère un commentaire par son ID
POST   http://localhost:8319/api/comments         # Crée un nouveau commentaire
PUT    http://localhost:8319/api/comments/{id}    # Met à jour un commentaire
PATCH  http://localhost:8319/api/comments/{id}    # Met à jour partiellement un commentaire
DELETE http://localhost:8319/api/comments/{id}    # Supprime un commentaire
```

#### Users (Utilisateurs)
```http
GET    http://localhost:8319/api/users         # Liste tous les utilisateurs
GET    http://localhost:8319/api/users/{id}    # Récupère un utilisateur par son ID
POST   http://localhost:8319/api/users         # Crée un nouvel utilisateur
PATCH  http://localhost:8319/api/users/{id}    # Met à jour partiellement un utilisateur
DELETE http://localhost:8319/api/users/{id}    # Supprime un utilisateur
```

#### Media Objects (Fichiers médias)
```http
GET  http://localhost:8319/api/media_objects         # Liste tous les objets médias
GET  http://localhost:8319/api/media_objects/{id}    # Récupère un objet média par son ID
POST http://localhost:8319/api/media_objects         # Upload un nouveau fichier média
```

Pour l'upload, utilisez:
```http
POST http://localhost:8319/api/media_objects
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [votre fichier]
```

### Pages web

#### Page de démonstration
```http
GET http://localhost:8319/demo
```

#### Liste des produits
```http
GET http://localhost:8319/products
```

#### Détail d'un produit
```http
GET http://localhost:8319/product/{id}
```

### GraphQL

Interface GraphQL disponible à http://localhost:8319/api/graphql

Toutes les requêtes GraphQL nécessitent un token JWT:
```
Authorization: Bearer {token}
```

Exemple de requête GraphQL:
```graphql
query {
  movies {
    edges {
      node {
        id
        name
        description
        duration
        releaseData
        director {
          firstname
          lastname
        }
        actors {
          edges {
            node {
              fullName
            }
          }
        }
      }
    }
  }
}
```

Exemple de mutation GraphQL:
```graphql
mutation {
  createMovie(input: {
    name: "Inception"
    description: "A thief who steals corporate secrets..."
    duration: 148
    releaseData: "2010-07-16"
  }) {
    movie {
      id
      name
    }
  }
}
```


