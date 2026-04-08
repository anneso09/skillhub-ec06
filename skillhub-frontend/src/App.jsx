import { useState } from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import Navbar         from './components/Navbar';
import ProtectedRoute from './components/ProtectedRoute';
import LoginModal     from './components/modals/LoginModal';
import RegisterModal  from './components/modals/RegisterModal';

import Home               from './pages/Home';
import Formations         from './pages/Formations';
import FormationDetail    from './pages/FormationDetail';
import DashboardApprenant from './pages/DashboardApprenant';
import DashboardFormateur from './pages/DashboardFormateur';
import SuiviFormation     from './pages/SuiviFormation';

export default function App() {
  const [modal, setModal] = useState(null); // null | 'login' | 'register'

  const openLogin    = () => setModal('login');
  const openRegister = () => setModal('register');
  const closeModal   = () => setModal(null);

  return (
    <AuthProvider>
      <BrowserRouter>
        {/* Navbar reçoit les handlers pour ouvrir les modals */}
        <Navbar onOpenLogin={openLogin} onOpenRegister={openRegister} />

        {/* Modals globales */}
        {modal === 'login' && (
          <LoginModal
            onClose={closeModal}
            onSwitchToRegister={() => setModal('register')}
          />
        )}
        {modal === 'register' && (
          <RegisterModal
            onClose={closeModal}
            onSwitchToLogin={() => setModal('login')}
          />
        )}

        <Routes>
          {/* Home reçoit les handlers pour ouvrir les modals */}
          <Route path="/" element={
            <Home onOpenLogin={openLogin} onOpenRegister={openRegister} />
          } />
          <Route path="/formations"    element={<Formations />} />
          <Route path="/formation/:id" element={<FormationDetail />} />

          <Route path="/dashboard/apprenant" element={
            <ProtectedRoute role="apprenant"><DashboardApprenant /></ProtectedRoute>
          } />
          <Route path="/apprendre/:id" element={
            <ProtectedRoute role="apprenant"><SuiviFormation /></ProtectedRoute>
          } />
          <Route path="/dashboard/formateur" element={
            <ProtectedRoute role="formateur"><DashboardFormateur /></ProtectedRoute>
          } />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}