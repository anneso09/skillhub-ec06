import { createContext, useContext, useState, useEffect } from "react";
import authApi from "../api/authApi";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser]       = useState(null);
  const [token, setToken]     = useState(null);
  const [loading, setLoading] = useState(true);

  // ─────────────────────────────────────────────────────────────
  // Rehydratation au refresh de page
  // Quand l'utilisateur rafraîchit, React repart de zéro.
  // On relit localStorage pour restaurer la session sans forcer
  // un nouveau login.
  // ─────────────────────────────────────────────────────────────
  useEffect(() => {
    const storedToken = localStorage.getItem("skillhub_token");
    const storedUser  = localStorage.getItem("skillhub_user");

    if (storedToken && storedUser) {
      setToken(storedToken);
      const parsedUser = JSON.parse(storedUser);
      setUser(parsedUser);
      console.log("Session restaurée :", parsedUser);
    }

    // Important : loading passe à false dans tous les cas
    // Sinon l'app reste bloquée sur l'écran de chargement
    setLoading(false);
  }, []);


  // ─────────────────────────────────────────────────────────────
  // Login
  // Appelle Spring Boot (port 8080) qui renvoie :
  // { accessToken, role, nom, prenom, email }
  // ─────────────────────────────────────────────────────────────
  const login = async (email, password) => {
    try {
      const response = await authApi.post("/auth/login", { email, password });

      const receivedToken = response.data.accessToken;

      if (!receivedToken) {
        throw new Error("Token non reçu de Spring Boot");
      }

      // ✅ On récupère maintenant nom et prenom depuis la réponse
      // Spring Boot doit les renvoyer (on vient de le corriger)
      const userData = {
        email:  response.data.email  || email,
        role:   response.data.role,
        nom:    response.data.nom,    // ✅ ajouté
        prenom: response.data.prenom, // ✅ ajouté
      };

      // On stocke token et user séparément dans localStorage
      // userData est sérialisé en JSON car localStorage ne stocke
      // que des strings
      localStorage.setItem("skillhub_token", receivedToken);
      localStorage.setItem("skillhub_user",  JSON.stringify(userData));

      setToken(receivedToken);
      setUser(userData);

      return userData;

    } catch (error) {
      console.error("Erreur login :", error);
      throw error;
    }
  };


  // ─────────────────────────────────────────────────────────────
  // Register
  // Appelle Spring Boot pour créer le compte.
  // On envoie exactement les champs attendus par RegisterRequest.java
  // ─────────────────────────────────────────────────────────────
  const register = async (formData) => {
    await authApi.post("/auth/register", {
      nom:      formData.nom,
      prenom:   formData.prenom,
      email:    formData.email,
      password: formData.password,
      role:     formData.role,
    });
  };


  // ─────────────────────────────────────────────────────────────
  // Logout
  // On vide localStorage et les states React.
  // L'appel API est optionnel (Spring Boot JWT est stateless —
  // il n'y a pas de session serveur à détruire).
  // ─────────────────────────────────────────────────────────────
  const logout = () => {
    localStorage.removeItem("skillhub_token");
    localStorage.removeItem("skillhub_user");
    setToken(null);
    setUser(null);
  };


  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        loading,
        // ✅ isAuthenticated basé sur token ET user
        isAuthenticated: !!token && !!user,
        isFormateur:     user?.role === "formateur",
        isApprenant:     user?.role === "apprenant",
        login,
        register,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth doit être utilisé dans AuthProvider");
  return context;
}