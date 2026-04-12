import { useState, useEffect, useMemo } from 'react';
import api from '../api/axios';
import FiltresSidebar from '../components/formations/FiltresSidebar';
import FormationCard  from '../components/formations/FormationCard';
import styles from './Formations.module.css';

const CATEGORIES = ['Développement web', 'Data & IA', 'Design', 'Marketing', 'DevOps'];
const NIVEAUX    = ['Débutant', 'Intermédiaire', 'Avancé'];
const PER_PAGE   = 9;

const FILTRES_INIT = {
  search: '', categories: [...CATEGORIES], niveaux: [...NIVEAUX], tri: 'popularite',
};

export default function Formations() {
  const [formations, setFormations] = useState([]);
  const [loading,    setLoading]    = useState(true);
  const [filtres,    setFiltres]    = useState(FILTRES_INIT);
  const [page,       setPage]       = useState(1);
  const [drawerOpen, setDrawerOpen] = useState(false); // ← nouveau

  useEffect(() => {
    const fetchFormations = async () => {
      try {
        setLoading(true);
        const res = await api.get('/formations');
        setFormations(res.data.data ?? res.data);
      } catch (err) {
        console.error('Erreur chargement formations', err);
      } finally {
        setLoading(false);
      }
    };
    fetchFormations();
  }, []);

  const filtered = useMemo(() => {
    let result = formations.filter(f => {
      const matchCat    = filtres.categories.includes(f.categorie);
      const matchNiveau = filtres.niveaux.includes(f.niveau);
      const matchSearch = f.titre.toLowerCase().includes(filtres.search.toLowerCase())
                       || f.categorie.toLowerCase().includes(filtres.search.toLowerCase());
      return matchCat && matchNiveau && matchSearch;
    });
    if (filtres.tri === 'vues') {
      result = [...result].sort((a, b) => (b.nombre_de_vues ?? 0) - (a.nombre_de_vues ?? 0));
    } else if (filtres.tri === 'recent') {
      result = [...result].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    } else {
      result = [...result].sort((a, b) => (b.enrollments_count ?? 0) - (a.enrollments_count ?? 0));
    }
    return result;
  }, [formations, filtres]);

  const totalPages = Math.ceil(filtered.length / PER_PAGE);
  const paginated  = filtered.slice((page - 1) * PER_PAGE, page * PER_PAGE);

  const handleFiltresChange = (newFiltres) => { setFiltres(newFiltres); setPage(1); };
  const handleReset         = () => { setFiltres(FILTRES_INIT); setPage(1); };

  const showAllCats    = filtres.categories.length === CATEGORIES.length;
  const showAllNiveaux = filtres.niveaux.length === NIVEAUX.length;
  const hasFilter      = !showAllCats || !showAllNiveaux || filtres.search;

  return (
    <div className={styles.page}>

      {/* Hero */}
      <div className={styles.hero}>
        <span className={styles.heroBadge}>Catalogue</span>
        <h1 className={styles.heroTitle}>Toutes les formations</h1>
        <p className={styles.heroSub}>
          {loading ? '...' : `${filtered.length} formation${filtered.length > 1 ? 's' : ''} disponible${filtered.length > 1 ? 's' : ''} · 100% gratuites`}
        </p>
      </div>

      {/* Layout */}
      <div className={styles.layout}>

        {/* Sidebar desktop — cachée sur mobile */}
        <div className={styles.sidebarDesktop}>
          <FiltresSidebar
            filtres={filtres}
            onChange={handleFiltresChange}
            onReset={handleReset}
          />
        </div>

        {/* Contenu */}
        <div>
          <div className={styles.resultsBar}>
            <span className={styles.resultsCount}>
              <strong>{filtered.length}</strong> formation{filtered.length > 1 ? 's' : ''} trouvée{filtered.length > 1 ? 's' : ''}
            </span>
            <select
              className={styles.sortSelect}
              value={filtres.tri}
              onChange={(e) => handleFiltresChange({ ...filtres, tri: e.target.value })}
            >
              <option value="popularite">Trier par : Popularité</option>
              <option value="recent">Trier par : Plus récent</option>
              <option value="vues">Trier par : Vues</option>
            </select>
          </div>

          {hasFilter && (
            <div className={styles.activeTags}>
              <span className={styles.tagLabel}>Filtres :</span>
              {!showAllCats && filtres.categories.map(c => (
                <span key={c} className={`${styles.tag} ${styles.tagCat}`}>{c}</span>
              ))}
              {!showAllNiveaux && filtres.niveaux.map(n => (
                <span key={n} className={`${styles.tag} ${styles.tagLevel}`}>{n}</span>
              ))}
              {filtres.search && (
                <span className={`${styles.tag} ${styles.tagSearch}`}>🔍 "{filtres.search}"</span>
              )}
            </div>
          )}

          {loading && (
            <div className={styles.loadingGrid}>
              {[...Array(6)].map((_, i) => <div key={i} className={styles.skeleton} />)}
            </div>
          )}

          {!loading && filtered.length > 0 && (
            <div className={styles.grid}>
              {paginated.map(f => <FormationCard key={f.id} formation={f} />)}
            </div>
          )}

          {!loading && filtered.length === 0 && (
            <div className={styles.emptyState}>
              <div className={styles.emptyEmoji}>🔍</div>
              <div className={styles.emptyTitle}>Aucune formation trouvée</div>
              <div className={styles.emptyDesc}>Essaie de modifier tes filtres.</div>
            </div>
          )}

          {!loading && totalPages > 1 && (
            <div className={styles.pagination}>
              <button className={styles.pageBtn} onClick={() => setPage(p => p - 1)} disabled={page === 1}>←</button>
              {[...Array(totalPages)].map((_, i) => (
                <button
                  key={i + 1}
                  className={`${styles.pageBtn} ${page === i + 1 ? styles.pageBtnActive : ''}`}
                  onClick={() => setPage(i + 1)}
                >
                  {i + 1}
                </button>
              ))}
              <button className={styles.pageBtn} onClick={() => setPage(p => p + 1)} disabled={page === totalPages}>→</button>
            </div>
          )}
        </div>
      </div>

      {/* Bouton filtres flottant — mobile uniquement */}
      <button className={styles.btnFiltresMobile} onClick={() => setDrawerOpen(true)}>
        🎛️ Filtres {hasFilter && `(actifs)`}
      </button>

      {/* Drawer filtres mobile */}
      {drawerOpen && (
        <div className={styles.filtresOverlayOpen} onClick={() => setDrawerOpen(false)}>
          <div className={styles.filtresDrawer} onClick={e => e.stopPropagation()}>
            <div className={styles.filtresDrawerHeader}>
              <span className={styles.filtresDrawerTitle}>🎛️ Filtres</span>
              <button className={styles.filtresDrawerClose} onClick={() => setDrawerOpen(false)}>×</button>
            </div>
            <FiltresSidebar
              filtres={filtres}
              onChange={handleFiltresChange}
              onReset={handleReset}
            />
            <button className={styles.btnAppliquer} onClick={() => setDrawerOpen(false)}>
              Voir les {filtered.length} formations
            </button>
          </div>
        </div>
      )}
    </div>
  );
}