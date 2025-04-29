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

Lancer le script d'installation
```bash
chmod +x install.sh
./install.sh
```

Feature
- Créer un cron pour enregistrer le log dans la base données
- Regrouper les pointages par hebdomadaire
- Regrouper les pointages par mensuel
- Calculer les temps par regroupement
