# Étape 1 : Build de l'application
# On utilise une image Maven avec Java 17 pour compiler
FROM eclipse-temurin:17-jdk-alpine AS build

# On définit le dossier de travail dans le conteneur
WORKDIR /app

# On copie d'abord le pom.xml pour profiter du cache Maven
# Si le pom.xml ne change pas, Maven ne retélécharge pas les dépendances
COPY pom.xml .
COPY .mvn .mvn
COPY mvnw .
RUN chmod +x mvnw

# On copie le code source
COPY src ./src

# On compile et package l'application
# -DskipTests car les tests tournent dans la CI, pas dans le build Docker
RUN ./mvnw package -DskipTests

# Étape 2 : Image finale légère
# On repart d'une image JRE (plus légère que JDK) pour l'exécution
FROM eclipse-temurin:17-jre-alpine

# Dossier de travail
WORKDIR /app

# On copie uniquement le jar depuis l'étape de build
# L'image finale ne contient pas Maven ni le code source — plus sécurisé !
COPY --from=build /app/target/*.jar app.jar

# On expose le port 8080
EXPOSE 8080

# La commande qui démarre l'application Spring Boot
# La Master Key est injectée via variable d'environnement au runtime
ENTRYPOINT ["java", "-jar", "app.jar"]