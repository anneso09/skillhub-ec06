package com.example.auth_tp3.service;

import com.example.auth_tp3.entity.AuthNonce;
import com.example.auth_tp3.entity.User;
import com.example.auth_tp3.exception.AuthenticationFailedException;
import com.example.auth_tp3.exception.InvalidInputException;
import com.example.auth_tp3.exception.ResourceConflictException;
import com.example.auth_tp3.repository.NonceRepository;
import com.example.auth_tp3.repository.UserRepository;
import org.springframework.stereotype.Service;
import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.time.LocalDateTime;
import java.util.HexFormat;
import java.util.Optional;
import java.util.UUID;

/**
 * Service principal d'authentification TP4.
 *
 * Protocole implementé : HMAC-SHA256 avec nonce et timestamp.
 * Le mot de passe ne circule jamais sur le réseau.
 *
 * AMÉLIORATION TP4 : Le mot de passe est maintenant chiffré
 * avec AES-GCM via la Master Key avant stockage en base.
 * Si la base est volée, les mots de passe sont illisibles
 * sans la Master Key.
 *
 * Processus inscription :
 * password_plain → AES-GCM(MasterKey) → password_encrypted → MySQL
 *
 * Processus login :
 * password_encrypted → déchiffrement(MasterKey) → password_plain → HMAC
 */
@Service
public class AuthService {

    private final UserRepository userRepository;
    private final NonceRepository nonceRepository;
    private final EncryptionService encryptionService;

    public AuthService(UserRepository userRepository,
                       NonceRepository nonceRepository,
                       EncryptionService encryptionService) {
        this.userRepository = userRepository;
        this.nonceRepository = nonceRepository;
        this.encryptionService = encryptionService;
    }

    // ===================================
    // INSCRIPTION
    // ===================================

    /**
     * Inscrit un nouvel utilisateur.
     * Le mot de passe est chiffré avec AES-GCM avant stockage.
     * Il ne sera JAMAIS stocké en clair — amélioration majeure vs TP3.
     *
     * @param email    L'email de l'utilisateur
     * @param password Le mot de passe en clair — chiffré avant stockage
     * @return L'utilisateur créé
     */
    public User register(String email, String password) throws Exception {

        // Validation email
        if (email == null || email.isBlank()) {
            throw new InvalidInputException("L'email ne peut pas être vide");
        }
        if (!email.contains("@")) {
            throw new InvalidInputException("Format d'email invalide");
        }

        // Validation mot de passe
        if (password == null || password.length() < 12) {
            throw new InvalidInputException("Le mot de passe doit faire au moins 12 caractères");
        }

        // Vérifie que l'email n'est pas déjà utilisé
        if (userRepository.existsByEmail(email)) {
            throw new ResourceConflictException("Cet email est déjà utilisé");
        }

        // Chiffre le mot de passe avec la Master Key avant stockage
        // C'est la différence principale avec TP3 !
        String encryptedPassword = encryptionService.encrypt(password);

        // Crée et sauvegarde l'utilisateur avec le mot de passe chiffré
        User user = new User();
        user.setEmail(email);
        user.setPasswordEncrypted(encryptedPassword);

        return userRepository.save(user);
    }

    // ===================================
    // LOGIN
    // ===================================

    /**
     * Authentifie un utilisateur avec le protocole HMAC.
     * Déchiffre le mot de passe stocké avant de recalculer le HMAC.
     *
     * @param email     Email de l'utilisateur
     * @param nonce     UUID unique généré par le client
     * @param timestamp Horodatage epoch en secondes
     * @param hmac      Signature HMAC calculée par le client
     * @return Un token d'accès si l'authentification réussit
     */
    public String login(String email, String nonce, long timestamp, String hmac) throws Exception {

        // VÉRIFICATION 1 : email existe ?
        User user = userRepository.findByEmail(email)
                .orElseThrow(() -> new AuthenticationFailedException("Authentification échouée"));

        // VÉRIFICATION 2 : timestamp dans fenêtre ±60 secondes ?
        long currentTime = System.currentTimeMillis() / 1000;
        if (Math.abs(currentTime - timestamp) > 60) {
            throw new AuthenticationFailedException("Authentification échouée");
        }

        // VÉRIFICATION 3 : nonce déjà utilisé ?
        Optional<AuthNonce> existingNonce = nonceRepository.findByUserAndNonce(user, nonce);
        if (existingNonce.isPresent()) {
            throw new AuthenticationFailedException("Authentification échouée");
        }

        // VÉRIFICATION 4 : HMAC valide ?
        // On déchiffre d'abord le mot de passe stocké en base
        String passwordPlain = encryptionService.decrypt(user.getPasswordEncrypted());

        // On reconstruit le message et on recalcule le HMAC
        String message = email + ":" + nonce + ":" + timestamp;
        String expectedHmac = calculateHmac(passwordPlain, message);

        // Comparaison en temps constant
        if (!MessageDigest.isEqual(
                expectedHmac.getBytes(StandardCharsets.UTF_8),
                hmac.getBytes(StandardCharsets.UTF_8))) {
            throw new AuthenticationFailedException("Authentification échouée");
        }

        // SUCCÈS — consomme le nonce
        AuthNonce usedNonce = new AuthNonce();
        usedNonce.setUser(user);
        usedNonce.setNonce(nonce);
        usedNonce.setExpiresAt(LocalDateTime.now().plusMinutes(2));
        usedNonce.setConsumed(true);
        nonceRepository.save(usedNonce);

        return UUID.randomUUID().toString();
    }

