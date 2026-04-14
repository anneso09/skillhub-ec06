package com.example.authclient;

import javafx.fxml.FXML;
import javafx.scene.control.Label;

/**
 * Controller JavaFX pour l'écran d'accueil.
 * Affiché après un login réussi.
 * Montre le token reçu et permet d'accéder à /api/me.
 */
public class HomeController {

    // Token et email stockés statiquement
    // pour être passés depuis AuthController
    private static String token;
    private static String email;

    // Champs liés au FXML
    @FXML private Label welcomeLabel;
    @FXML private Label tokenLabel;
    @FXML private Label meMessage;

    private final ApiService apiService = new ApiService();

    /**
     * Appelé automatiquement par JavaFX quand l'écran est chargé.
     * Affiche le message de bienvenue et le token.
     */
    @FXML
    public void initialize() {
        welcomeLabel.setText("Bienvenue " + email + " !");
        tokenLabel.setText("Token : " + token);
    }

    /**
     * Appelé quand l'utilisateur clique sur "Accéder à /api/me".
     * Envoie le token dans le header Authorization.
     */
    @FXML
    private void handleGetMe() {
        try {
            var response = apiService.getMe(token);

            if (response.statusCode() == 200) {
                meMessage.setText("Accès autorisé ! " + response.body());
                meMessage.setStyle("-fx-text-fill: #27ae60;");
            } else {
                meMessage.setText("Accès refusé — token invalide.");
                meMessage.setStyle("-fx-text-fill: #e74c3c;");
            }

        } catch (Exception e) {
            meMessage.setText("Impossible de contacter le serveur.");
            meMessage.setStyle("-fx-text-fill: #e74c3c;");
        }
    }
    /**
     * Navigue vers l'écran de changement de mot de passe.
     */
    @FXML
    private void handleChangePassword() throws Exception {
        MainApp.switchScene("change-password-view.fxml");
    }

    /**
     * Retourne à l'écran de connexion.
     */
    @FXML
    private void handleLogout() throws Exception {
        token = null;
        email = null;
        MainApp.switchScene("auth-view.fxml");
    }

    // Setters statiques appelés depuis AuthController
    public static void setToken(String t) { token = t; }
    public static void setEmail(String e) { email = e; }
}