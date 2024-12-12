# PLM (Product Lifecycle Management) Parfumerie

## 📘 **Description**
Ce projet est une application de gestion du cycle de vie des produits (PLM) pour l'industrie de la parfumerie. L'application est composée d'un **backend** en Node.js, d'un **frontend** en Angular et d'une **base de données** PostgreSQL, le tout orchestré avec Docker.

L'objectif est de faciliter la gestion des utilisateurs, des produits de parfumerie et de fournir une interface intuitive pour les utilisateurs.

---

## 📋 **Prérequis**
Avant de commencer, assurez-vous d'avoir les outils suivants installés sur votre machine :

- [Git](https://git-scm.com/)
- [Docker Desktop](https://www.docker.com/products/docker-desktop) (pour Windows, MacOS et Linux)
- [Node.js](https://nodejs.org/) (pour exécuter les commandes npm si besoin)

Vérifiez que les outils sont bien installés avec :
```bash
git --version
docker --version
node --version
```

---

## 🚀 **Installation**
Suivez ces instructions pour configurer et exécuter le projet sur votre machine, quel que soit le système d'exploitation (Windows, MacOS, Linux).

### 1️⃣ **Cloner le dépôt GitHub**
Exécutez la commande suivante pour cloner le dépôt :
```bash
git clone https://github.com/Djuju02/PLM.git
cd PLM
```

### 2️⃣ **Configurer les variables d'environnement**
Créez un fichier **`.env`** à la racine du projet avec les variables d'environnement suivantes :
```env
DB_USER=parfum_user
DB_PASSWORD=password
DB_NAME=parfum_db
DB_HOST=db
DB_PORT=5432
JWT_SECRET=votre_secret_pour_jwt
```
> **Note** : Vous pouvez personnaliser ces valeurs selon vos besoins.

### 3️⃣ **Lancer l'application**
Exécutez la commande suivante pour lancer le projet avec Docker :
```bash
docker-compose up --build
```
Cette commande va :
- **Construire les conteneurs Docker** (backend, frontend, base de données).
- **Lancer les conteneurs**.
- **Exposer les ports** suivants :
  - **Backend** : [http://localhost:5000](http://localhost:5000)
  - **Frontend** : [http://localhost:4200](http://localhost:4200)

> **Note** : La première fois, la construction du projet peut prendre quelques minutes.

### 4️⃣ **Accéder à l'application**
- Ouvrez votre navigateur sur [http://localhost:4200](http://localhost:4200) pour accéder à l'interface utilisateur.
- Vous pouvez également interagir avec l'API sur [http://localhost:5000/api](http://localhost:5000/api).

---

## 📚 **Structure du projet**
Voici l'organisation des principaux fichiers et répertoires :
```
PLM/
├── backend/           # Dossier du backend Node.js (API REST)
├── frontend/          # Dossier du frontend Angular
├── docker-compose.yml # Fichier de configuration Docker Compose
├── Dockerfile         # Dockerfile du backend
├── .env               # Variables d'environnement
└── README.md          # Ce fichier README
```

- **backend/**
  - **app.js** : Point d'entrée principal du serveur Node.js.
  - **controllers/** : Logique des routes (users, parfums).
  - **models/** : Modèles de la base de données.
  - **routes/** : Routes de l'API (ex : `/api/users`, `/api/parfums`).

- **frontend/**
  - **src/app/** : Code source de l'application Angular.
  - **environments/** : Fichiers de configuration des environnements (production, développement).

- **docker-compose.yml**
  - Configuration de tous les services nécessaires (base de données, backend, frontend).

---

## 📡 **API Endpoints**
Voici la liste des principales routes disponibles sur l'API.

### 🔐 **Utilisateurs**
| **Méthode** | **URL**              | **Description**                  |
|-------------|---------------------|-----------------------------------|
| POST        | /api/users/register  | Inscription d'un utilisateur     |
| POST        | /api/users/login     | Connexion d'un utilisateur       |

**Exemple** d'inscription via `curl` :
```bash
curl -X POST http://localhost:5000/api/users/register \
  -H "Content-Type: application/json" \
  -d '{"username": "testuser", "password": "password123"}'
```

### 📦 **Produits (Parfums)**
| **Méthode** | **URL**              | **Description**                  |
|-------------|---------------------|-----------------------------------|
| GET         | /api/parfums         | Liste des parfums                |
| GET         | /api/parfums/:id     | Détails d'un parfum (par id)     |

**Exemple** de récupération des parfums :
```bash
curl http://localhost:5000/api/parfums
```

---

## 🛠️ **Commandes utiles**
| **Commande**              | **Description**                   |
|--------------------------|-------------------------------------|
| `docker-compose up --build` | Construire et lancer les conteneurs  |
| `docker-compose down`       | Arrêter et supprimer les conteneurs  |
| `docker-compose logs`       | Voir les logs des conteneurs         |
| `docker exec -it plm-db-1 psql -U parfum_user -d parfum_db` | Accéder à la base PostgreSQL |

---

## 💡 **Dépannage**
### ⚠️ **Problèmes courants**
- **Problème de connexion à la base de données**
  - Vérifiez que la base de données est bien en cours d'exécution :
    ```bash
    docker ps
    ```
  - Assurez-vous que les fichiers de configuration `.env` sont correctement remplis.

- **Problème de ports**
  - Vérifiez si les ports **5432 (PostgreSQL)**, **5000 (backend)** et **4200 (frontend)** ne sont pas déjà utilisés par d'autres applications.
  - Vous pouvez les modifier dans le fichier `docker-compose.yml` si nécessaire.

- **Problèmes de cache**
  - Si vous rencontrez des problèmes lors de la construction de l'application, supprimez les caches :
    ```bash
    docker system prune -f
    docker-compose down -v
    ```
  - Rebuild du projet :
    ```bash
    docker-compose up --build
    ```

---

## 🤝 **Contribuer**
1. **Clonez le dépôt** :
```bash
git clone https://github.com/Djuju02/PLM.git
```
2. **Créez une branche** :
```bash
git checkout -b ma-nouvelle-fonctionnalite
```
3. **Faites vos modifications**.
4. **Testez votre code**.
5. **Faites un commit** :
```bash
git commit -m "Ajout d'une nouvelle fonctionnalité"
```
6. **Poussez votre branche** :
```bash
git push origin ma-nouvelle-fonctionnalite
```
7. **Créez une Pull Request (PR)**.

---

## 🧑‍💻 **Auteur**
- **Julien Saleh**
- **Projet GitHub** : [https://github.com/Djuju02/PLM](https://github.com/Djuju02/PLM)

---

Bon codage 🚀 !

