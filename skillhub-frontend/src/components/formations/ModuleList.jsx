import styles from './ModuleList.module.css';

export default function ModuleList({ modules, enrolled }) {
  return (
    <div className={styles.list}>
      {modules.map((m, i) => {
        // Si non inscrit : tout gris
        if (!enrolled) {
          return (
            <div key={m.id} className={styles.module}>
              <div className={`${styles.moduleIcon} ${styles.moduleIconDefault}`}>
                {i + 1}
              </div>
              <div className={styles.moduleInfo}>
                <div className={`${styles.moduleNum} ${styles.moduleNumDefault}`}>
                  Module {i + 1}
                </div>
                <div className={`${styles.moduleTitle} ${styles.moduleTitleLocked}`}>
                  {m.titre}
                </div>
              </div>
            </div>
          );
        }

        // Inscrit : statut selon progression
        const status = m.status ?? 'locked';

        if (status === 'done') {
          return (
            <div key={m.id} className={`${styles.module} ${styles.moduleDone}`}>
              <div className={`${styles.moduleIcon} ${styles.moduleIconDone}`}>✓</div>
              <div className={styles.moduleInfo}>
                <div className={`${styles.moduleNum} ${styles.moduleNumDone}`}>Module {i + 1}</div>
                <div className={styles.moduleTitle}>{m.titre}</div>
                {m.contenu_resume && (
                  <div className={`${styles.moduleDesc} ${styles.moduleDescVisible}`}>{m.contenu_resume}</div>
                )}
              </div>
              <span className={`${styles.moduleBadge} ${styles.moduleBadgeDone}`}>Complété</span>
            </div>
          );
        }

        if (status === 'current') {
          return (
            <div key={m.id} className={`${styles.module} ${styles.moduleCurrent}`}>
              <div className={`${styles.moduleIcon} ${styles.moduleIconCurrent}`}>▶</div>
              <div className={styles.moduleInfo}>
                <div className={`${styles.moduleNum} ${styles.moduleNumCurrent}`}>
                  Module {i + 1} — En cours
                </div>
                <div className={styles.moduleTitle}>{m.titre}</div>
              </div>
              <span className={`${styles.moduleBadge} ${styles.moduleBadgeCurrent}`}>En cours</span>
            </div>
          );
        }

        // locked
        return (
          <div key={m.id} className={styles.module}>
            <div className={`${styles.moduleIcon} ${styles.moduleIconDefault}`}>{i + 1}</div>
            <div className={styles.moduleInfo}>
              <div className={`${styles.moduleNum} ${styles.moduleNumDefault}`}>Module {i + 1}</div>
              <div className={`${styles.moduleTitle} ${styles.moduleTitleLocked}`}>{m.titre}</div>
            </div>
            <span className={`${styles.moduleBadge} ${styles.moduleBadgeLocked}`}>À venir</span>
          </div>
        );
      })}
    </div>
  );
}