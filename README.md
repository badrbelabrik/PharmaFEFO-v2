
# PharmaFEFO - Application d'Optimisation des Stocks Pharmaceutiques

## 📋 Description

PharmaFEFO est une application web de gestion des stocks pharmaceutiques basée sur la méthode **FEFO (First Expired, First Out)**. Son objectif est d'aider les pharmacies d'officine et les cliniques à optimiser la gestion des médicaments en réduisant les pertes liées aux produits périmés et en garantissant une meilleure traçabilité des lots.

L'application permet de :
- Gérer les entrées de stock avec numéro de lot et date de péremption.
- Surveiller les produits proches de la péremption avec des alertes colorées.
- Générer des alertes selon le niveau de criticité (Vert, Orange, Rouge).
- Appliquer automatiquement la règle FEFO lors des sorties de stock.
- Déclarer les lots périmés pour destruction.
- Générer des rapports financiers sur les pertes de stock.
- Gérer les retours fournisseurs.

---

## 🚀 Fonctionnalités

### 📦 EPIC 1 : Réception & Entrées Intelligentes
- **US 1.1** : Réception asynchrone des commandes avec saisie du lot et date de péremption.
- Validation des données avant enregistrement (date de péremption non antérieure).
- Soumission du formulaire sans rechargement de page (JavaScript + Fetch API).
- Messages de confirmation instantanés.

### ⚠️ EPIC 2 : Surveillance & Alertes Péremption
- **US 2.1** : Affichage des lots selon leur criticité :
  - 🟢 **Vert** : plus de 6 mois avant expiration.
  - 🟠 **Orange** : moins de 90 jours.
  - 🔴 **Rouge** : moins de 30 jours.
- Filtrage dynamique des alertes (Tout, Critique, Warning, Sain) sans rechargement.
- **US 2.2** : Compteur dynamique des produits périment le mois prochain.

### 💊 EPIC 3 : Sorties de Stock Intelligentes (FEFO)
- **US 3.1** : Sélection automatique du lot FEFO (date de péremption la plus proche).
- Dispensation en un clic (1 unité) avec décrémentation automatique.
- Mise à jour instantanée de la quantité sans rechargement.
- Suppression/grise des lots à quantité 0.

### 🗑️ EPIC 4 : Gestion des Pertes et Retours
- **US 4.1** : Déclaration des lots périmés avec confirmation.
- Retrait automatique du stock disponible.
- Notification de destruction.
- **US 4.2** : Rapport financier des pertes (Admin uniquement).
- Gestion des retours fournisseurs (Pharmacien+).

---

## 👥 Rôles Utilisateurs

### 🟢 Préparateur / Gestionnaire de Stock
- Réception des commandes (US 1.1).
- Enregistrement des lots avec lot et péremption.
- Dispensation FEFO (US 3.1).
- Consultation du dashboard.

### 🟠 Pharmacien Titulaire
- Consultation des alertes de péremption (US 2.1, 2.2).
- Validation des inventaires.
- Gestion des retours fournisseurs.
- Déclaration des lots périmés (US 4.1).

### 🔴 Administrateur
- Gestion des utilisateurs et rôles.
- Génération des rapports financiers (US 4.2).
- Gestion de la base des médicaments.
- Accès à toutes les fonctionnalités.

---

## 🏗️ Architecture du Projet

Le projet suit une architecture **MVC (Model View Controller)** avec une séparation Web/API.
## 🏗️ Architecture du projet

Le projet suit une architecture MVC (Model View Controller).

pharmafefo/
├── config/
│ ├── autoloader.php
│ ├── Database.php
│ └── Environment.php
├── public/
│ ├── css/
│ ├── js/
│ │ ├── dashboard.js
│ │ ├── receive.js
│ │ └── reports.js
│ └── index.php
├── src/
│ ├── Controller/
│ │ ├── Web/ # Contrôleurs HTML
│ │ │ ├── AuthController.php
│ │ │ ├── DashboardController.php
│ │ │ ├── StockController.php
│ │ │ └── ReportController.php
│ │ └── Api/ # Contrôleurs JSON (API)
│ │ ├── ApiStockController.php
│ │ └── ApiDashboardController.php
│ ├── Entity/
│ │ ├── Product.php
│ │ ├── StockBatch.php
│ │ └── User.php
│ ├── Enum/
│ │ └── BatchStatus.php
│ ├── Middleware/
│ │ ├── AuthMiddleware.php
│ │ └── RoleMiddleware.php
│ ├── Repository/
│ │ ├── ProductRepository.php
│ │ ├── StockBatchRepository.php
│ │ └── UserRepository.php
│ └── Service/
│ └── StockBatchService.php
├── templates/
│ ├── layout/
│ │ └── sidebar.php
│ ├── auth/
│ │ └── login.php
│ ├── dashboard/
│ │ └── dashboard.php
│ ├── stock/
│ │ ├── receive.php
│ │ ├── dispatch.php
│ │ └── alerts.php
│ └── reports/
│ └── financial.php
├── sql/
│ ├── schema.sql
│ └── test_data.sql
├── docs/
│ ├── pharmafefo-use-case-diagram.png
│ ├── pharmafefo-class-diagram.png
│ └── pharmafefo-erd-diagram.png
├── .env
├── .gitignore
└── README.md

---

## 🔗 Architecture API

Les données sont chargées dynamiquement via des endpoints API RESTful :

| Endpoint | Méthode | Description | EPIC |
|----------|---------|-------------|------|
| `/api?action=receive` | POST | Réception asynchrone de stock | 1 |
| `/api?action=products` | GET | Récupération des produits | 1 |
| `/api?action=dispense` | POST | Dispensation FEFO | 3 |
| `/api?action=expired` | POST | Marquage périmé | 4 |
| `/api?action=return` | POST | Retour fournisseur | 4 |
| `/api?action=batches` | GET | Lots filtrés | 2 |
| `/api?action=stats` | GET | Statistiques dashboard | 2 |
| `/api?action=loss-report` | GET | Rapport financier | 4 |

---
---

## 🛠️ Technologies Utilisées

| Catégorie | Technologies |
|-----------|--------------|
| **Backend** | PHP 8.2+, MySQL, PDO |
| **Frontend** | HTML5, CSS3 (Tailwind CSS), JavaScript ES6 |
| **Architecture** | MVC, Repository Pattern, Service Layer |
| **API** | RESTful, JSON |
| **Sécurité** | Sessions, BCrypt, Middleware (Auth & Roles) |
| **Outils** | Git & GitHub, Jira, UML |
| **Normes** | PSR-4, PHP 8+ Typage Strict, SOLID |

---
## 📊 Diagrammes UML

# diagramme de cas d'utilisation
![pharmafefo-use-case-diagram.png](docs/pharmafefo-use-case-diagram.png)
# diagramme de classe
![pharmafefo-class-diagram.png](docs/pharmafefo-class-diagram.png)
# diagramme ERD
![pharmafefo-erd-diagram.png](docs/pharmafefo-erd-diagram.png)
## 👨‍💻 Auteur
Projet réalisé dans le cadre de la formation Développeur Web et Web Mobile (DWWM).

## 🚀 Installation

```bash
# 1. Cloner le projet
git clone https://github.com/yourusername/PharmaFEFO.git

# 2. Configurer la base de données
mysql -u root -p < sql/schema.sql
mysql -u root -p < sql/test_data.sql

# 3. Configurer l'environnement
cp .env.example .env
# Modifier les informations de connexion dans .env

# 4. Lancer l'application
http://localhost/PharmaFEFO/public/index.php

Nom : badr belabrik

Année : 2026