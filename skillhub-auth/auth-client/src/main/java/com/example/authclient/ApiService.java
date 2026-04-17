package com.example.authclient;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;

/**
 * Service qui gère tous les appels HTTP vers le serveur Spring Boot.
 * Utilise le HttpClient natif de Java 17 — pas besoin de librairie externe.
 *
 * C'est ici que le client communique avec l'API REST :
 * - Inscription : POST /api/auth/register
 * - Login HMAC  : POST /api/auth/login
 * - Profil      : GET  /api/me
 */
public class ApiService {

    // L'URL de base du serveur — doit tourner sur WAMP + Spring Boot
    private static final String BASE_URL = "http://localhost:8080";

    // HttpClient = l'outil Java pour faire des requêtes HTTP
    // C'est comme un navigateur web mais pour du code
    private final HttpClient httpClient = HttpClient.newHttpClient();

    // ===================================
    // INSCRIPTION
    // ===================================

    /**
     * Envoie une requête d'inscription au serveur.
     * POST /api/auth/register avec email + password en JSON.
     *
     * @param email    L'email saisi par l'utilisateur
     * @param password Le mot de passe saisi
     * @return La réponse du serveur (201 si succès, 409 si email existe déjà)
     */
    public HttpResponse<String> register(String email, String password) throws Exception {

        // On construit le JSON manuellement — simple et lisible
        String json = String.format(
                "{\"email\":\"%s\",\"password\":\"%s\"}",
                email, password
        );

        // On construit la requête HTTP
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(BASE_URL + "/api/auth/register"))
                .header("Content-Type", "application/json")  // On envoie du JSON
                .POST(HttpRequest.BodyPublishers.ofString(json, StandardCharsets.UTF_8))
                .build();

        // On envoie la requête et on récupère la réponse
        return httpClient.send(request, HttpResponse.BodyHandlers.ofString());
    }

    // ===================================
    // LOGIN HMAC
    // ===================================

    /**
     * Envoie une requête de login avec le protocole HMAC.
     * POST /api/auth/login avec email + nonce + timestamp + hmac.
     * Le mot de passe ne circule JAMAIS dans cette requête.
     *
     * @param email     L'email de l'utilisateur
     * @param password  Le mot de passe — utilisé pour calculer le HMAC, jamais envoyé
     * @return La réponse du serveur avec le token si succès
     */
    public HttpResponse<String> login(String email, String password) throws Exception {

        // Étape 1 : générer les éléments du protocole HMAC
        String nonce = HmacUtil.generateNonce();
        long timestamp = HmacUtil.getCurrentTimestamp();

        // Étape 2 : construire le message à signer
        // message = email + ":" + nonce + ":" + timestamp
        String message = email + ":" + nonce + ":" + timestamp;

        // Étape 3 : calculer la signature HMAC
        // Le password sert de CLÉ — il reste ici, jamais envoyé
        String hmac = HmacUtil.calculate(password, message);

        // Étape 4 : construire le JSON avec TOUT SAUF le password
        String json = String.format(
                "{\"email\":\"%s\",\"nonce\":\"%s\",\"timestamp\":%d,\"hmac\":\"%s\"}",
                email, nonce, timestamp, hmac
        );

        // Étape 5 : envoyer la requête
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(BASE_URL + "/api/auth/login"))
                .header("Content-Type", "application/json")
                .POST(HttpRequest.BodyPublishers.ofString(json, StandardCharsets.UTF_8))
                .build();

        return httpClient.send(request, HttpResponse.BodyHandlers.ofString());
    }

    // ===================================
    // ROUTE PROTÉGÉE /api/me
    // ===================================

    /**
     * Accède à la route protégée /api/me.
     * Envoie le token dans le header Authorization.
     * Si le token est absent ou invalide → 401.
     *
     * @param token Le token reçu après un login réussi
     * @return La réponse du serveur
     */
    public HttpResponse<String> getMe(String token) throws Exception {

        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(BASE_URL + "/api/me"))
                // Le token est envoyé dans le header Authorization
                // Format standard : "Bearer <token>"
                .header("Authorization", "Bearer " + token)
                .GET()
                .build();

        return httpClient.send(request, HttpResponse.BodyHandlers.ofString());
    }

    // ===================================
// CHANGEMENT DE MOT DE PASSE
// ===================================

    /**
     * Envoie une requête de changement de mot de passe.
     * PUT /api/auth/change-password
     *
     * @param email           Email de l'utilisateur
     * @param oldPassword     Ancien mot de passe en clair
     * @param newPassword     Nouveau mot de passe en clair
     * @param confirmPassword Confirmation du nouveau mot de passe
     * @return La réponse du serveur
     */
    public HttpResponse<String> changePassword(String email, String oldPassword,
                                               String newPassword, String confirmPassword) throws Exception {
        String json = String.format(
                "{\"email\":\"%s\",\"oldPassword\":\"%s\",\"newPassword\":\"%s\",\"confirmPassword\":\"%s\"}",
                email, oldPassword, newPassword, confirmPassword
        );

        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(BASE_URL + "/api/auth/change-password"))
                .header("Content-Type", "application/json")
                .PUT(HttpRequest.BodyPublishers.ofString(json, StandardCharsets.UTF_8))
                .build();

        return httpClient.send(request, HttpResponse.BodyHandlers.ofString());
    }
}