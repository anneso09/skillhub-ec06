package com.example.authclient;

import javafx.application.Application;
import javafx.fxml.FXMLLoader;
import javafx.scene.Scene;
import javafx.stage.Stage;
import java.io.IOException;

/**
 * Point d'entrée de l'application client JavaFX.
 * Lance la fenêtre principale avec l'écran de connexion.
 */
public class MainApp extends Application {

    // Stage = la fenêtre principale de l'application
    public static Stage primaryStage;

    @Override
    public void start(Stage stage) throws IOException {
        primaryStage = stage;

        // Charge le fichier FXML qui décrit l'interface
        FXMLLoader loader = new FXMLLoader(
                MainApp.class.getResource("auth-view.fxml")
        );

        // Crée la scène avec une taille fixe
        Scene scene = new Scene(loader.load(), 420, 500);

        stage.setTitle("TP3 - Authentification HMAC");
        stage.setScene(scene);
        stage.setResizable(false);  // Fenêtre non redimensionnable
        stage.show();
    }

    /**
     * Change l'écran affiché dans la fenêtre principale.
     * Appelé après un login réussi pour afficher l'écran d'accueil.
     *
     * @param fxmlFile Le nom du fichier FXML à charger
     */
    public static void switchScene(String fxmlFile) throws IOException {
        FXMLLoader loader = new FXMLLoader(
                MainApp.class.getResource(fxmlFile)
        );
        Scene scene = new Scene(loader.load(), 420, 500);
        primaryStage.setScene(scene);
    }

    public static void main(String[] args) {
        launch();
    }
}