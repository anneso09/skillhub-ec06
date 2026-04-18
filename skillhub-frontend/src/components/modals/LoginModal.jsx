import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import styles from './LoginModal.module.css';

export default function LoginModal({ onClose, onSwitchToRegister }) {
  const { login, register } = useAuth();
  const navigate  = useNavigate();

  const [form,    setForm]    = useState({ email: '', password: '' });
  const [error,   setError]   = useState('');
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
    setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const user = await login(form.email, form.password);
      onClose();
      // Redirection selon le rôle
      if (user.role === 'formateur') {
        navigate('/dashboard/formateur');
      } else {
        navigate('/dashboard/apprenant');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Email ou mot de passe incorrect.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className={styles.overlay} onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className={styles.modal}>

        {/* Header */}
        <div className={styles.header}>
          <h3 className={styles.title}>Connexion à SkillHub</h3>
          <button className={styles.closeBtn} onClick={onClose}>×</button>
        </div>

        {/* Body */}
        <form className={styles.body} onSubmit={handleSubmit}>

          {error && <div className={styles.error}>{error}</div>}

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
              placeholder="••••••••"
              value={form.password}
              onChange={handleChange}
              required
            />
          </div>

          <button className={styles.btnSubmit} type="submit" disabled={loading}>
            {loading ? 'Connexion...' : 'Se connecter'}
          </button>

          <p className={styles.switchText}>
            Pas encore de compte ?{' '}
            <button
              type="button"
              className={styles.switchLink}
              onClick={onSwitchToRegister}
            >
              S'inscrire gratuitement
            </button>
          </p>
        </form>
      </div>
    </div>
  );
}