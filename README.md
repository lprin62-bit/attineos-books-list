# Books List — exercice technique Attineos

Plugin WordPress affichant une sélection de livres issus de l'API publique
[Gutendex](https://gutendex.com/books/), accompagné d'un environnement Docker
permettant de le faire tourner en une commande.

> **État du dépôt** — la couche projet (Docker, outillage qualité, CI) est en
> place. Le plugin lui-même est en cours de développement.

---

## Sommaire

- [Prérequis](#prérequis)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Structure du dépôt](#structure-du-dépôt)
- [Outillage qualité](#outillage-qualité)
- [Choix techniques](#choix-techniques)
- [Limitations connues](#limitations-connues)
- [Pistes de poursuite](#pistes-de-poursuite)
- [Temps passé](#temps-passé)

---

## Prérequis

- Docker et Docker Compose v2
- Un port libre en `8080`
- _Pour l'outillage qualité uniquement_ : PHP 8.1+ et Composer en local

Aucune installation de WordPress ni de PHP n'est nécessaire pour faire tourner
la démonstration : tout est fourni par les conteneurs.

## Installation

```bash
git clone <url-du-depot>
cd exercice-attineos
docker compose up -d
docker compose run --rm --entrypoint /bin/sh wpcli /scripts/init.sh
```

Ou, si `make` est disponible :

```bash
make up
```

Le site est alors accessible sur **http://localhost:8080**

Le script `init.sh` est **idempotent** : le relancer sur une installation
existante ne réinstalle rien. Pour repartir de zéro, volumes compris :

```bash
make reset      # ou : docker compose down -v && make up
```

> **Windows** — `make` n'est pas disponible nativement. Utiliser WSL, ou les
> commandes `docker compose` détaillées ci-dessus.

> **Port 8080 occupé ?** Créer un `docker-compose.override.yml` local
> redéfinissant le mappage de ports : Compose le fusionne automatiquement.
> Ce fichier n'est pas versionné.

### Installation sur un WordPress existant

Copier le dossier `books-list/` dans `wp-content/plugins/`, puis activer le
plugin depuis l'administration. Aucune dépendance à installer : le plugin
n'utilise que les API du cœur de WordPress.

## Utilisation

<!-- À compléter une fois le plugin implémenté. -->

_Section à compléter : shortcode, attributs disponibles, page d'administration._

## Structure du dépôt

```
.
├── books-list/              # LE plugin — seul dossier à copier en production
├── .docker/init.sh          # Installation et configuration du site de démo
├── .github/workflows/       # Intégration continue
├── docs/screenshots/        # Captures d'écran référencées dans ce README
├── tests/                   # Tests unitaires (sans bootstrap WordPress)
├── docker-compose.yml
├── Makefile
├── composer.json            # Outils de développement uniquement
├── phpcs.xml.dist
└── phpunit.xml.dist
```

Le plugin est volontairement placé dans un **sous-dossier** plutôt qu'à la
racine : cela sépare le livrable (`books-list/`) de l'outillage de projet
(Docker, CI, captures), qui n'a pas vocation à être déployé en production.

Conformément aux consignes, ne sont versionnés ni le cœur de WordPress
(il vit dans un volume Docker nommé), ni les dépendances téléchargées
(`vendor/`, `node_modules/`).

## Outillage qualité

```bash
composer install     # Installe les outils de développement
composer lint        # PHPCS — WordPress Coding Standards
composer lint:fix    # PHPCBF — corrections automatiques
composer test        # PHPUnit — tests unitaires
```

Ces outils sont des **dépendances de développement uniquement**. Le plugin
fonctionne sans `composer install` : il embarque son propre autoloader PSR-4.

L'intégration continue exécute PHPCS sur PHP 8.1 et 8.3, valide la syntaxe de
`docker-compose.yml` et analyse `init.sh` avec ShellCheck.

## Choix techniques

### Plugin classique plutôt que mu-plugin

Le plugin expose une fonctionnalité métier dotée d'un cycle de vie propre
(activation, désactivation, désinstallation) et dépend d'une API tierce : un
administrateur doit pouvoir le désactiver si le service distant devient
indisponible. Les mu-plugins restent réservés au code d'infrastructure qui ne
doit précisément pas pouvoir être désactivé.

### Autoloader PSR-4 maison, sans Composer au runtime

Le plugin n'a aucune dépendance d'exécution : les appels HTTP passent par
`wp_remote_get()` et le cache par l'API Transients. Introduire Composer
uniquement pour l'autoloading créerait une friction inutile — un plugin copié
dans `wp-content/plugins/` sans `composer install` doit fonctionner. Composer
reste utilisé pour l'outillage de développement.

### Nommage des fichiers

L'autoloader étant PSR-4, les fichiers portent le nom de la classe
(`Book.php`) et non la forme historique `class-book.php`. Le sniff
`WordPress.Files.FileName` est explicitement désactivé dans `phpcs.xml.dist`,
avec le commentaire justifiant la dérogation.

### Identifiants de l'environnement Docker

Les identifiants de la stack figurent en clair dans `docker-compose.yml`, sans
fichier `.env` ni mécanisme de masquage. Ce choix est délibéré : il s'agit d'un
environnement de développement jetable, dont le port MySQL n'est pas exposé à
la machine hôte et qui ne contient aucune donnée réelle. Ces valeurs ne
constituent pas des secrets, et introduire une gestion de secrets pour des
non-secrets brouillerait la lecture plutôt que d'apporter une garantie.

En production, la configuration sensible passerait par des variables
d'environnement injectées par la plateforme d'hébergement ou par un
gestionnaire de secrets — jamais par un fichier versionné.

Le durcissement présent dans la stack porte sur ce qui compte réellement :
base de données inaccessible depuis l'hôte, `DISALLOW_FILE_EDIT` actif,
et `WP_DEBUG_DISPLAY` désactivé au profit d'un journal.

### Reste à documenter

- Mode d'intégration retenu (shortcode / bloc) et raison du choix
- Stratégie de cache et mécanisme d'invalidation
- Sécurisation des actions d'administration
- Approche accessibilité et responsive

## Limitations connues

<!-- À compléter. -->

## Pistes de poursuite

<!-- Ce que j'aurais implémenté avec davantage de temps. -->

## Temps passé

| Phase                             | Durée         |
| --------------------------------- | ------------- |
| Conception et architecture        | _à compléter_ |
| Environnement Docker et outillage | _à compléter_ |
| Développement du plugin           | _à compléter_ |
| Documentation                     | _à compléter_ |
| **Total**                         | _à compléter_ |
