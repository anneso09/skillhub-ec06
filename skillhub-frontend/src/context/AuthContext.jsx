import { createContext, useContext, useState, useEffect } from "react";
import axios from 'axios';
// import { SPRING_BOOT_URL } from '../config/api';
import authApi from "../api/authApi";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [loading, setLoading] = useState(true);

  // Rehydrate au refresh de page
  useEffect(() => {
    const storedToken = localStorage.getItem("skillhub_token");
    const storedUser = localStorage.getItem("skillhub_user");
    if (storedToken && storedUser) {
      setToken(storedToken);
      const parsedUser = JSON.parse(storedUser);
    setUser(parsedUser); // <--- Vérifie que parsedUser contient bien .role
    console.log("Utilisateur rechargé:", parsedUser);
    }
    setLoading(false);
  }, []);


  // Login
// const login = async (email, password) => {
//   try {

//     const response = await authApi.post("/auth/login", { email, password });

//     const token = response.data.accessToken; 

//     if (!token) {
//         throw new Error("Token non reçu du serveur");
//     }

//     const userData = {
//       email: email,
//       role: "apprenant" 
//     };

//     setUser(userData);
//     localStorage.setItem("skillhub_token", token);
//     localStorage.setItem("skillhub_user", JSON.stringify(userData));

//     return userData;
//   } catch (error) {
//     console.error("Erreur de connexion :", error);
//     throw error;
//   }
// };

const login = async (email, password) => {
        try {
            // On envoie email/password (comme son handleLogin le fait)
            const response = await authApi.post("/auth/login", { email, password });

            // ADAPTATION 1 : On récupère accessToken
            const token = response.data.accessToken;

            // ADAPTATION 2 : On crée l'objet utilisateur manuellement
            // Puisque son Java ne renvoie pas le rôle, on l'injecte ici
            const userData = {
                email: email,
                role: response.data.role // On le force pour débloquer tes ProtectedRoutes
            };

            // Stockage pour rester connecté au rafraîchissement
            localStorage.setItem("skillhub_token", token);
            localStorage.setItem("skillhub_user", JSON.stringify(userData));
            
            setUser(userData);
            return userData;
        } catch (error) {
            console.error("Erreur login adaptée:", error);
            throw error;
        }
    };

// Register
const register = async (formData) => {
    // Appel vers Spring Boot au lieu de Laravel
    await authApi.post("/auth/register", {
      nom: formData.nom,
      prenom: formData.prenom,
      email: formData.email,
      password: formData.password,
      role: formData.role,
    });
  };

  // Logout
  const logout = async () => {
    try {
      await api.post("/auth/logout");
    } catch (e) {
      // déconnexion front même si erreur réseau
    } finally {
      localStorage.removeItem("skillhub_token");
      localStorage.removeItem("skillhub_user");
      setToken(null);
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        loading,
        isAuthenticated: !!token,
        isFormateur: user?.role === "formateur",
        isApprenant: user?.role === "apprenant",
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
