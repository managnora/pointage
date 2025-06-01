#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

SYSTEMD_DIR="/etc/systemd/system"

echo "🧹 Désinstallation des services et fichiers de log..."

# === Stop et désactivation services ===
for service in system-log-start system-log-stop; do
  sudo systemctl disable --now "$service.service" 2>/dev/null
  sudo rm -f "$SYSTEMD_DIR/$service.service"
done

# === Rechargement systemd ===
sudo systemctl daemon-reload

echo "✅ Nettoyage terminé."
