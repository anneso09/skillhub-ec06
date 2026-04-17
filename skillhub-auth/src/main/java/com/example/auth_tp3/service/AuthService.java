package com.example.auth_tp3.service;

import org.springframework.stereotype.Service;

import com.example.auth_tp3.entity.User;
import com.example.auth_tp3.exception.AuthenticationFailedException;
import com.example.auth_tp3.exception.InvalidInputException;
import com.example.auth_tp3.exception.ResourceConflictException;
import com.example.auth_tp3.repository.UserRepository;

@Service
public class AuthService {

    private final UserRepository userRepository;
    private final EncryptionService encryptionService;
    private final JwtService jwtService;

    public AuthService(UserRepository userRepository,
                       EncryptionService encryptionService,
                       JwtService jwtService) {
        this.userRepository = userRepository;
        this.encryptionService = encryptionService;
        this.jwtService = jwtService;
    }

    // INSCRIPTION
    public User register(String nom, String prenom,
                         String email, String password,
                         String role) throws Exception {

        if (email == null || !email.contains("@")) {
            throw new InvalidInputException("Format d'email invalide");
        }
        if (password == null || password.length() < 12) {
            throw new InvalidInputException("Le mot de passe doit faire au moins 12 caractères");
        }
        if (userRepository.existsByEmail(email)) {
            throw new ResourceConflictException("Cet email est déjà utilisé");
        }
        if (role == null || (!role.equals("apprenant") && !role.equals("formateur"))) {
            throw new InvalidInputException("Le rôle doit être apprenant ou formateur");
        }

        String encryptedPassword = encryptionService.encrypt(password);

        User user = new User();
        user.setNom(nom);
        user.setPrenom(prenom);
        user.setEmail(email);
        user.setPassword(encryptedPassword);
        user.setRole(role);

        return userRepository.save(user);
    }

    // LOGIN
    public String login(String email, String password) throws Exception {

        User user = userRepository.findByEmail(email)
                .orElseThrow(() ->
                    new AuthenticationFailedException("Email ou mot de passe incorrect"));

        String storedPassword = encryptionService.decrypt(user.getPassword());
        if (!storedPassword.equals(password)) {
            throw new AuthenticationFailedException("Email ou mot de passe incorrect");
        }

        return jwtService.generateToken(user);
    }
}