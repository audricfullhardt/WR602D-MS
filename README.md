# Mini Golf — Micro-service Mailer

Micro-service Symfony qui envoie les e-mails transactionnels du jeu de mini-golf 3D.

Il expose une petite API HTTP. Le back-end du jeu l'appelle pour avertir un joueur
quand un score est enregistré (`score-created`) ou quand quelqu'un lui partage un
score (`score-shared`). Le service construit l'e-mail puis le remet au serveur SMTP.

L'API est protégée par une clé d'API (header `X-API-KEY`) et restreinte par CORS.

---

## Prérequis

| Outil          | Version    |
| -------------- | ---------- |
| PHP            | 8.4+       |
| Composer       | 2+         |
| Symfony CLI    | dernière   |
| Docker         | dernière   |
| Docker Compose | dernière   |

Le serveur SMTP de développement (Mailpit) tourne dans Docker. Aucune installation
SMTP locale n'est nécessaire.

---

## Installation et démarrage

### 1. Cloner le dépôt

```bash
git clone https://github.com/audricfullhardt/WR602D-MS.git
cd WR602D-MS
```

### 2. Démarrer Mailpit (SMTP de dev)

```bash
docker compose up -d
```

Mailpit écoute le SMTP sur le port `1025` et sert son interface web sur le port `8025`.

### 3. Installer les dépendances PHP

```bash
composer install
```

### 4. Lancer le serveur de développement

```bash
symfony server:start --port=8001
```

L'API est alors disponible sur `http://localhost:8001`.

---

## Variables d'environnement

Les valeurs par défaut sont dans `.env` (versionné). **Ne modifiez pas `.env`** : créez
un fichier `.env.local` (non versionné) pour vos surcharges locales, en particulier la
clé d'API.

`.env.local` :

```dotenv
# Serveur SMTP. En dev, pointe vers Mailpit.
MAILER_DSN=smtp://localhost:1025

# Sécurité API : nom du header et valeur attendue.
API_KEY_HEADER=X-API-KEY
API_KEY_VALUE=change-me-api-key

# Identité de l'expéditeur appliquée à tous les e-mails.
MAILER_NO_REPLY_EMAIL=no-reply@minigolf.local
MAILER_FROM_NAME="Mini Golf"
```

| Variable                | Rôle                                                          |
| ----------------------- | ------------------------------------------------------------- |
| `MAILER_DSN`            | Adresse du serveur SMTP. `smtp://localhost:1025` pour Mailpit. |
| `API_KEY_HEADER`        | Nom du header HTTP qui porte la clé d'API.                    |
| `API_KEY_VALUE`         | Valeur secrète attendue dans ce header.                      |
| `MAILER_NO_REPLY_EMAIL` | Adresse `From` de tous les e-mails envoyés.                  |
| `MAILER_FROM_NAME`      | Nom affiché de l'expéditeur.                                  |

> Changez toujours `API_KEY_VALUE` pour une vraie valeur secrète. Ne la commitez jamais.

---

## Endpoints disponibles

Toutes les routes sont préfixées par `/mail` et demandent le header d'API.

| Méthode | URL                   | Header requis        | Payload (JSON)                                          | Réponse                                                      |
| ------- | --------------------- | -------------------- | ------------------------------------------------------- | ----------------------------------------------------------- |
| POST    | `/mail/score-created` | `X-API-KEY: <clé>`   | `to`, `username`, `strokes`, `holeNumber`               | `202 Accepted` — `{"status":"sent","type":"score-created","to":"..."}` |
| POST    | `/mail/score-shared`  | `X-API-KEY: <clé>`   | `to`, `username`, `strokes`, `holeNumber`, `shareUrl`   | `202 Accepted` — `{"status":"sent","type":"score-shared","to":"..."}`  |

### Champs du payload

| Champ        | Type   | Description                                            |
| ------------ | ------ | ------------------------------------------------------ |
| `to`         | string | E-mail du destinataire. Doit être un e-mail valide.    |
| `username`   | string | Pseudo du joueur.                                      |
| `strokes`    | int    | Nombre de coups joués.                                 |
| `holeNumber` | int    | Numéro du trou.                                        |
| `shareUrl`   | string | (score-shared) URL de partage. Doit être une URL valide. |

### Codes de réponse

| Code | Signification                                                  |
| ---- | -------------------------------------------------------------- |
| 202  | E-mail accepté et remis au serveur SMTP.                       |
| 401  | Clé d'API manquante ou invalide.                               |
| 422  | Champ requis manquant/vide, e-mail ou URL invalide.            |

