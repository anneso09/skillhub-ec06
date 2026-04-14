package com.example.authclient;

import javafx.fxml.FXML;
import javafx.scene.control.*;

/**
 * Controller JavaFX pour l'écran d'authentification.
 * Gère les actions des boutons Inscription et Connexion.
 * Fait le lien entre l'interface graphique (FXML) et le service HTTP.
 */
public class AuthController {

    // ===================================
    // CHAMPS LIÉS AU FXML
    // @FXML = lié à un élément dans auth-view.fxml via fx:id
    // ===================================

    // Champs de l'onglet Inscription
    @FXML private TextField registerEmail;
    @FXML private PasswordField registerPassword;
    @FXML private Label registerMessage;

    // Champs de l'onglet Connexion
    @FXML private TextField loginEmail;
    @FXML private PasswordField loginPassword;
    @FXML private Label loginMessage;

    // Le service qui fait les appels HTTP
    private final ApiService apiService = new ApiService();

    // ===================================
    // INSCRIPTION
    // ===================================

    /**
     * Appelé quand l'utilisateur clique sur "S'inscrire".
     * Récupère email + password depuis les champs,
     * envoie la requête au serveur et affiche le résultat.
     */
    @FXML
    private void handleRegister() {
        String email = registerEmail.getText().trim();
        String password = registerPassword.getText();

        // Validation basique côté client
        if (email.isEmpty() || password.isEmpty()) {
            showError(registerMessage, "Email et mot de passe obligatoires");
            return;
        }

        if (password.length() < 12) {
            showError(registerMessage, "Mot de passe trop court (min. 12 caractères)");
            return;
        }

        try {
            // Appel HTTP au serveur
            var response = apiService.register(email, password);

            if (response.statusCode() == 201) {
                // 201 Created = inscription réussie
                showSuccess(registerMessage, "Inscription réussie ! Vous pouvez vous connecter.");
                registerEmail.clear();
                registerPassword.clear();
            } else if (response.statusCode() == 409) {
                // 409 Conflict = email déjà utilisé
                showError(registerMessage, "Cet email est déjà utilisé.");
            } else {
                // Autre erreur
                showError(registerMessage, "Erreur : " + response.statusCode());
            }

        } catch (Exception e) {
            // Erreur de connexion — le serveur est peut-être arrêté
            showError(registerMessage, "Impossible de contacter le serveur. WAMP est lancé ?");
        }
    }

    // ===================================
    // LOGIN
    // ===================================

    /**
     * Appelé quand l'utilisateur clique sur "Se connecter".
     * Calcule le HMAC localement et envoie la preuve au serveur.
     * Le mot de passe NE CIRCULE JAMAIS sur le réseau.
     */
    @FXML
    private void handleLogin() {
        String email = loginEmail.getText().trim();
        String password = loginPassword.getText();

        if (email.isEmpty() || password.isEmpty()) {
            showError(loginMessage, "Email et mot de passe obligatoires");
            return;
        }

        try {
            // ApiService calcule le HMAC et envoie la requête
            var response = apiService.login(email, password);

            if (response.statusCode() == 200) {
                // Login réussi — on extrait le token de la réponse JSON
                String responseBody = response.body();
                String token = extractToken(responseBody);

                // On passe à l'écran d'accueil avec le token
                HomeController.setToken(token);
                HomeController.setEmail(email);
                MainApp.switchScene("home-view.fxml");

            } else {
                // 401 = authentification échouée
                showError(loginMessage, "Email ou mot de passe incorrect.");
            }

        } catch (Exception e) {
            showError(loginMessage, "Impossible de contacter le serveur. WAMP est lancé ?");
        }
    }

    // ===================================
    // MÉTHODES UTILITAIRES
    // ===================================

    /**
     * Extrait le token de la réponse JSON du serveur.
     * Exemple de réponse : {"accessToken":"uuid-123","expiresAt":1234}
     * On extrait juste la valeur de "accessToken".
     */
    private String extractToken(String json) {
        // Recherche "accessToken":"valeur"
        int start = json.indexOf("\"accessToken\":\"") + 15;
        int end = json.indexOf("\"", start);
        return json.substring(start, end);
    }

    /** Affiche un message d'erreur en rouge */
    private void showError(Label label, String message) {
        label.setText(message);
        label.setStyle("-fx-text-fill: #e74c3c; -fx-font-size: 12px;");
    }

    /** Affiche un message de succès en vert */
    private void showSuccess(Label label, String message) {
        label.setText(message);
        label.setStyle("-fx-text-fill: #27ae60; -fx-font-size: 12px;");
    }
}