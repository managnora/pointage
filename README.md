# 📌 Projet Pointage

Une application de gestion de pointage développée en PHP 8.3, Symfony, Node.js 20, et intégrée avec Apache.

---

## 🚀 Prérequis

Assurez-vous que les outils suivants sont installés :

- PHP 8.3 (FPM + CLI)
- Apache 2 avec `mod_rewrite`
- MySQL/MariaDB
- Composer
- Node.js (via NVM, version 20 recommandée)
- NPM
- Git
- `nvm` (Node Version Manager)

---

## ⚙️ Installation automatique (recommandée)

1. **Cloner le projet**

```bash
git clone <votre-repo> pointage
cd pointage

```

Pour installer les services, lancer le commande suivant:  
```bash
cd scripts
chmod +x setup-system-logging.sh uninstall-system-logging.sh
./setup-system-logging.sh
```

Pour désinstaller les services, lancer le commande suivant:
```bash
cd scripts
./uninstall-system-logging.sh
```

Pour tester manuellement les services:
```bash
sudo systemctl start system-log-start.service
sudo systemctl start system-log-stop.service
```

Vérifier le contenu du log :
```bash
cat logs/system-events.log
```

Installer tous les dependances, vas sur le container node ou php
```bash
npm install
npm run build
composer install
```

Acceder sur navigateur et copier cette url:
```bash
http://localhost:8084
```

Feature
- Créer un cron pour enregistrer le log dans la base données
- Regrouper les pointages par hebdomadaire
- Regrouper les pointages par mensuel
- Calculer les temps par regroupement
