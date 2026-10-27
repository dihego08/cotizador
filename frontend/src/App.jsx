import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import { ThemeProvider } from './context/ThemeContext';
import { CompanyProvider } from './context/CompanyContext';
import Layout from './components/layout/Layout';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import ProductosPage from './pages/ProductosPage';
import CategoriasMarcasPage from './pages/CategoriasMarcasPage';
import StockPage from './pages/StockPage';
import MovimientosPage from './pages/MovimientosPage';
import SeriesPage from './pages/SeriesPage';
import CotizacionesPage from './pages/CotizacionesPage';
import VentasPage from './pages/VentasPage';
import CrmPage from './pages/CrmPage';
import HelpdeskPage from './pages/HelpdeskPage';
import ClientesPage from './pages/ClientesPage';
import AlmacenesPage from './pages/AlmacenesPage';
import EmpresasPage from './pages/EmpresasPage';
import UsuariosPage from './pages/UsuariosPage';
import TipoCambioPage from './pages/TipoCambioPage';

function ProtectedRoute({ children }) {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return (
      <div className="flex h-screen items-center justify-center bg-slate-900">
        <div className="flex flex-col items-center gap-3">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent" />
          <span className="text-xs font-semibold text-slate-400">Iniciando sesión...</span>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return children;
}

export default function App() {
  return (
    <ThemeProvider>
      <AuthProvider>
        <CompanyProvider>
          <BrowserRouter>
            <Routes>
              <Route path="/login" element={<LoginPage />} />

              <Route
                path="/"
                element={
                  <ProtectedRoute>
                    <Layout />
                  </ProtectedRoute>
                }
              >
                <Route index element={<DashboardPage />} />
                <Route path="crm" element={<CrmPage />} />
                <Route path="cotizaciones" element={<CotizacionesPage />} />
                <Route path="ventas" element={<VentasPage />} />
                <Route path="clientes" element={<ClientesPage />} />
                <Route path="productos" element={<ProductosPage />} />
                <Route path="categorias-marcas" element={<CategoriasMarcasPage />} />
                <Route path="stock" element={<StockPage />} />
                <Route path="movimientos" element={<MovimientosPage />} />
                <Route path="series" element={<SeriesPage />} />
                <Route path="helpdesk" element={<HelpdeskPage />} />
                <Route path="almacenes" element={<AlmacenesPage />} />
                <Route path="empresas" element={<EmpresasPage />} />
                <Route path="usuarios" element={<UsuariosPage />} />
                <Route path="tipo-cambio" element={<TipoCambioPage />} />
              </Route>

              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </BrowserRouter>
        </CompanyProvider>
      </AuthProvider>
    </ThemeProvider>
  );
}
