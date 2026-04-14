package com.example.authclient;

import javafx.fxml.FXML;
import javafx.scene.control.*;

/**
 * Controller JavaFX pour l'écran de changement de mot de passe.
 * Gère la validation côté client et l'appel à l'API.
 */
public class ChangePasswordController {

    // Champs liés au FXML
    @FXML private TextField emailField;
    @FXML private PasswordField oldPasswordField;
    @FXML private PasswordField newPasswordField;
    @FXML private PasswordField confirmPasswordField;
    @FXML private Label messageLabel;

    private final ApiService apiService = new ApiService();

    // ===================================
    // CHANGEMENT MOT DE PASSE
    // ===================================

    /**
     * Appelé quand l'utilisateur clique sur "Changer le mot de passe".
     * Valide les champs puis envoie la requête au serveur.
     */
    @FXML
    private void handleChangePassword() {
        String email = emailField.getText().trim();
        String oldPassword = oldPasswordField.getText();
        String newPassword = newPasswordField.getText();
        String confirmPassword = confirmPasswordField.getText();

        // Validation côté client
        if (email.isEmpty() || oldPassword.isEmpty()
                || newPassword.isEmpty() || confirmPassword.isEmpty()) {
            showError("Tous les champs sont obligatoires");
            return;
        }

        if (!newPassword.equals(confirmPassword)) {
            showError("Les nouveaux mots de passe ne correspondent pas");
            return;
        }

        if (newPassword.length() < 12) {
            showError("Le nouveau mot de passe doit faire au moins 12 caractères");
            return;
        }

        try {
            var response = apiService.changePassword(
                    email, oldPassword, newPassword, confirmPassword);

            if (response.statusCode() == 200) {
                showSuccess("Mot de passe changé avec succès !");
                // On vide les champs après succès
                oldPasswordField.clear();
                newPasswordField.clear();
                confirmPasswordField.clear();
            } else if (response.statusCode() == 401) {
                showError("Ancien mot de passe incorrect.");
            } else {
                showError("Erreur : " + response.statusCode());
            }

        } catch (Exception e) {
            showError("Impossible de contacter le serveur.");
        }
    }

    // ===================================
    // RETOUR À L'ACCUEIL
    // ===================================

    /**
     * Retourne à l'écran d'accueil.
     */
    @FXML
    private void handleBack() throws Exception {
        MainApp.switchScene("home-view.fxml");
    }

    // ===================================
    // MÉTHODES UTILITAIRES
    // ===================================

    private void showError(String message) {
        messageLabel.setText(message);
        messageLabel.setStyle("-fx-text-fill: #e74c3c; -fx-font-size: 12px;");
    }

    private void showSuccess(String message) {
        messageLabel.setText(message);
        messageLabel.setStyle("-fx-text-fill: #27ae60; -fx-font-size: 12px;");
    }
}