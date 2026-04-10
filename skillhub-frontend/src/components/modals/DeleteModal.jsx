import { useState } from 'react';
import styles from './DeleteModal.module.css';

export default function DeleteModal({ onClose, onConfirm, title, message, warning }) {
  const [loading, setLoading] = useState(false);

  const handleConfirm = async () => {
    setLoading(true);
    await onConfirm();
    setLoading(false);
  };

  return (
    <div className={styles.overlay} onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className={styles.modal}>
        <div className={styles.body}>
          <div className={styles.iconWrapper}>🗑️</div>
          <h3 className={styles.title}>{title}</h3>
          <p className={styles.sub}>{message}</p>
          {warning && <div className={styles.warning}>⚠️ {warning}</div>}
        </div>
        <div className={styles.footer}>
          <button className={styles.btnCancel} onClick={onClose}>Annuler</button>
          <button className={styles.btnDelete} onClick={handleConfirm} disabled={loading}>
            {loading ? 'Suppression...' : 'Oui, supprimer'}
          </button>
        </div>
      </div>
    </div>
  );
}