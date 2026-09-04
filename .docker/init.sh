#!/bin/sh
#
# Installation et configuration du site de démonstration.
# Exécuté dans le conteneur wpcli : voir `make install` ou le README.
#
# Le script est idempotent : le relancer sur une installation existante
# ne casse rien et ne réinstalle pas.

set -eu

# Ces variables sont fournies par le service wpcli de docker-compose.yml.
# La syntaxe :? fait echouer le script avec un message clair si l'une manque,
# plutot que de retomber silencieusement sur une valeur devinable.
: "${WP_URL:?definie dans docker-compose.yml, service wpcli}"
: "${WP_TITLE:?definie dans docker-compose.yml, service wpcli}"
: "${WP_ADMIN_USER:?definie dans docker-compose.yml, service wpcli}"
: "${WP_ADMIN_PASSWORD:?definie dans docker-compose.yml, service wpcli}"
: "${WP_ADMIN_EMAIL:?definie dans docker-compose.yml, service wpcli}"
: "${WP_LOCALE:=fr_FR}"

PLUGIN_SLUG="books-list"

log() { printf '\033[0;36m==>\033[0m %s\n' "$1"; }

# --- 1. Attendre que l'image WordPress ait déposé wp-config.php -------------
log "Attente de l'initialisation de WordPress..."
i=0
while [ ! -f /var/www/html/wp-config.php ]; do
    i=$((i + 1))
    if [ "$i" -ge 30 ]; then
        echo "ERREUR : wp-config.php absent après 60 s. Le service wordpress a-t-il démarré ?" >&2
        exit 1
    fi
    sleep 2
done

# --- 2. Installer WordPress si nécessaire -----------------------------------
if wp core is-installed 2>/dev/null; then
    log "WordPress est déjà installé — installation ignorée."
else
    log "Installation de WordPress..."
    wp core install \
        --url="$WP_URL" \
        --title="$WP_TITLE" \
        --admin_user="$WP_ADMIN_USER" \
        --admin_password="$WP_ADMIN_PASSWORD" \
        --admin_email="$WP_ADMIN_EMAIL" \
        --skip-email
fi

# --- 3. Langue --------------------------------------------------------------
if [ "$WP_LOCALE" != "en_US" ]; then
    log "Installation de la locale $WP_LOCALE..."
    wp language core install "$WP_LOCALE" --activate || \
        echo "AVERTISSEMENT : locale $WP_LOCALE indisponible, on reste en anglais."
fi

# --- 4. Thème standard ------------------------------------------------------
log "Activation d'un thème WordPress standard..."
for theme in twentytwentyfive twentytwentyfour; do
    if wp theme is-installed "$theme" 2>/dev/null; then
        wp theme activate "$theme"
        break
    fi
done

# --- 5. Réglages de confort --------------------------------------------------
wp rewrite structure '/%postname%/'
wp option update timezone_string 'Europe/Paris'
wp option update blogdescription 'Exercice technique - liste de livres Gutendex'

# --- 6. Activation du plugin -------------------------------------------------
# L'activation déclenche register_activation_hook(), qui crée lui-même la page
# de démonstration contenant le shortcode. Rien à faire de plus ici.
if wp plugin is-installed "$PLUGIN_SLUG" 2>/dev/null; then
    log "Activation du plugin $PLUGIN_SLUG..."
    wp plugin activate "$PLUGIN_SLUG"
else
    log "Plugin $PLUGIN_SLUG pas encore présent — étape ignorée."
fi

# --- 7. Nettoyage -------------------------------------------------------------
wp plugin delete akismet hello 2>/dev/null || true

log "Terminé. Site disponible sur $WP_URL"
log "Identifiants d'administration : voir le service wpcli dans docker-compose.yml"
