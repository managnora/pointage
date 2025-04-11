#!/bin/bash

# Définition des variables
date_format="%Y-%m-%d %H:%M:%S"
LOGFILE="$HOME/Documents/logfile.txt"
PROJECT_DIR="my_project"
PROJECT_URL="my_project.local"
PROJECT_PATH="/var/www/"
VHOST_PATH="/etc/apache2/sites-available/$PROJECT_DIR.conf"
SYSTEMD_PATH="/etc/systemd/system"

# Fonction pour loguer les messages
log_message() {
    echo "$(date "+$date_format") - $1"
}

log_message "Début de l'installation et du déploiement..."

# Fonction d'installation des paquets requis
install_packages() {
    log_message "Mise à jour et installation des dépendances..."
    sudo apt update -y
    sudo apt install -y apache2 libapache2-mod-php php8.3 php8.3-cli php8.3-fpm \
        php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-intl php8.3-mysql unzip curl git
}

# Fonction de configuration Apache
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
    sudo a2ensite "$PROJECT_DIR"
    sudo a2enmod rewrite proxy_fcgi
    sudo systemctl restart apache2
}

# Fonction de configuration du projet
configure_project() {
    log_message "Déplacement et configuration du projet..."
    sudo mv "$PROJECT_DIR" "$PROJECT_PATH"
    sudo chown -R www-data:www-data "$PROJECT_PATH$PROJECT_DIR"
    sudo chmod -R 775 "$PROJECT_PATH$PROJECT_DIR/var" "$PROJECT_PATH$PROJECT_DIR/public"
    sudo sed -i 's|APP_ENV=dev|APP_ENV=prod|g' "$PROJECT_PATH$PROJECT_DIR/.env"
    echo "127.0.0.1 $PROJECT_URL" | sudo tee -a /etc/hosts > /dev/null
}

# Fonction de création des services systemd
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

# Fonction de création du script de log
deploy_log_script() {
    log_message "Création du script de log..."
    cat <<EOL > "$HOME/log_time.sh"
#!/bin/bash
echo "\$(date) - \$1"
EOL
    chmod +x "$HOME/log_time.sh"
}

# Exécution des fonctions
install_packages
configure_project
configure_apache
deploy_log_script
create_systemd_services

log_message "Installation et déploiement terminés ! 🚀"
log_message "Accédez à votre projet sur http://$PROJECT_URL"