    // ===================================
// CHANGEMENT DE MOT DE PASSE
// ===================================

    /**
     * Change le mot de passe d'un utilisateur authentifié.
     *
     * Processus :
     * 1. Vérifie que l'utilisateur existe
     * 2. Déchiffre l'ancien mot de passe et vérifie qu'il est correct
     * 3. Vérifie que newPassword = confirmPassword
     * 4. Vérifie les règles de sécurité du nouveau mot de passe
     * 5. Chiffre le nouveau mot de passe avec la Master Key
     * 6. Met à jour en base
     *
     * @param email           Email de l'utilisateur
     * @param oldPassword     Ancien mot de passe en clair
     * @param newPassword     Nouveau mot de passe en clair
     * @param confirmPassword Confirmation du nouveau mot de passe
     */
    public void changePassword(String email, String oldPassword,
                               String newPassword, String confirmPassword) throws Exception {

        // ÉTAPE 1 : l'utilisateur existe ?
        User user = userRepository.findByEmail(email)
                .orElseThrow(() -> new InvalidInputException("Utilisateur introuvable"));

        // ÉTAPE 2 : l'ancien mot de passe est-il correct ?
        // On déchiffre le mot de passe stocké et on compare
        String storedPassword = encryptionService.decrypt(user.getPasswordEncrypted());
        if (!storedPassword.equals(oldPassword)) {
            throw new AuthenticationFailedException("Ancien mot de passe incorrect");
        }

        // ÉTAPE 3 : newPassword = confirmPassword ?
        if (!newPassword.equals(confirmPassword)) {
            throw new InvalidInputException("Les mots de passe ne correspondent pas");
        }

        // ÉTAPE 4 : le nouveau mot de passe respecte les règles ?
        validatePasswordStrength(newPassword);

        // ÉTAPE 5 : chiffre le nouveau mot de passe avec la Master Key
        String encryptedNewPassword = encryptionService.encrypt(newPassword);

        // ÉTAPE 6 : met à jour en base
        user.setPasswordEncrypted(encryptedNewPassword);
        userRepository.save(user);
    }

    /**
     * Vérifie que le mot de passe respecte les règles de sécurité.
     * Minimum 12 caractères, une majuscule, une minuscule,
     * un chiffre et un caractère spécial.
     *
     * @param password Le mot de passe à valider
     */
    private void validatePasswordStrength(String password) {
        if (password == null || password.length() < 12) {
            throw new InvalidInputException("Le mot de passe doit faire au moins 12 caractères");
        }
        if (!password.matches(".*[A-Z].*")) {
            throw new InvalidInputException("Le mot de passe doit contenir au moins une majuscule");
        }
        if (!password.matches(".*[a-z].*")) {
            throw new InvalidInputException("Le mot de passe doit contenir au moins une minuscule");
        }
        if (!password.matches(".*[0-9].*")) {
            throw new InvalidInputException("Le mot de passe doit contenir au moins un chiffre");
        }
        if (!password.matches(".*[!@#$%^&*()_+\\-=\\[\\]{};':\"\\\\|,.<>/?].*")) {
            throw new InvalidInputException("Le mot de passe doit contenir au moins un caractère spécial");
        }
    }

    // ===================================
    // MÉTHODE PRIVÉE — CALCUL HMAC
    // ===================================

    /**
     * Calcule une signature HMAC-SHA256.
     *
     * @param key     La clé secrète = le mot de passe de l'utilisateur
     * @param message Le message à signer = email:nonce:timestamp
     * @return La signature en hexadécimal
     */
    private String calculateHmac(String key, String message) {
        try {
            Mac mac = Mac.getInstance("HmacSHA256");
            SecretKeySpec secretKey = new SecretKeySpec(
                    key.getBytes(StandardCharsets.UTF_8),
                    "HmacSHA256"
            );
            mac.init(secretKey);
            byte[] hmacBytes = mac.doFinal(message.getBytes(StandardCharsets.UTF_8));
            return HexFormat.of().formatHex(hmacBytes);
        } catch (Exception e) {
            throw new RuntimeException("Erreur lors du calcul HMAC", e);
        }
    }
}