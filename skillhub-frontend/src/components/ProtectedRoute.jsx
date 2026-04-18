import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function ProtectedRoute({ children, role }) {
  const { isAuthenticated, user, loading } = useAuth();

  if (loading) return null; 

  // 1. Si pas connecté -> Accueil
  // if (!isAuthenticated) {
  //   return <Navigate to="/" replace />;
  // }

  // 2. Si un rôle est requis mais que l'utilisateur n'a pas le bon
  // On vérifie directement dans user.role (qui vient de ton Java)
  //if (role && user?.role !== role) {
  //   console.warn(`Accès refusé: requis ${role}, actuel ${user?.role}`);
  //   return <Navigate to="/" replace />;  Retour à l'accueil pour casser la boucle
  // }

  // Remplace ta condition de rôle par celle-ci temporairement
if (role && user && !user.role) {
    console.error("ATTENTION : L'utilisateur n'a pas de rôle stocké !");
  }

  return children;
}