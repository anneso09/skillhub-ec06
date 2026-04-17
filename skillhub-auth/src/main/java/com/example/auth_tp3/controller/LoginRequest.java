package com.example.auth_tp3.controller;

import lombok.Data;

@Data
public class LoginRequest {
    private String email;
    private String password;
}