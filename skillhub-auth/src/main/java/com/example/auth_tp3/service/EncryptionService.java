package com.example.auth_tp3.service;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;
import javax.crypto.Cipher;
import javax.crypto.SecretKey;
import javax.crypto.spec.GCMParameterSpec;
import javax.crypto.spec.SecretKeySpec;
import java.security.SecureRandom;
import java.util.Base64;

/**
 * Service de chiffrement AES-GCM.
 * Chiffre et déchiffre les mots de passe avec la Master Key.
 *
 * AES = algorithme de chiffrement standard très solide.
 * GCM = mode qui garantit à la fois le chiffrement ET l'intégrité.
 *       Si quelqu'un modifie le texte chiffré, le déchiffrement échoue.
 *
 * Format de stockage : v1:Base64(iv):Base64(ciphertext)
 * - v1 = version du format (pour pouvoir évoluer plus tard)
 * - iv = vecteur d'initialisation aléatoire (différent à chaque chiffrement)
 * - ciphertext = le mot de passe chiffré
 *
 * IMPORTANT : La Master Key ne doit JAMAIS être dans le code.
 * Elle est injectée via la variable d'environnement APP_MASTER_KEY.
 * Si elle est absente, l'application refuse de démarrer.
 */
@Service
public class EncryptionService {

    // Taille du vecteur d'initialisation en bytes
    private static final int GCM_IV_LENGTH = 12;

    // Taille du tag d'authentification GCM en bits
    private static final int GCM_TAG_LENGTH = 128;

    // La Master Key injectée depuis la variable d'environnement
    // ${APP_MASTER_KEY} = Spring lit la variable d'environnement APP_MASTER_KEY
    private final SecretKey masterKey;

    // SecureRandom est thread-safe et coûteux à créer
    // On le crée une seule fois et on le réutilise
    private final SecureRandom secureRandom = new SecureRandom();

    /**
     * Constructeur — vérifie que la Master Key est présente au démarrage.
     * Si APP_MASTER_KEY est absente → l'application refuse de démarrer.
     *
     * @param masterKeyValue La valeur de APP_MASTER_KEY
     */
    public EncryptionService(@Value("${app.master.key}") String masterKeyValue) {
        if (masterKeyValue == null || masterKeyValue.isBlank()) {
            throw new IllegalStateException(
                    "APP_MASTER_KEY est absente ! L'application ne peut pas démarrer sans la Master Key."
            );
        }

        // On dérive une clé AES de 256 bits depuis la Master Key
        // On padde ou tronque à exactement 32 bytes pour AES-256
        byte[] keyBytes = masterKeyValue.getBytes();
        byte[] aesKey = new byte[32];  // 32 bytes = 256 bits
        System.arraycopy(keyBytes, 0, aesKey, 0, Math.min(keyBytes.length, 32));
        this.masterKey = new SecretKeySpec(aesKey, "AES");
    }

    /**
     * Chiffre un mot de passe avec AES-GCM.
     * Génère un IV aléatoire à chaque appel — deux chiffrements du même
     * mot de passe donnent des résultats différents (sécurité renforcée).
     *
     * @param plaintext Le mot de passe en clair à chiffrer
     * @return Le mot de passe chiffré au format "v1:Base64(iv):Base64(cipher)"
     */
    public String encrypt(String plaintext) throws Exception {
        // Génère un IV aléatoire — différent à chaque chiffrement
        // Sans IV aléatoire, deux mots de passe identiques donneraient
        // le même résultat chiffré — ce serait une faille de sécurité
        byte[] iv = new byte[GCM_IV_LENGTH];
        secureRandom.nextBytes(iv);

        // Configure le chiffrement AES-GCM
        Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
        GCMParameterSpec parameterSpec = new GCMParameterSpec(GCM_TAG_LENGTH, iv);
        cipher.init(Cipher.ENCRYPT_MODE, masterKey, parameterSpec);

        // Chiffre le mot de passe
        byte[] ciphertext = cipher.doFinal(plaintext.getBytes());

        // Encode en Base64 pour stocker en base de données (texte lisible)
        String ivBase64 = Base64.getEncoder().encodeToString(iv);
        String ciphertextBase64 = Base64.getEncoder().encodeToString(ciphertext);

        // Format final : v1:iv:ciphertext
        return "v1:" + ivBase64 + ":" + ciphertextBase64;
    }

    /**
     * Déchiffre un mot de passe chiffré avec AES-GCM.
     * Utilise la même Master Key pour retrouver le mot de passe original.
     *
     * @param encryptedData Le mot de passe chiffré au format "v1:iv:cipher"
     * @return Le mot de passe en clair
     */
    public String decrypt(String encryptedData) throws Exception {
        // Découpe le format "v1:iv:ciphertext"
        String[] parts = encryptedData.split(":");
        if (parts.length != 3 || !parts[0].equals("v1")) {
            throw new IllegalArgumentException("Format de données chiffrées invalide");
        }

        // Décode le Base64
        byte[] iv = Base64.getDecoder().decode(parts[1]);
        byte[] ciphertext = Base64.getDecoder().decode(parts[2]);

        // Configure le déchiffrement AES-GCM
        Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
        GCMParameterSpec parameterSpec = new GCMParameterSpec(GCM_TAG_LENGTH, iv);
        cipher.init(Cipher.DECRYPT_MODE, masterKey, parameterSpec);

        // Déchiffre et retourne le mot de passe en clair
        byte[] plaintext = cipher.doFinal(ciphertext);
        return new String(plaintext);
    }
}