// src/config/api.js

export const SPRING_BOOT_URL = "http://localhost:8080/api/auth";
export const LARAVEL_URL = "http://localhost:8000/api"; 

// Tu peux même préparer les endpoints précis pour gagner du temps
export const ENDPOINTS = {
    LOGIN: `${SPRING_BOOT_URL}/login`,
    REGISTER: `${SPRING_BOOT_URL}/register`,
    FORMATIONS: `${LARAVEL_URL}/formations`,
};