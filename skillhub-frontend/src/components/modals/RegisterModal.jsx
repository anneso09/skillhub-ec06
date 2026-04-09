import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../../context/AuthContext";
import api from "../../api/axios";
import styles from "./RegisterModal.module.css";

export default function RegisterModal({ onClose, onSwitchToLogin }) {
  const { login } = useAuth();
  const navigate = useNavigate();

  const [form, setForm] = useState({
    nom: "",
    prenom: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "apprenant",
  });
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
    setError("");
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (form.password !== form.password_confirmation) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }
    setLoading(true);
    setError("");
    try {
      // 1. Inscription
      await api.post("/register", form);
      // 2. Connexion automatique après inscription
      const user = await login(form.email, form.password);
      onClose();
      if (user.role === "formateur") {
        navigate("/dashboard/formateur");
      } else {
        navigate("/dashboard/apprenant");
      }
    } catch (err) {
      const errors = err.response?.data?.errors;
      if (errors) {
        // Affiche la première erreur de validation Laravel
        const firstError = Object.values(errors)[0][0];
        setError(firstError);
      } else {
        setError(err.response?.data?.message || "Une erreur est survenue.");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      className={styles.overlay}
      onClick={(e) => e.target === e.currentTarget && onClose()}
    >
      <div className={styles.modal}>
        {/* Header */}
        <div className={styles.header}>
          <h3 className={styles.title}>Créer un compte SkillHub</h3>
          <button className={styles.closeBtn} onClick={onClose}>
            ×
          </button>
        </div>

        {/* Body */}
        <form className={styles.body} onSubmit={handleSubmit}>
          {error && <div className={styles.error}>{error}</div>}

          {/* Choix du rôle — CDC : l'utilisateur choisit apprenant ou formateur */}
          <div>
            <div className={styles.label} style={{ marginBottom: 8 }}>
              Je suis...
            </div>
            <div className={styles.roleGrid}>
              <div
                className={`${styles.roleCard} ${form.role === "apprenant" ? styles.roleCardActive : ""}`}
                onClick={() => setForm({ ...form, role: "apprenant" })}
              >
                <div className={styles.roleEmoji}>🎓</div>
                <div className={styles.roleLabel}>Apprenant</div>
                <div className={styles.roleDesc}>Je veux apprendre</div>
              </div>
              <div
                className={`${styles.roleCard} ${form.role === "formateur" ? styles.roleCardActive : ""}`}
                onClick={() => setForm({ ...form, role: "formateur" })}
              >
                <div className={styles.roleEmoji}>🧑‍🏫</div>
                <div className={styles.roleLabel}>Formateur</div>
                <div className={styles.roleDesc}>Je veux enseigner</div>
              </div>
            </div>
          </div>

          <div className={styles.nameGrid}>
            <div className={styles.field}>
              <label className={styles.label}>Prénom</label>
              <input
                className={styles.input}
                type="text"
                name="prenom"
                placeholder="Ton prénom"
                value={form.prenom}
                onChange={handleChange}
                required
              />
            </div>

            <div className={styles.field}>
              <label className={styles.label}>Nom</label>
              <input
                className={styles.input}
                type="text"
                name="nom"
                placeholder="Ton nom"
                value={form.nom}
                onChange={handleChange}
                required
              />
            </div>
          </div>

          <div className={styles.field}>
            <label className={styles.label}>Adresse email</label>
            <input
              className={styles.input}
              type="email"
              name="email"
              placeholder="ton@email.com"
              value={form.email}
              onChange={handleChange}
              required
            />
          </div>

          <div className={styles.field}>
            <label className={styles.label}>Mot de passe</label>
            <input
              className={styles.input}
              type="password"
              name="password"
              placeholder="Minimum 8 caractères"
              value={form.password}
              onChange={handleChange}
              required
            />
          </div>

          <div className={styles.field}>
            <label className={styles.label}>Confirmer le mot de passe</label>
            <input
              className={styles.input}
              type="password"
              name="password_confirmation"
              placeholder="••••••••"
              value={form.password_confirmation}
              onChange={handleChange}
              required
            />
          </div>

          <button className={styles.btnSubmit} type="submit" disabled={loading}>
            {loading
              ? "Création du compte..."
              : "Créer mon compte gratuitement"}
          </button>

          <p className={styles.switchText}>
            Déjà un compte ?{" "}
            <button
              type="button"
              className={styles.switchLink}
              onClick={onSwitchToLogin}
            >
              Se connecter
            </button>
          </p>
        </form>
      </div>
    </div>
  );
}
