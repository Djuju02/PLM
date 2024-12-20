# PLM Web Application

Ce projet est une application web de type PLM (Product Lifecycle Management) permettant de gérer des parfums, leurs ingrédients, leurs coûts, leurs rôles utilisateurs, ainsi que l’historique des modifications et des commentaires.

## Fonctionnalités

- **Gestion des Parfums** : Affichage de la liste des parfums, détails complets (ingrédients, coût, cycle de vie, etc.).
- **Gestion des Ingrédients** : Modification du prix, des quantités, TVA, suivi des changements (historique).
- **Gestion des Utilisateurs** : Liste des utilisateurs, rôles multiples, possibilité pour un manager ou un admin de modifier et supprimer des utilisateurs.
- **Rôles et Équipes** : Administration des rôles, association des parfums et utilisateurs à des équipes, filtrage des données par équipe.
- **Cycle de Vie du Produit** : Statut du parfum (Développement, Production, Obsolète), versions du produit.
- **Commentaires** : Chaque utilisateur connecté peut laisser des commentaires sur un parfum pour un suivi collaboratif.
- **Logs et Historique** : Historique des changements d’ingrédients (quantité, prix, etc.) et commentaires horodatés.

## Technologies

- **Front-end** : HTML, CSS, JavaScript.
- **Back-end** : PHP, MySQL.
- **Environnement** : Docker, Docker Compose.

## Pré-requis

- [Docker](https://www.docker.com/) et [Docker Compose](https://docs.docker.com/compose/) installés sur votre machine.

## Installation

1. **Cloner le dépôt** :  
   ```bash
   git clone https://github.com/votre-nom-utilisateur/mon-projet-plm.git
   cd mon-projet-plm
   ```

2. **Arborescence** :  
   Assurez-vous de disposer des fichiers suivants :  
   - `docker-compose.yml`
   - `Dockerfile`
   - Le dossier `src/` contenant les fichiers `.php`, `styles.css`, `script.js`, etc.
   - Le dossier `initdb/` contenant `init.sql` si utilisé (pour initialiser la base de données).

3. **Initialisation de la Base de Données** :  
   Le fichier `init.sql` dans `initdb/` sera exécuté automatiquement au premier lancement du conteneur MySQL afin de créer les tables et insérer les données de base (utilisateurs, rôles, parfums, ingrédients, etc.).

## Lancement

1. **Démarrer les conteneurs** :  
   Dans le répertoire du projet :
   ```bash
   docker compose up -d --build
   ```
   
   Cette commande :
   - Lance le serveur PHP/Apache sur [http://localhost:8081](http://localhost:8081).
   - Lance la base de données MySQL sur le port 3306.
   
   *Note* : Si le port 8081 est déjà utilisé, modifiez-le dans `docker-compose.yml`.

2. **Accéder à l’application** :  
   Ouvrez votre navigateur à l’adresse [http://localhost:8081](http://localhost:8081).

   Vous verrez la page de connexion.  
   Utilisateurs par défaut (définis dans `init.sql`) :  
   - **admin** / **admin**  
   - **User1** / **mdp** (Equipe1)  
   - **User2** / **mdp** (Equipe2)

3. **Arrêter l’environnement** :  
   ```bash
   docker compose down
   ```

## Utilisation

- Connectez-vous avec un utilisateur (par exemple `admin/admin`).
- Accédez à la liste des parfums, utilisateurs, etc.
- Si vous êtes admin, vous pouvez gérer les ressources (ajouter, modifier, supprimer parfums, ingrédients, utilisateurs, rôles).
- Si vous êtes manager d’une équipe, vous pouvez modifier les membres et parfums de votre équipe.
- Tout utilisateur connecté peut ajouter des commentaires sur les parfums.

## Personnalisation

- Pour modifier les données initiales, éditez `init.sql` et relancez l’environnement sans conserver les volumes pour réinitialiser la base de données :
  ```bash
  docker compose down -v
  docker compose up -d --build
  ```

- Pour changer le port, éditez `docker-compose.yml` (par exemple `8081` vers un autre port libre).

## Troubleshooting

- **Problèmes de connexion à la base** : Vérifiez `docker compose logs db`.
- **Absence de commentaires après ajout** : Assurez-vous que `$_SESSION['user_id']` est défini lors de la connexion. Vérifiez le code dans `login.php`.
- **Problèmes de droits (manager, admin)** : Vérifiez les rôles dans la BD (table `users` et `roles`).

## Contributions

Les contributions sont les bienvenues. Ouvrez une issue ou soumettez une pull request.

## Licence

Ce projet est distribué sous licence MIT