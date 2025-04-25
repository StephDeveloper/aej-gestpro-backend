# 📊 GestPro

**GestPro** est une plateforme full stack permettant aux promoteurs de soumettre leur plan d'affaires via une landing page, 
qui sera ensuite analysé automatiquement par une IA (Ollama) côté back-office. Le système fournit des outils d'administration complets, 
un tableau de bord dynamique, ainsi que des fonctionnalités avancées d'exportation et de génération de documents.

---

## 🚀 Stack Technique

| Côté              | Technologie                                                    |
|-------------------|----------------------------------------------------------------|
| Frontend          | Angular 19                                                     |
| Backend           | Laravel 10                                                     |
| Base de données   | PostgreSQL                                                     |
| IA                | Ollama (analyse Plan affaire en PDF + scoring IA)              |
| Libs JS (Angular) | `jspdf`, `jspdf-autotable`, `xlsx`, `file-saver`, `apexcharts` |

---

## 🧠 Fonctionnalités principales

- Soumission de projets via une landing page
- Notification par e-mail de réception du projet
- Page d'inscription d'un administrateur (pour permettre les tests)
- Authentification JWT pour les administrateurs
- Tableau de bord dynamique (statistiques, graphiques)
- Analyse intelligente des plans d'affaires (IA via Ollama)
- Système de validation/rejet avec notification e-mail
- Génération de PDF récapitulatif
- Exportation de données au format Excel (XLSX)

---

## 📁 Architecture

```
gestpro/
├── frontend/ (Angular)
│   ├── environnement/
│   ├── guards/
│   ├── interceptors/
│   ├── components
│   └── services/
├── backend/ (Laravel)
│   ├── app/
│   ├── routes/
│   ├── controllers/
│   ├── Mail
│   └── config
└── database/ (PostgreSQL)
```

---

## ⚙️ Installation

Voir [INSTALLATION.md](./INSTALLATION.md)

## Fonctionnalités

Voir [FONCTIONNALITES](./FONCTIONNALITÉS.md)

## 📚 Documentation API

Voir [API.md](./aej-gestpro.postman_collection.json)
