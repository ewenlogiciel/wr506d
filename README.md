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


