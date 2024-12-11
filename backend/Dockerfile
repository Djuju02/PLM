FROM node:18

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers package.json et package-lock.json
COPY package*.json ./

# Installer les dépendances
RUN npm install

# Copier le reste des fichiers du backend
COPY . .

# Exposer le port pour l'application
EXPOSE 5000

# Démarrer le serveur
CMD ["node", "app.js"]
