# 🛠️ Installation et exécution du projet GestPro

## Prérequis

- Node.js v18+
- PHP 8.1+
- Composer
- PostgreSQL
- Angular CLI
- Laravel CLI
- Ollama installé localement

---

## 📦 Backend (Laravel)

Après avoir récupéré le projet Laravel aej-gestpro-backend rendez vous à la racine du projet et installez les dépendances
```bash
cd backend
composer install
```

Exécutez également cette commande pour créer le fichier d'environnement .env
```bash
cp .env.example .env
```
Configurez le fichier d'environnement comme suit :
### 📄 Configuration `.env` (extrait)
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gestpro
DB_USERNAME=postgres
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="info@gestpro.ci"
MAIL_FROM_NAME="GestPro"

OLLAMA_API_URL=http://localhost:11434/api/generate
OLLAMA_MODEL=llama3.2:latest
```

Exécutez les commandes ci-dessous pour permettre la création des tables dans la base de données PostgreSQL 
et pour démarrer le serveur local. Assurez vous d'avoir installé PostgreSQL 😂
```bash
php artisan migrate
php artisan serve
```

### ✉️ Démarrez le simulateur d'envoi et de réception de mail `MailHog`
Pour utiliser le simulateur d'email suivez ces instructions : 
- Lancez le fichier exécutable MailHog_windows_386.exe (se trouvant dans la racine du projet Laravel)
- Reduisez le terminal ouvert puis ouvrez votre navigateur et accédez à cette adresse http://localhost:8025
- Vous recevrez dans le menu Inbox les différents mails de soumission de projets des promoteur
---


## 🌐 Frontend (Angular)

Après avoir récupéré le projet Angular aej-gestpro-frontend rendez vous à la racine du projet, 
installez les dépendances et démarrez le serveur qui s'exécutera sur cette adresse http://localhost:4200
```bash
cd frontend
npm install
npm run start
```

---

## 🧠 Lancer le moteur IA (Ollama)

```bash
ollama run llama3.2:latest
```

Assurez-vous avant tout que vous avez installé ollama sur votre machine. Si ce n'est pas encore le cas suivez ces instructions : 
- Rendez vous sur le site https://ollama.com
- Cliquez sur le menu Models en haut à gauche
- Dans la barre de recherche tapez llama3.2 puis cliquez sur le résultat de recherche llama3.2
- Sélectionnez le nombre de paramètres en fonction des performances de votre machine puis copiez la commande correspondante
- Ouvrez la commande cmd et copier coller puis lancer la commande. (Ex: ollama run llama3.2) dans mon cas.
- Attendez la fin de l'installation et profitez de l'IA en locale.
