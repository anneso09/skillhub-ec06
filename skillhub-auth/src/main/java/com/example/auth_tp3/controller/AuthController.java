package com.example.auth_tp3.controller;

import java.util.HashMap;
import java.util.Map;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.CrossOrigin;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import com.example.auth_tp3.entity.User;
import com.example.auth_tp3.repository.UserRepository;
import com.example.auth_tp3.service.AuthService;
import com.example.auth_tp3.service.JwtService;

import io.jsonwebtoken.Claims;

@RestController
@RequestMapping("/api")
@CrossOrigin(origins = {"http://localhost:5173", "http://127.0.0.1:5173"})
public class AuthController {

    private final AuthService authService;
    private final UserRepository userRepository;
    private final JwtService jwtService;

    public AuthController(AuthService authService,
                          UserRepository userRepository,
                          JwtService jwtService) {
        this.authService = authService;
        this.userRepository = userRepository;
        this.jwtService = jwtService;
    }

    // POST /api/auth/register
    @PostMapping("/auth/register")
    public ResponseEntity<Map<String, Object>> register(
            @RequestBody RegisterRequest request) throws Exception {

        User user = authService.register(
                request.getNom(),
                request.getPrenom(),
                request.getEmail(),
                request.getPassword(),
                request.getRole()
        );

        Map<String, Object> response = new HashMap<>();
        response.put("message", "Inscription réussie");
        response.put("email", user.getEmail());

        return ResponseEntity.status(HttpStatus.CREATED).body(response);
    }

    // POST /api/auth/login
    @PostMapping("/auth/login")
    public ResponseEntity<Map<String, Object>> login(
            @RequestBody LoginRequest request) throws Exception {

        String token = authService.login(
                request.getEmail(),
                request.getPassword()
        );

        User user = userRepository.findByEmail(request.getEmail()).orElseThrow();

        Map<String, Object> response = new HashMap<>();
        response.put("accessToken", token);
        response.put("role", user.getRole());

        return ResponseEntity.ok(response);
    }

    // POST /api/auth/validate
    @PostMapping("/auth/validate")
    public ResponseEntity<Map<String, Object>> validate(
            @RequestBody Map<String, String> body) {
        try {
            String token = body.get("token");
            Claims claims = jwtService.validateToken(token);

            Map<String, Object> response = new HashMap<>();
            response.put("email", claims.getSubject());
            response.put("role", claims.get("role", String.class));
            response.put("userId", claims.get("userId", Long.class));

            return ResponseEntity.ok(response);
        } catch (Exception e) {
            return ResponseEntity.status(401)
                    .body(Map.of("message", "Token invalide"));
        }
    }
}