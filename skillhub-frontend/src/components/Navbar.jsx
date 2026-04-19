import { useState } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import styles from "./Navbar.module.css";

export default function Navbar({ onOpenLogin, onOpenRegister }) {
  const { isAuthenticated, isFormateur, user, logout } = useAuth();
  const navigate  = useNavigate();
  const location  = useLocation();
  const [dropdownOpen, setDropdownOpen] = useState(false);

  const handleLogout = async () => {
    setDropdownOpen(false);
    await logout();
    navigate("/");
  };

  const dashboardPath = isFormateur
    ? "/dashboard/formateur"
    : "/dashboard/apprenant";

  const formationsClass = location.pathname.startsWith("/formation")
    ? `${styles.navLink} ${styles.navLinkActive}`
    : styles.navLink;

  // ─────────────────────────────────────────────────────────────
  // Initiales de l'avatar : on prend la 1ère lettre du prénom
  // Si prenom est undefined (session pas encore chargée),
  // on affiche "?" pour éviter un avatar vide
  // ─────────────────────────────────────────────────────────────
  const initiale = user?.prenom?.charAt(0).toUpperCase() ?? "?";

  // Nom complet affiché à côté de l'avatar
  const fullName = user?.prenom && user?.nom
    ? `${user.prenom} ${user.nom}`
    : user?.email ?? "";

  return (
    <nav className={styles.navbar}>

      {/* Logo */}
      <Link to="/" className={styles.logo}>
        Skill<span className={styles.logoAccent}>Hub</span>
      </Link>

      {/* Droite */}
      <div className={styles.navRight}>
        <Link to="/formations" className={formationsClass}>
          Formations
        </Link>

        {!isAuthenticated ? (
          <>
            <button className={styles.btnConnexion} onClick={onOpenLogin}>
              Connexion
            </button>
            <button className={styles.btnInscription} onClick={onOpenRegister}>
              S'inscrire
            </button>
          </>
        ) : (
          /* ── Connecté : avatar + dropdown ── */
          <div
            className={styles.profileWrapper}
            onMouseEnter={() => setDropdownOpen(true)}
            onMouseLeave={() => setDropdownOpen(false)}
          >
            <div className={styles.profileTrigger}>

              {/* Avatar rond avec initiale du prénom */}
              <div className={`${styles.avatar} ${isFormateur ? styles.avatarFormateur : styles.avatarApprenant}`}>
                {initiale}
              </div>

              {/* Prénom + Nom à côté de l'avatar */}
              <span className={styles.profileName}>{fullName}</span>
            </div>

            {dropdownOpen && (
              <div className={styles.dropdown}>

                <div className={styles.dropdownHeader}>
                  {/* ✅ Corrigé : user.name → user.prenom + user.nom */}
                  <div className={styles.dropdownName}>{fullName}</div>
                  <div className={styles.dropdownRole}>
                    {isFormateur ? "Formateur" : "Apprenant"}
                  </div>
                </div>

                <div className={styles.dropdownBody}>
                  <button
                    className={styles.dropdownBtn}
                    onClick={() => {
                      setDropdownOpen(false);
                      navigate(dashboardPath);
                    }}
                  >
                    🏠 Mon dashboard
                  </button>
                  <div className={styles.dropdownDivider} />
                  <button
                    className={`${styles.dropdownBtn} ${styles.dropdownBtnDanger}`}
                    onClick={handleLogout}
                  >
                    🚪 Se déconnecter
                  </button>
                </div>

              </div>
            )}
          </div>
        )}
      </div>
    </nav>
  );
}