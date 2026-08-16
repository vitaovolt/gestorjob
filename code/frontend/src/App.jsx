import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import ProtectedRoute from './components/layout/ProtectedRoute.jsx'
import SuperAdminRoute from './components/layout/SuperAdminRoute.jsx'
import ConfigRoute from './components/layout/ConfigRoute.jsx'
import { AuthProvider, useAuth } from './context/AuthContext.jsx'
import { ToastProvider } from './context/ToastContext.jsx'
import { ehSuperAdmin } from './utils/format'
import ClienteFormPage from './pages/ClienteFormPage.jsx'
import ClientesPage from './pages/ClientesPage.jsx'
import ColaboradorFormPage from './pages/ColaboradorFormPage.jsx'
import ColaboradoresPage from './pages/ColaboradoresPage.jsx'
import ConfigPage from './pages/ConfigPage.jsx'
import ConvitePage from './pages/ConvitePage.jsx'
import EmpresaDetalhePage from './pages/EmpresaDetalhePage.jsx'
import EmpresaFormPage from './pages/EmpresaFormPage.jsx'
import EmpresasPage from './pages/EmpresasPage.jsx'
import KanbanPage from './pages/KanbanPage.jsx'
import ListaPage from './pages/ListaPage.jsx'
import LoginPage from './pages/LoginPage.jsx'
import PerfilPage from './pages/PerfilPage.jsx'
import PermissoesPage from './pages/PermissoesPage.jsx'
import RecuperarSenhaOkPage from './pages/RecuperarSenhaOkPage.jsx'
import RecuperarSenhaPage from './pages/RecuperarSenhaPage.jsx'
import RedefinirSenhaPage from './pages/RedefinirSenhaPage.jsx'
import ServicoFormPage from './pages/ServicoFormPage.jsx'
import ServicosPage from './pages/ServicosPage.jsx'
import WizardPage from './pages/WizardPage.jsx'

function Privado({ children, permitirWizard = false }) {
  return <ProtectedRoute permitirWizard={permitirWizard}>{children}</ProtectedRoute>
}

function Inicio() {
  const { user } = useAuth()
  if (ehSuperAdmin(user)) {
    return <Navigate to="/empresas" replace />
  }
  return <KanbanPage />
}

export default function App() {
  return (
    <AuthProvider>
      <ToastProvider>
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/convite" element={<ConvitePage />} />
            <Route path="/recuperar" element={<RecuperarSenhaPage />} />
            <Route path="/recuperar-ok" element={<RecuperarSenhaOkPage />} />
            <Route path="/redefinir-senha" element={<RedefinirSenhaPage />} />
            <Route path="/wizard" element={<Privado permitirWizard><WizardPage /></Privado>} />
            <Route path="/" element={<Privado><Inicio /></Privado>} />
            <Route path="/perfil" element={<Privado><PerfilPage /></Privado>} />
            <Route path="/lista" element={<Privado><ListaPage /></Privado>} />
            <Route path="/clientes" element={<Privado><ClientesPage /></Privado>} />
            <Route path="/clientes/novo" element={<Privado><ClienteFormPage /></Privado>} />
            <Route path="/clientes/:id" element={<Privado><ClienteFormPage /></Privado>} />
            <Route path="/servicos" element={<Privado><ServicosPage /></Privado>} />
            <Route path="/servicos/novo" element={<Privado><ServicoFormPage /></Privado>} />
            <Route path="/servicos/:id" element={<Privado><ServicoFormPage /></Privado>} />
            <Route path="/colaboradores" element={<Privado><ColaboradoresPage /></Privado>} />
            <Route path="/colaboradores/novo" element={<Privado><ColaboradorFormPage /></Privado>} />
            <Route path="/colaboradores/:id" element={<Privado><ColaboradorFormPage /></Privado>} />
            <Route path="/config" element={<ConfigRoute><ConfigPage /></ConfigRoute>} />
            <Route path="/permissoes" element={<ConfigRoute><PermissoesPage /></ConfigRoute>} />
            <Route path="/empresas" element={<SuperAdminRoute><EmpresasPage /></SuperAdminRoute>} />
            <Route path="/empresas/novo" element={<SuperAdminRoute><EmpresaFormPage /></SuperAdminRoute>} />
            <Route path="/empresas/:id" element={<SuperAdminRoute><EmpresaDetalhePage /></SuperAdminRoute>} />
          </Routes>
        </BrowserRouter>
      </ToastProvider>
    </AuthProvider>
  )
}
