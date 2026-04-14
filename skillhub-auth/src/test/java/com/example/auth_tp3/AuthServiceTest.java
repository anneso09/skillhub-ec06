package com.example.auth_tp3;

import com.example.auth_tp3.entity.AuthNonce;
import com.example.auth_tp3.entity.User;
import com.example.auth_tp3.exception.AuthenticationFailedException;
import com.example.auth_tp3.exception.InvalidInputException;
import com.example.auth_tp3.exception.ResourceConflictException;
import com.example.auth_tp3.repository.NonceRepository;
import com.example.auth_tp3.repository.UserRepository;
import com.example.auth_tp3.service.AuthService;
import com.example.auth_tp3.service.EncryptionService;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.extension.ExtendWith;
import org.mockito.InjectMocks;
import org.mockito.Mock;
import org.mockito.junit.jupiter.MockitoExtension;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.nio.charset.StandardCharsets;
import java.util.HexFormat;
import java.util.Optional;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.ArgumentMatchers.eq;
import static org.mockito.Mockito.*;

/**
 * Tests unitaires pour AuthService TP4.
 * On mocke les repositories ET EncryptionService
 * pour tester la logique sans dépendances externes.
 */
@ExtendWith(MockitoExtension.class)
class AuthServiceTest {

    @Mock
    private UserRepository userRepository;

    @Mock
    private NonceRepository nonceRepository;

    @Mock
    private EncryptionService encryptionService;

    @InjectMocks
    private AuthService authService;

    private User testUser;
    private static final String TEST_PASSWORD = "MonMotDePasse123!";
    private static final String TEST_EMAIL = "alice@mail.com";
    private static final String ENCRYPTED_PASSWORD = "v1:aGVsbG8=:eW91cmNpcGhlcg==";

    @BeforeEach
    void setUp() {
        testUser = new User();
        testUser.setEmail(TEST_EMAIL);
        testUser.setPasswordEncrypted(ENCRYPTED_PASSWORD);
    }

    // ===================================
    // TESTS INSCRIPTION
    // ===================================

    @Test
    void inscription_OK() throws Exception {
        when(userRepository.existsByEmail(TEST_EMAIL)).thenReturn(false);
        when(encryptionService.encrypt(TEST_PASSWORD)).thenReturn(ENCRYPTED_PASSWORD);
        when(userRepository.save(any(User.class))).thenReturn(testUser);

        User result = authService.register(TEST_EMAIL, TEST_PASSWORD);

        assertNotNull(result);
        assertEquals(TEST_EMAIL, result.getEmail());
        // Vérifie que le mot de passe a bien été chiffré avant stockage
        verify(encryptionService, times(1)).encrypt(TEST_PASSWORD);
    }

//    @Test
//    void inscription_OK() throws Exception {
//        // TEST VOLONTAIREMENT CASSÉ POUR TESTER LE PIPELINE
//        assertEquals("mauvaise valeur", "autre valeur");
//    }

    @Test
    void inscription_KO_email_deja_existant() {
        when(userRepository.existsByEmail(TEST_EMAIL)).thenReturn(true);

        assertThrows(ResourceConflictException.class, () ->
                authService.register(TEST_EMAIL, TEST_PASSWORD)
        );
    }

    @Test
    void inscription_KO_email_vide() {
        assertThrows(InvalidInputException.class, () ->
                authService.register("", TEST_PASSWORD)
        );
    }

    @Test
    void inscription_KO_email_format_invalide() {
        assertThrows(InvalidInputException.class, () ->
                authService.register("pasUnEmail", TEST_PASSWORD)
        );
    }

    @Test
    void inscription_KO_mot_de_passe_trop_court() {
        assertThrows(InvalidInputException.class, () ->
                authService.register(TEST_EMAIL, "court")
        );
    }

