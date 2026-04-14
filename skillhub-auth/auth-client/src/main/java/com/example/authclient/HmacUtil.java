package com.example.authclient;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.nio.charset.StandardCharsets;
import java.util.HexFormat;
import java.util.UUID;

/**
 * Utilitaire pour le calcul HMAC côté client.
 * C'est ici que le client prépare sa preuve d'identité
 * sans jamais envoyer le mot de passe sur le réseau.
 */
public class HmacUtil {

    /**
     * Calcule une signature HMAC-SHA256.
     * Mélange le mot de passe (clé) avec le message
     * pour produire une signature unique et non réversible.
     *
     * @param key     Le mot de passe de l'utilisateur — reste sur le client
     * @param message Le message à signer = email:nonce:timestamp
     * @return La signature en hexadécimal
     */
    public static String calculate(String key, String message) throws Exception {
        Mac mac = Mac.getInstance("HmacSHA256");
        SecretKeySpec secretKey = new SecretKeySpec(
                key.getBytes(StandardCharsets.UTF_8),
                "HmacSHA256"
        );
        mac.init(secretKey);
        byte[] hmacBytes = mac.doFinal(message.getBytes(StandardCharsets.UTF_8));
        return HexFormat.of().formatHex(hmacBytes);
    }

    /**
     * Génère un nonce unique (UUID aléatoire).
     * Ce nonce ne peut être utilisé qu'une seule fois.
     * Empêche les attaques par rejeu.
     *
     * @return Un UUID aléatoire sous forme de String
     */
    public static String generateNonce() {
        return UUID.randomUUID().toString();
    }

    /**
     * Retourne le timestamp actuel en secondes (epoch).
     * Le serveur vérifie que ce timestamp est dans une
     * fenêtre de ±60 secondes pour éviter le rejeu.
     *
     * @return Le timestamp actuel en secondes
     */
    public static long getCurrentTimestamp() {
        return System.currentTimeMillis() / 1000;
    }
}