Exemple de réponse 422 :

```json
{ "status": "error", "message": "Missing or empty required field(s): username." }
```

---

## Sécurisation

### Header `X-API-KEY`

Chaque requête doit porter le header d'API (par défaut `X-API-KEY`). Sa valeur est
comparée à `API_KEY_VALUE` avec `hash_equals` (comparaison à temps constant).

- Header absent ou vide → `401 Unauthorized`, message `Missing API key.`
- Valeur incorrecte → `401 Unauthorized`, message `Invalid API key.`

Toutes les routes (`access_control: ^/`) exigent un appelant authentifié. Il n'y a pas
de session : le firewall est `stateless`, seule la clé authentifie la requête.

### CORS

Les requêtes navigateur sont filtrées par `nelmio/cors-bundle` sur le préfixe `/mail` :

- **Origines autorisées** : tout `localhost` / `127.0.0.1` (n'importe quel port) via
  `CORS_ALLOW_ORIGIN`, plus l'origine du back-end via `CORS_BACK_URL`.
- **Méthodes autorisées** : `POST`, `OPTIONS`.
- **Headers autorisés** : `Content-Type`, `X-API-KEY`.

Les origines sont configurables dans `.env` / `.env.local`.

---

## Test avec curl

Remplacez `change-me-api-key` par votre `API_KEY_VALUE`.

### score-created

```bash
curl -X POST http://localhost:8001/mail/score-created \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: change-me-api-key" \
  -d '{
    "to": "joueur@example.com",
    "username": "Tiger",
    "strokes": 3,
    "holeNumber": 7
  }'
```

### score-shared

```bash
curl -X POST http://localhost:8001/mail/score-shared \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: change-me-api-key" \
  -d '{
    "to": "ami@example.com",
    "username": "Tiger",
    "strokes": 3,
    "holeNumber": 7,
    "shareUrl": "https://minigolf.local/scores/42"
  }'
```

Réponse attendue dans les deux cas : `202 Accepted`.

---

## Interface Mailpit

En développement, les e-mails ne partent pas vers de vraies boîtes : ils sont capturés
par Mailpit. Ouvrez l'interface web pour les consulter :

```
http://localhost:8025
```

Chaque e-mail envoyé via l'API y apparaît, avec ses versions texte et HTML.

---

## Tests PHPUnit

```bash
php bin/phpunit
```

En environnement de test, le `MAILER_DSN` vaut `null://null` (aucun e-mail réel n'est
envoyé) et une clé d'API dédiée est utilisée (voir `.env.test`). Les tests couvrent
le contrôleur (`tests/Controller/MailControllerTest.php`) : authentification, validation
des champs et réponses des endpoints.

---

## Qualité de code et CI

- **PHP 8.4 strict** : tous les fichiers déclarent `strict_types=1`.
- **Conventions** : style imposé par `.editorconfig` (indentation, fins de ligne).
- **Tests** : `php bin/phpunit` doit passer avant tout commit.

> Aucun pipeline CI n'est encore configuré dans ce dépôt. Pour en ajouter un (ex.
> GitHub Actions), exécutez sur chaque push : `composer install`, puis `php bin/phpunit`.

---

## Structure du projet

```
src/
├── Kernel.php                         # Noyau Symfony
├── Controller/
│   └── MailController.php             # Routes /mail/* : décode le JSON, valide, déclenche l'envoi
├── Security/
│   └── ApiAuthenticator.php           # Vérifie le header X-API-KEY sur chaque requête
├── Service/
│   ├── MailerService.php              # Construit l'e-mail (From + builder) et l'envoie via SMTP
│   └── Utils/
│       └── RequestChecker.php         # Valide champs requis, e-mail et URL ; renvoie une erreur 422
└── Email/
    ├── EmailBuilderInterface.php      # Contrat commun des builders d'e-mail
    ├── ScoreCreatedEmailBuilder.php   # Modèle d'e-mail "score enregistré"
    └── ScoreSharedEmailBuilder.php    # Modèle d'e-mail "score partagé"
```

Autres dossiers utiles :

- `config/` — configuration Symfony (sécurité, CORS, mailer, services).
- `tests/` — tests PHPUnit.
- `docker/` — config Nginx / Supervisor / entrypoint pour l'image Docker.
- `docker-compose.yml` — service Mailpit pour le développement.
```