    @Test
    void inscription_mot_de_passe_chiffre_avant_stockage() throws Exception {
        // Vérifie que encrypt() est bien appelé
        // et que le mot de passe en clair n'est jamais sauvegardé
        when(userRepository.existsByEmail(TEST_EMAIL)).thenReturn(false);
        when(encryptionService.encrypt(TEST_PASSWORD)).thenReturn(ENCRYPTED_PASSWORD);
        when(userRepository.save(any(User.class))).thenReturn(testUser);

        authService.register(TEST_EMAIL, TEST_PASSWORD);

        // encrypt() doit être appelé exactement une fois
        verify(encryptionService, times(1)).encrypt(TEST_PASSWORD);
        // Le mot de passe en clair ne doit jamais être passé à save()
        verify(userRepository, times(1)).save(argThat(user ->
                ENCRYPTED_PASSWORD.equals(user.getPasswordEncrypted())
        ));
    }

    // ===================================
    // TESTS LOGIN
    // ===================================

    @Test
    void login_OK_hmac_valide() throws Exception {
        long timestamp = System.currentTimeMillis() / 1000;
        String nonce = "uuid-test-123";
        String message = TEST_EMAIL + ":" + nonce + ":" + timestamp;
        String hmac = calculateHmac(TEST_PASSWORD, message);

        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));
        when(nonceRepository.findByUserAndNonce(any(), any())).thenReturn(Optional.empty());
        when(encryptionService.decrypt(ENCRYPTED_PASSWORD)).thenReturn(TEST_PASSWORD);
        when(nonceRepository.save(any(AuthNonce.class))).thenReturn(new AuthNonce());

        String token = authService.login(TEST_EMAIL, nonce, timestamp, hmac);

        assertNotNull(token);
        assertFalse(token.isEmpty());
        // Vérifie que decrypt() est bien appelé pour récupérer le mot de passe
        verify(encryptionService, times(1)).decrypt(ENCRYPTED_PASSWORD);
    }

    @Test
    void login_KO_hmac_invalide() throws Exception {
        long timestamp = System.currentTimeMillis() / 1000;
        String nonce = "uuid-test-123";

        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));
        when(nonceRepository.findByUserAndNonce(any(), any())).thenReturn(Optional.empty());
        when(encryptionService.decrypt(ENCRYPTED_PASSWORD)).thenReturn(TEST_PASSWORD);

        assertThrows(AuthenticationFailedException.class, () ->
                authService.login(TEST_EMAIL, nonce, timestamp, "hmac-faux")
        );
    }

    @Test
    void login_KO_user_inconnu() {
        when(userRepository.findByEmail("inconnu@mail.com")).thenReturn(Optional.empty());

        assertThrows(AuthenticationFailedException.class, () ->
                authService.login("inconnu@mail.com", "nonce",
                        System.currentTimeMillis() / 1000, "hmac")
        );
    }

    @Test
    void login_KO_timestamp_expire() {
        long vieuxTimestamp = (System.currentTimeMillis() / 1000) - 600;
        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));

        assertThrows(AuthenticationFailedException.class, () ->
                authService.login(TEST_EMAIL, "nonce", vieuxTimestamp, "hmac")
        );
    }

    @Test
    void login_KO_timestamp_futur() {
        long futurTimestamp = (System.currentTimeMillis() / 1000) + 600;
        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));

        assertThrows(AuthenticationFailedException.class, () ->
                authService.login(TEST_EMAIL, "nonce", futurTimestamp, "hmac")
        );
    }

    @Test
    void login_KO_nonce_deja_utilise() throws Exception {
        long timestamp = System.currentTimeMillis() / 1000;
        String nonce = "uuid-deja-utilise";

        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));
        when(nonceRepository.findByUserAndNonce(any(), eq(nonce)))
                .thenReturn(Optional.of(new AuthNonce()));

        assertThrows(AuthenticationFailedException.class, () ->
                authService.login(TEST_EMAIL, nonce, timestamp, "hmac")
        );
    }

    @Test
    void login_token_non_null_apres_succes() throws Exception {
        long timestamp = System.currentTimeMillis() / 1000;
        String nonce = "uuid-token-test";
        String message = TEST_EMAIL + ":" + nonce + ":" + timestamp;
        String hmac = calculateHmac(TEST_PASSWORD, message);

        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));
        when(nonceRepository.findByUserAndNonce(any(), any())).thenReturn(Optional.empty());
        when(encryptionService.decrypt(ENCRYPTED_PASSWORD)).thenReturn(TEST_PASSWORD);
        when(nonceRepository.save(any())).thenReturn(new AuthNonce());

        String token = authService.login(TEST_EMAIL, nonce, timestamp, hmac);

        assertNotNull(token);
        assertEquals(36, token.length());
    }

    // ===================================
    // TESTS MASTER KEY — spécifiques TP4
    // ===================================

    @Test
    void encryption_decryption_OK() throws Exception {
        // Teste que encrypt puis decrypt redonne le même mot de passe
        EncryptionService realEncryption = new EncryptionService("uneCleMasterTresLongue123456789!");
        String encrypted = realEncryption.encrypt(TEST_PASSWORD);
        String decrypted = realEncryption.decrypt(encrypted);
        assertEquals(TEST_PASSWORD, decrypted);
    }

    @Test
    void encryption_produit_resultat_different_a_chaque_fois() throws Exception {
        // Deux chiffrements du même mot de passe donnent des résultats différents
        // grâce à l'IV aléatoire
        EncryptionService realEncryption = new EncryptionService("uneCleMasterTresLongue123456789!");
        String encrypted1 = realEncryption.encrypt(TEST_PASSWORD);
        String encrypted2 = realEncryption.encrypt(TEST_PASSWORD);
        assertNotEquals(encrypted1, encrypted2);
    }

    @Test
    void encrypted_password_different_du_password_clair() throws Exception {
        // Le mot de passe chiffré ne doit jamais ressembler au clair
        EncryptionService realEncryption = new EncryptionService("uneCleMasterTresLongue123456789!");
        String encrypted = realEncryption.encrypt(TEST_PASSWORD);
        assertNotEquals(TEST_PASSWORD, encrypted);
        assertTrue(encrypted.startsWith("v1:"));
    }

    @Test
    void decryption_KO_si_ciphertext_modifie() {
        // Si quelqu'un modifie le texte chiffré, le déchiffrement doit échouer
        EncryptionService realEncryption = new EncryptionService("uneCleMasterTresLongue123456789!");
        assertThrows(Exception.class, () ->
                realEncryption.decrypt("v1:aGVsbG8=:DONNEES_MODIFIEES_PAR_ATTAQUANT")
        );
    }

    @Test
    void masterkey_absente_empeche_demarrage() {
        // Si la Master Key est absente, l'application doit refuser de démarrer
        assertThrows(IllegalStateException.class, () ->
                new EncryptionService("")
        );
    }

    // ===================================
    // TESTS EXCEPTIONS ET ENTITÉS
    // ===================================

    @Test
    void globalExceptionHandler_invalidInput() {
        InvalidInputException ex = new InvalidInputException("Email invalide");
        assertEquals("Email invalide", ex.getMessage());
    }

    @Test
    void globalExceptionHandler_authFailed() {
        AuthenticationFailedException ex =
                new AuthenticationFailedException("Authentification échouée");
        assertEquals("Authentification échouée", ex.getMessage());
    }

    @Test
    void globalExceptionHandler_conflict() {
        ResourceConflictException ex =
                new ResourceConflictException("Email déjà utilisé");
        assertEquals("Email déjà utilisé", ex.getMessage());
    }

    @Test
    void user_entity_gettersSetters() {
        User user = new User();
        user.setEmail("test@mail.com");
        user.setPasswordEncrypted(ENCRYPTED_PASSWORD);

        assertEquals("test@mail.com", user.getEmail());
        assertEquals(ENCRYPTED_PASSWORD, user.getPasswordEncrypted());
    }

    @Test
    void authNonce_entity_gettersSetters() {
        AuthNonce nonce = new AuthNonce();
        nonce.setNonce("uuid-test");
        nonce.setConsumed(true);

        assertEquals("uuid-test", nonce.getNonce());
        assertTrue(nonce.getConsumed());
    }


    // ===================================
