import { createContext, useContext, useState, useEffect } from "react";
import api from "../api/axios";

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
      setUser(JSON.parse(storedUser));
    }
    setLoading(false);
  }, []);

  const login = async (email, password) => {
    const response = await api.post("/login", { email, password });
    const { token, access_token, user: userData } = response.data;
    const finalToken = access_token ?? token;
    localStorage.setItem("skillhub_token", finalToken);
    localStorage.setItem("skillhub_user", JSON.stringify(userData));
    setToken(finalToken);
    setUser(userData); // ← manquait cette ligne !
    return userData;
  };

  const logout = async () => {
    try {
      await api.post("/logout");
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
