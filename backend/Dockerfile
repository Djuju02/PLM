FROM node:18

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers package.json et package-lock.json
COPY package*.json ./

# Installer les dépendances
RUN npm install

# Copier le reste des fichiers du backend
COPY . .

# Copier le fichier wait-for-it.sh et rendre le fichier exécutable
COPY wait-for-it.sh /wait-for-it.sh
RUN chmod +x /wait-for-it.sh

# Exposer le port pour l'application
EXPOSE 5000

# Démarrer le serveur après vérification de la disponibilité de la base de données
CMD ["/wait-for-it.sh", "db:5432", "--", "node", "app.js"]