// TESTS CHANGEMENT MOT DE PASSE — TP5
// ===================================

    @Test
    void changePassword_OK() throws Exception {
        // ARRANGE
        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));
        when(encryptionService.decrypt(ENCRYPTED_PASSWORD)).thenReturn(TEST_PASSWORD);
        when(encryptionService.encrypt("NouveauMotDePasse123!")).thenReturn("v1:nouveau:chiffre");
        when(userRepository.save(any(User.class))).thenReturn(testUser);

        // ACT — ne doit pas lancer d'exception
        assertDoesNotThrow(() ->
                authService.changePassword(
                        TEST_EMAIL,
                        TEST_PASSWORD,
                        "NouveauMotDePasse123!",
                        "NouveauMotDePasse123!"
                )
        );

        // Vérifie que le nouveau mot de passe a été chiffré et sauvegardé
        verify(encryptionService, times(1)).encrypt("NouveauMotDePasse123!");
        verify(userRepository, times(1)).save(any(User.class));
    }

    @Test
    void changePassword_KO_ancien_mot_de_passe_incorrect() throws Exception {
        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));
        // Le mot de passe stocké est TEST_PASSWORD
        when(encryptionService.decrypt(ENCRYPTED_PASSWORD)).thenReturn(TEST_PASSWORD);

        // On envoie un mauvais ancien mot de passe
        assertThrows(AuthenticationFailedException.class, () ->
                authService.changePassword(
                        TEST_EMAIL,
                        "MauvaisMotDePasse123!",
                        "NouveauMotDePasse123!",
                        "NouveauMotDePasse123!"
                )
        );
    }

    @Test
    void changePassword_KO_confirmation_differente() throws Exception {
        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));
        when(encryptionService.decrypt(ENCRYPTED_PASSWORD)).thenReturn(TEST_PASSWORD);

        // newPassword != confirmPassword
        assertThrows(InvalidInputException.class, () ->
                authService.changePassword(
                        TEST_EMAIL,
                        TEST_PASSWORD,
                        "NouveauMotDePasse123!",
                        "MotDePasseDifferent123!"
                )
        );
    }

    @Test
    void changePassword_KO_mot_de_passe_trop_faible() throws Exception {
        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));
        when(encryptionService.decrypt(ENCRYPTED_PASSWORD)).thenReturn(TEST_PASSWORD);

        // Nouveau mot de passe trop court
        assertThrows(InvalidInputException.class, () ->
                authService.changePassword(
                        TEST_EMAIL,
                        TEST_PASSWORD,
                        "court",
                        "court"
                )
        );
    }

    @Test
    void changePassword_KO_utilisateur_inexistant() {
        when(userRepository.findByEmail("inconnu@mail.com")).thenReturn(Optional.empty());

        assertThrows(InvalidInputException.class, () ->
                authService.changePassword(
                        "inconnu@mail.com",
                        TEST_PASSWORD,
                        "NouveauMotDePasse123!",
                        "NouveauMotDePasse123!"
                )
        );
    }

    @Test
    void changePassword_KO_sans_majuscule() throws Exception {
        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));
        when(encryptionService.decrypt(ENCRYPTED_PASSWORD)).thenReturn(TEST_PASSWORD);

        // Pas de majuscule
        assertThrows(InvalidInputException.class, () ->
                authService.changePassword(
                        TEST_EMAIL,
                        TEST_PASSWORD,
                        "nouveaumotdepasse123!",
                        "nouveaumotdepasse123!"
                )
        );
    }

    @Test
    void changePassword_KO_sans_caractere_special() throws Exception {
        when(userRepository.findByEmail(TEST_EMAIL)).thenReturn(Optional.of(testUser));
        when(encryptionService.decrypt(ENCRYPTED_PASSWORD)).thenReturn(TEST_PASSWORD);

        // Pas de caractère spécial
        assertThrows(InvalidInputException.class, () ->
                authService.changePassword(
                        TEST_EMAIL,
                        TEST_PASSWORD,
                        "NouveauMotDePasse123",
                        "NouveauMotDePasse123"
                )
        );
    }

    // ===================================
    // MÉTHODE UTILITAIRE
    // ===================================

    private String calculateHmac(String key, String message) throws Exception {
        Mac mac = Mac.getInstance("HmacSHA256");
        SecretKeySpec secretKey = new SecretKeySpec(
                key.getBytes(StandardCharsets.UTF_8), "HmacSHA256"
        );
        mac.init(secretKey);
        byte[] hmacBytes = mac.doFinal(message.getBytes(StandardCharsets.UTF_8));
        return HexFormat.of().formatHex(hmacBytes);
    }
}