# Books List — exercice technique Attineos

Plugin WordPress affichant une sélection de livres issus de l'API publique
[Gutendex](https://gutendex.com/books/), accompagné d'un environnement Docker
permettant de le faire tourner en une commande.

Le plugin est autonome : il n'utilise que les API du cœur de WordPress, n'a
aucune dépendance à installer, et fonctionne sur un thème standard sans qu'un
seul fichier du thème soit modifié.

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

Aucune installation de WordPress, de PHP ou de Composer n'est nécessaire :
tout est fourni par les conteneurs.

Pour installer le plugin sur un WordPress existant : **PHP 8.1+** et
**WordPress 6.4+**.

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

Le site est accessible sur **http://localhost:8080**.
La page de démonstration se trouve sur **http://localhost:8080/books/**.

Le script `init.sh` installe WordPress, active le plugin et configure les
permaliens. Pour repartir de zéro, volumes compris :

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
plugin depuis l'administration. Rien d'autre à faire : l'activation crée une
page « Books » contenant le shortcode, immédiatement consultable.

### Identifiants de l'environnement Docker

Les identifiants de la stack figurent en clair dans `docker-compose.yml`, sans
fichier `.env`. Ce choix est délibéré : il s'agit d'un environnement de
développement jetable, dont le port de la base n'est pas exposé à la machine
hôte et qui ne contient aucune donnée réelle. Ces valeurs ne sont pas des
secrets.

En production, la configuration sensible passe par des variables
d'environnement injectées par la plateforme d'hébergement ou par un
gestionnaire de secrets.

## Utilisation

### Le shortcode

```
[books_list]
```

Trois attributs, tous facultatifs :

| Attribut   | Valeur par défaut           | Effet                                        |
| ---------- | --------------------------- | -------------------------------------------- |
| `heading`  | « Une sélection de livres » | Titre affiché au-dessus de la liste          |
| `search`   | vide                        | Recherche appliquée par défaut               |
| `language` | vide                        | Code de langue ISO 639-1 appliqué par défaut |

```
[books_list heading="Romans français" language="fr"]
```

Les paramètres présents dans l'URL l'emportent sur les attributs : le choix du
visiteur passe avant celui de l'auteur de la page.

L'interface est traduite : les chaînes sources sont en anglais, un catalogue
`fr_FR` complet est fourni dans `languages/`.

### Administration

**Réglages → Books List** affiche la date du dernier appel réussi et propose
trois actions : modifier la durée de cache, forcer un rafraîchissement, vider
le cache. Un lien direct figure sur la ligne du plugin, dans la liste des
extensions.

Les deux boutons sont des points d'entrée `admin_post_*` : la capacité
`manage_options` est vérifiée **avant** le nonce, et l'action se termine par une
redirection portant un code de message plutôt que par un affichage direct — un
rechargement de page ne rejoue donc jamais l'action.

### Personnalisation

Quatre filtres permettent d'adapter le plugin sans le modifier :

| Filtre                    | Rôle                                        |
| ------------------------- | ------------------------------------------- |
| `books_list_request_args` | Modifier les paramètres envoyés à l'API     |
| `books_list_books`        | Modifier la liste de livres avant affichage |
| `books_list_languages`    | Changer les langues proposées par le filtre |
| `books_list_cache_ttl`    | Imposer une durée de cache                  |

`books_list_request_args` s'applique **avant** le calcul de la clé de cache,
`books_list_books` **après** la lecture du cache : deux jeux de paramètres
différents ne peuvent pas se retrouver sous la même entrée.

## Structure du dépôt

```
.
├── books-list/              # LE plugin — seul dossier à copier en production
│   ├── src/                 # Classes, autochargées en PSR-4
│   ├── templates/           # Gabarits d'affichage
│   ├── assets/css/          # Feuille de style
│   └── languages/           # Catalogue de traduction
├── .docker/init.sh          # Installation et configuration du site de démo
├── .github/workflows/       # Intégration continue
├── tests/                   # Tests unitaires (sans bootstrap WordPress)
├── docker-compose.yml
├── Makefile
├── composer.json            # Outils de développement uniquement
├── phpcs.xml.dist
└── phpunit.xml.dist
```

Le plugin est volontairement placé dans un **sous-dossier** plutôt qu'à la
racine : cela sépare le livrable (`books-list/`) de l'outillage de projet
(Docker, CI), qui n'a pas vocation à être déployé.

Conformément aux consignes, ne sont versionnés ni le cœur de WordPress (il vit
dans un volume Docker nommé), ni les dépendances téléchargées (`vendor/`,
`node_modules/`), ni aucun fichier de configuration sensible.

## Outillage qualité

```bash
composer install     # Installe les outils de développement
composer lint        # PHPCS — WordPress Coding Standards
composer lint:fix    # PHPCBF — corrections automatiques
composer test        # PHPUnit — tests unitaires
```

Sans PHP installé localement, la même chose via Docker :

```bash
docker run --rm -v "${PWD}:/app" -w /app composer:2 \
  sh -c "composer install && composer lint && composer test"
```

État actuel : **24 fichiers analysés, 0 erreur** ; **6 tests, 18 assertions**.

Ces outils sont des dépendances de développement uniquement. Le plugin
fonctionne sans `composer install` : il embarque son propre autoloader PSR-4.

L'intégration continue rejoue le lint et les tests sur PHP 8.1 et 8.3, valide
la syntaxe de `docker-compose.yml` et analyse `init.sh` avec ShellCheck.

## Choix techniques

### 1. Un shortcode plutôt qu'un bloc Gutenberg ou une page générée

Le shortcode fonctionne partout : dans l'éditeur de blocs, dans l'éditeur
classique, dans un widget, et dans un `do_shortcode()` appelé depuis un thème.
Il ne demande aucune chaîne de compilation JavaScript, ne dépend d'aucune
version de l'éditeur, et laisse l'auteur de la page choisir où placer la liste.
Un bloc Gutenberg offrirait une meilleure expérience d'édition, au prix d'un
build `@wordpress/scripts` et d'un `vendor/` de développement bien plus lourd
que le livrable lui-même. J'ai trouvé que le dispositif était ici disproportionné par rapport à la demande.

Pour que le plugin soit utilisable sans manipulation, l'activation crée une
page « Books » contenant le shortcode. Aucun fichier de thème n'est touché.

### 2. Choix d'un plugin classique plutôt qu'un mu-plugin

Le plugin expose une fonctionnalité métier dotée d'un cycle de vie propre
(activation, désinstallation) et dépend d'une API tierce : un administrateur
doit pouvoir le désactiver si le service distant devient indisponible. Les
mu-plugins restent réservés au code d'infrastructure qui ne doit précisément
pas pouvoir être désactivé.

### 3. Un autoloader PSR-4 maison, sans Composer à l'exécution

Le plugin n'a aucune dépendance d'exécution : les appels HTTP passent par
`wp_safe_remote_get()` et le cache par l'API Transients. Introduire Composer
uniquement pour l'autochargement créerait une friction inutile — un plugin
copié dans `wp-content/plugins/` sans `composer install` doit fonctionner.
L'autoloader tenant en moins de quarante lignes, il est écrit en dur.

Conséquence assumée : les fichiers portent le nom de la classe (`Book.php`) et
non la forme historique `class-book.php`. Le sniff `WordPress.Files.FileName`
est désactivé dans `phpcs.xml.dist`, avec le commentaire qui justifie la
dérogation.

### 4. Une classe, une responsabilité

`GutendexClient` parle HTTP, `BookRepository` gère le cache, `Renderer` produit
le HTML, `Shortcode` sert de point d'entrée, `Admin\*` tient l'écran de
réglages. `Plugin` se contente de câbler le tout et ne porte aucune règle.

`Book` est un objet valeur immuable qui **n'appelle aucune fonction WordPress** : c'est ce qui permet de le tester unitairement sans charger le
cœur. Le bootstrap des tests ne charge que ce fichier — si la classe se met un
jour à dépendre de WordPress, les tests le signalent immédiatement.

### 5. Un appel réseau validé à trois niveaux

`wp_safe_remote_get()` est préféré à `wp_remote_get()` : il refuse les adresses
privées et de bouclage, ce qui ferme la porte au SSRF si l'URL devenait un jour
configurable. Ensuite, trois vérifications successives :

1. l'absence d'erreur de transport (`is_wp_error`) ;
2. un code HTTP 200 — tout autre code est traité comme un échec ;
3. la forme du JSON, puis chaque entrée individuellement.

`Book::from_array()` renvoie `null` sur une entrée inexploitable, et cette
entrée est simplement écartée : une anomalie ponctuelle de l'API ampute la
liste, elle ne fait pas tomber la page. Les URL sont acceptées uniquement en
`http://` ou `https://`.

### 6. Un cache invalidé par compteur de version

Les résultats sont stockés en transients, sous une clé dérivée des paramètres
de la requête. Vider le cache **n'efface rien** : un entier stocké en option est
incrémenté, et comme il entre dans le calcul de chaque clé, toutes les entrées
précédentes deviennent inatteignables et expirent d'elles-mêmes.

L'alternative habituelle — un `DELETE ... LIKE '_transient_books_list_%'` sur
`wp_options` — parcourt toute la table et, surtout, ne fonctionne pas dès qu'un
cache objet persistant (Redis, Memcached) est installé, puisque les transients
ne sont alors plus en base. Le compteur, lui, marche dans les deux cas.

La durée de cache est réglable depuis l'administration, une heure par défaut.

### 7. Une pagination découplée de celle de l'API

Gutendex renvoie 32 livres par appel ; la liste en affiche 16. Un appel sert
donc deux pages consultées, et chaque page transfère deux fois moins de
couvertures. Mesuré sur un parcours complet de plusieurs pages : **44 % d'appels réseau en moins**.

Le numéro de page est borné avant tout calcul : sans plafond, une valeur
extravagante dans l'URL fait déborder l'arithmétique d'offset en flottant.

### 8. JavaScript et CSS

La liste est présente dans le HTML servi par le serveur : elle est peinte dans
la même passe que le reste de la page. Une version JavaScript serait
mécaniquement plus lente au premier affichage : téléchargement du
script, l'exécution, puis demande des données. Sur la page de démonstration, le
plugin ajoute **1,4 Ko transférés et zéro script**, là où le thème seul en
charge 244 Ko.

Le responsive tient sans une seule media query
(`repeat(auto-fill, minmax(min(24rem, 100%), 1fr))`), et la classe `alignwide`
permet à la grille d'échapper à la largeur maximale imposée par le thème.

Côté accessibilité : section nommée par son titre, `<label for>` sur les deux
champs du formulaire, navigation de pagination étiquetée, mention « ouvre dans
un nouvel onglet » réservée aux lecteurs d'écran, couvertures décoratives en
`alt=""`. L'ensemble est utilisable au clavier seul.

## Limitations connues

- **Dépendance à une API publique tierce.** Le délai d'attente est fixé à 10
  secondes et **les échecs ne sont pas mis en cache** : sur une connexion lente
  ou pendant une indisponibilité de Gutendex, chaque affichage retente et coûte
  10 secondes avant d'afficher le message d'indisponibilité. Mesuré à 10,3 s. 
  Des tests supplémentaires sont à réaliser pour accélérer le temps de chargement, que je trouve un peu long.
- **Les couvertures sont servies par `gutenberg.org`**, en lien direct. Elles
  sont chargées en `loading="lazy"` et ne bloquent donc pas le rendu, mais une
  connexion qui n'atteint pas ce domaine affiche des cartes sans image.
- **La recherche est celle de Gutendex**, qui cherche en sous-chaîne sur le
  titre **et** l'auteur : « carmen » remonte aussi « S*carmen*tado ». Ce n'est
  pas une recherche par titre seul.
  De nombreux échecs de la recherche, probablement liés à la connexion internet un peu lente. Mais des tests supplémentaires sont à réaliser pour s'assurer que le problème vient effectivement de là.
- **Pas de chargement asynchrone** : chaque recherche ou changement de page
  recharge la page entière.
- **Une page au-delà du dernier résultat** affiche « aucun livre ne correspond »
  plutôt qu'une erreur 404.
- **La liste des langues du filtre est fixée à la main** — Gutendex n'expose
  aucun point d'entrée listant ses langues. Elle reste modifiable par filtre.
- **Les clés de cache ne sont pas bornées** : chaque recherche distincte crée
  une entrée de transient.
- **Pas de bloc Gutenberg, pas de point d'entrée REST, pas de commande WP-CLI**,
  et les gabarits ne sont pas surchargeables depuis le thème.
- **Les tests unitaires ne couvrent que `Book`** ; il n'y a pas eu de test
  d'intégration WordPress.
- **Les titres en langue étrangère n'ont pas d'attribut `lang`**, alors que la
  liste est multilingue.


## Pistes de poursuite

Par ordre de valeur ajoutée décroissante :

1. **Cache négatif court** — mémoriser un échec une minute, pour qu'une API
   lente ne pénalise que le premier visiteur au lieu de chacun.
2. **Chargement asynchrone du formulaire** — la page s'afficherait
   immédiatement, la liste se remplirait ensuite. Cela n'accélère pas Gutendex,
   mais rend l'attente supportable.
3. **Servir le dernier résultat connu** avec un bandeau lorsqu'un
   rafraîchissement échoue, plutôt qu'un message d'indisponibilité.
4. **Bloc Gutenberg** s'appuyant sur le même `Renderer`, pour un rendu dans
   l'éditeur.
5. **Tests d'intégration** sur la couche cache et l'écran d'administration, qui
   demandent le jeu de tests WordPress.
6. **Attribut `lang`** sur les titres, et surcharge des gabarits depuis le
   thème via `locate_template()`.
7. ** Des commentaires en multilingue** La partie du plugin est développée avec des commentaires
en anglais, mais les fichiers de configuration (Docker et cie) + le readme sont en français.
Manque de cohérence à ce niveau-là. 

## Temps passé

Environ **3 h 30**, de la conception à la rédaction de ce document.
