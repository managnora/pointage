#!/bin/bash

# === CONFIGURATION ===
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$PROJECT_ROOT/logs"
LOG_FILE="$LOG_DIR/system-events.log"

START_SCRIPT="$SCRIPT_DIR/log-system-start.sh"
STOP_SCRIPT="$SCRIPT_DIR/log-system-stop.sh"

SYSTEMD_DIR="/etc/systemd/system"

ENV_FILE="$PROJECT_ROOT/.env"
ENV_EXAMPLE_FILE="$PROJECT_ROOT/.env.example"

echo "📁 Projet détecté à : $PROJECT_ROOT"

# === 0. Copier le fichier .env s'il n'existe pas ===
if [ ! -f "$ENV_FILE" ] && [ -f "$ENV_EXAMPLE_FILE" ]; then
  cp "$ENV_EXAMPLE_FILE" "$ENV_FILE"
  echo "✅ Fichier .env copié depuis .env.example"
else
  echo "ℹ️  Fichier .env déjà présent ou .env.example introuvable"
fi

# === 1. Créer dossier logs ===
mkdir -p "$LOG_DIR"
touch "$LOG_FILE"
chmod 666 "$LOG_FILE"
echo "✅ Fichier log : $LOG_FILE"

# === 2. Créer les scripts shell ===
cat <<EOF > "$START_SCRIPT"
#!/bin/bash
export LC_TIME=fr_FR.UTF-8
echo "\$(date +'%e %b %Y %H:%M:%S') - Start" >> "$LOG_FILE"
EOF

cat <<EOF > "$STOP_SCRIPT"
#!/bin/bash
export LC_TIME=fr_FR.UTF-8
echo "\$(date +'%e %b %Y %H:%M:%S') - Stop" >> "$LOG_FILE"
EOF

chmod +x "$START_SCRIPT" "$STOP_SCRIPT"
echo "✅ Scripts shell créés dans $SCRIPT_DIR"

# === 3. Créer services systemd ===

# START
cat <<EOF | sudo tee "$SYSTEMD_DIR/system-log-start.service" > /dev/null
[Unit]
Description=Log system startup
After=multi-user.target

[Service]
Type=oneshot
ExecStart=$START_SCRIPT

[Install]
WantedBy=multi-user.target
EOF

# STOP
cat <<EOF | sudo tee "$SYSTEMD_DIR/system-log-stop.service" > /dev/null
[Unit]
Description=Log system shutdown
DefaultDependencies=no
Before=shutdown.target

[Service]
Type=oneshot
ExecStart=$STOP_SCRIPT

[Install]
WantedBy=halt.target reboot.target shutdown.target
EOF

# === 4. Activer services ===
sudo systemctl daemon-reexec
sudo systemctl daemon-reload

sudo systemctl enable system-log-start.service
sudo systemctl enable system-log-stop.service

echo "✅ Tous les services activés."
