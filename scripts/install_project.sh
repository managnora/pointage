#!/bin/bash

set -e  # Stopper le script en cas d'erreur

# Définition des variables
date_format="%Y-%m-%d %H:%M:%S"
LOGFILE="$HOME/Documents/logfile.txt"
PROJECT_DIR="pointage"
PROJECT_URL="pointage.local"
PROJECT_PATH="/var/www/"
VHOST_PATH="/etc/apache2/sites-available/$PROJECT_DIR.conf"
SYSTEMD_PATH="/etc/systemd/system"

# Fonction pour loguer les messages
log_message() {
    echo "$(date "+$date_format") - $1"
}

log_error() {
    echo "$(date "+$date_format") - ❌ ERREUR : $1" >&2
}

# Vérifie et installe NVM et Node.js 20
setup_node() {
    log_message "Vérification de NVM et Node.js 20..."

    if ! command -v nvm &> /dev/null; then
        log_message "NVM non trouvé, installation..."
        curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
        export NVM_DIR="$HOME/.nvm"
        [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    else
        log_message "NVM déjà installé."
    fi

    if ! nvm ls 20 &>/dev/null; then
        log_message "Node.js 20 non trouvé, installation..."
        nvm install 20
    fi

    nvm use 20
    log_message "Node.js version utilisée : $(node -v)"
}

log_message "Début de l'installation et du déploiement..."

install_packages() {
    log_message "Installation des paquets requis..."
    sudo apt update -y
    sudo apt install -y apache2 libapache2-mod-php php8.3 php8.3-cli php8.3-fpm \
        php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-intl php8.3-mysql unzip curl git
}

configure_apache() {
    log_message "Configuration d'Apache..."
    sudo tee "$VHOST_PATH" > /dev/null <<EOL
<VirtualHost *:80>
    ServerName $PROJECT_URL
    DocumentRoot $PROJECT_PATH$PROJECT_DIR/public

    <Directory $PROJECT_PATH$PROJECT_DIR/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${PROJECT_DIR}_error.log
    CustomLog \${APACHE_LOG_DIR}/${PROJECT_DIR}_access.log combined

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
EOL
    sudo a2ensite "$PROJECT_DIR.conf"
    sudo a2enmod rewrite proxy_fcgi
    sudo systemctl restart apache2
}
verify_hosts_entry() {
    log_message "Vérification de la présence de $PROJECT_URL dans /etc/hosts..."

    if grep -q "$PROJECT_URL" /etc/hosts; then
        log_message "$PROJECT_URL est déjà présent dans /etc/hosts."
    else
        log_message "$PROJECT_URL absent de /etc/hosts. Ajout en cours..."
        echo "127.0.0.1 $PROJECT_URL" | sudo tee -a /etc/hosts > /dev/null
        log_message "$PROJECT_URL a été ajouté à /etc/hosts."
    fi
}

verify_project_directory() {
    log_message "Vérification de la présence du projet dans $PROJECT_PATH..."
    PROJECT_PATH_INIT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
    if [ -d "$PROJECT_PATH$PROJECT_DIR" ]; then
        log_message "Le projet $PROJECT_DIR existe déjà dans $PROJECT_PATH."
    else
        log_message "Le projet $PROJECT_DIR n'existe pas dans $PROJECT_PATH. Déplacement en cours..."
        sudo mv "$PROJECT_PATH_INIT" "$PROJECT_PATH"
    fi

    sudo chown -R www-data:www-data "$PROJECT_PATH$PROJECT_DIR"
    sudo chmod -R 775 "$PROJECT_PATH$PROJECT_DIR/var" "$PROJECT_PATH$PROJECT_DIR/public"
}

copy_env_file() {
    log_message "Vérification du fichier .env..."

    ENV_FILE="$PROJECT_PATH$PROJECT_DIR/.env"
    ENV_EXAMPLE_FILE="$PROJECT_PATH$PROJECT_DIR/.env.example"

    if [ ! -f "$ENV_FILE" ]; then
        if [ -f "$ENV_EXAMPLE_FILE" ]; then
            sudo cp "$ENV_EXAMPLE_FILE" "$ENV_FILE"
            log_message "Fichier .env copié depuis .env.example."
        else
            log_error ".env.example introuvable. Impossible de créer .env."
            return 1
        fi
    else
        log_message "Le fichier .env existe déjà. Aucune copie nécessaire."
    fi
}

update_app_env() {
    log_message "Mise à jour de APP_ENV dans le fichier .env..."

    ENV_FILE="$PROJECT_PATH$PROJECT_DIR/.env"

    if [ ! -f "$ENV_FILE" ]; then
        log_message "Erreur : Le fichier .env n'existe pas à l'emplacement $ENV_FILE."
        return 1
    fi

    if grep -q "^APP_ENV=" "$ENV_FILE"; then
        sudo sed -i 's|^APP_ENV=.*|APP_ENV=prod|' "$ENV_FILE"
        log_message "APP_ENV mis à jour en prod."
    else
        echo "APP_ENV=prod" | sudo tee -a "$ENV_FILE" > /dev/null
        log_message "APP_ENV ajouté et configuré en prod."
    fi

    # Vérifie si la variable existe déjà
    if grep -q "^LOG_FILE_PATH=" "$ENV_FILE"; then
        sed -i "s|^LOG_FILE_PATH=.*|LOG_FILE_PATH=$LOGFILE|" "$ENV_FILE"
    else
        echo "LOG_FILE_PATH=$LOGFILE" >> "$ENV_FILE"
    fi
}

configure_project() {
    log_message "Déplacement et configuration du projet..."
    verify_project_directory
    copy_env_file
    update_app_env
    verify_hosts_entry
}

install_project() {
    log_message "Installation du projet..."

    # Forcer PHP 8.3
    log_message "Configuration de PHP 8.3 par défaut..."
    sudo update-alternatives --set php /usr/bin/php8.3

    # Se déplacer dans le dossier projet
    PROJECT_FULL_PATH="$PROJECT_PATH$PROJECT_DIR"
    if [ ! -d "$PROJECT_FULL_PATH" ]; then
        log_error "Le dossier projet $PROJECT_FULL_PATH n'existe pas."
        exit 1
    fi

    # Installer les dépendances PHP
    if [ -f "composer.json" ]; then
        log_message "Installation des dépendances Composer..."
        composer install --no-interaction --prefer-dist || { log_error "Erreur lors de l'installation Composer."; exit 1; }
    else
        log_error "Aucun fichier composer.json trouvé, saut de Composer."
        exit 1;
    fi

    # Installer les dépendances NPM
    if [ -f "package.json" ]; then
        log_message "Installation des dépendances npm..."
        npm install || { log_error "Erreur lors de l'installation npm."; exit 1; }

        # Build du projet
        log_message "Compilation du projet (npm run build)..."
        npm run build || { log_error "Erreur lors de la compilation npm."; exit 1; }
    else
        log_error "Aucun fichier package.json trouvé, saut de npm."
        exit 1;
    fi

    # Vider le cache Symfony
    if [ -f "bin/console" ]; then
        log_message "Nettoyage du cache Symfony..."
        php bin/console cache:clear --no-warmup || { log_error "Erreur lors du cache:clear Symfony."; exit 1; }
    else
        log_error "Aucun fichier bin/console trouvé, saut du cache Symfony."
    fi
}

create_systemd_services() {
    log_message "Création des services systemd..."
    cat <<EOL | sudo tee "$SYSTEMD_PATH/log_start.service" > /dev/null
[Unit]
Description=Log system start time
After=network.target

[Service]
Type=oneshot
ExecStart=$HOME/log_time.sh Start

[Install]
WantedBy=multi-user.target
EOL

    cat <<EOL | sudo tee "$SYSTEMD_PATH/log_shutdown.service" > /dev/null
[Unit]
Description=Log system shutdown time
Before=shutdown.target

[Service]
Type=oneshot
ExecStart=$HOME/log_time.sh Stop

[Install]
WantedBy=shutdown.target
EOL
    sudo systemctl enable log_start.service log_shutdown.service
    sudo systemctl start log_start.service
}

deploy_log_script() {
    log_message "Création du script de log..."
    cat <<EOL > "$HOME/log_time.sh"
#!/bin/bash
echo "\$(date) - \$1" >> \$LOGFILE
EOL
    chmod +x "$HOME/log_time.sh"
}

verify_apache() {
    log_message "Vérification d'Apache..."

    if ! command -v apache2 &> /dev/null; then
        log_error "Apache2 n'est pas installé ou introuvable."
        exit 1
    fi

    if ! sudo systemctl is-active --quiet apache2; then
        log_message "Apache n'est pas actif, tentative de démarrage..."
        sudo systemctl start apache2
    fi

    log_message "Apache est actif."
}

verify_php_fpm() {
    log_message "Vérification de PHP-FPM..."

    if ! systemctl list-units --type=service | grep -q php8.3-fpm.service; then
        log_error "Le service PHP-FPM (php8.3-fpm) n'est pas détecté."
        exit 1
    fi

    if ! sudo systemctl is-active --quiet php8.3-fpm; then
        log_message "PHP-FPM n'est pas actif, tentative de démarrage..."
        sudo systemctl start php8.3-fpm
    fi

    log_message "PHP-FPM est actif."
}

# Execution
log_message "Début de l'installation et du déploiement..."

install_packages
configure_project
install_project
verify_apache
verify_php_fpm
configure_apache
deploy_log_script
create_systemd_services

log_message "✅ Installation et déploiement terminés ! 🚀"
log_message "🌐 Accédez à votre projet sur http://$PROJECT_URL"
