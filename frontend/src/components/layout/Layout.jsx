import React, { useState } from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import Sidebar from './Sidebar';
import Navbar from './Navbar';
import { useCompany } from '../../context/CompanyContext';
import { useAuth } from '../../context/AuthContext';

export default function Layout() {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { activeEmpresa } = useCompany();
  const { esPlataforma } = useAuth();
  const location = useLocation();

  // La cuenta de plataforma solo usa el panel de cuentas de clientes
  if (esPlataforma && location.pathname !== '/plataforma') {
    return <Navigate to="/plataforma" replace />;
  }

  return (
    <div className="flex h-screen overflow-hidden bg-slate-100/70 dark:bg-slate-950 text-slate-900 dark:text-slate-100 antialiased selection:bg-indigo-500 selection:text-white">
      {/* Sidebar Navigation */}
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      {/* Main Content Area */}
      <div className="flex flex-1 flex-col overflow-hidden">
        <Navbar onToggleSidebar={() => setSidebarOpen(!sidebarOpen)} />
        <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
          {/* key: al cambiar de empresa se vuelve a montar la página y recarga sus datos */}
          <div key={activeEmpresa?.id_empresa ?? 'sin-empresa'} className="mx-auto max-w-7xl">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
