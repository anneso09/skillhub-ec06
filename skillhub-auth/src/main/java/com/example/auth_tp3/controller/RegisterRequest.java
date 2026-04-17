package com.example.auth_tp3.controller;

import lombok.Data;

@Data
public class RegisterRequest {
    private String nom;
    private String prenom;
    private String email;
    private String password;
    private String role; // "apprenant" ou "formateur"
}