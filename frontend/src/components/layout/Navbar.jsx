import React, { useState, useEffect } from 'react';
import {
  Menu,
  Sun,
  Moon,
  LogOut,
  Building,
  TrendingUp,
  User,
  Shield,
  ChevronDown,
} from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { useTheme } from '../../context/ThemeContext';
import { useCompany } from '../../context/CompanyContext';
import api from '../../api/client';

export default function Navbar({ onToggleSidebar }) {
  const { user, logout } = useAuth();
  const { theme, toggleTheme } = useTheme();
  const { empresas, activeEmpresa, changeEmpresa } = useCompany();
  const [tipoCambio, setTipoCambio] = useState(null);
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const [showCompanyMenu, setShowCompanyMenu] = useState(false);

  useEffect(() => {
    const fetchTC = async () => {
      try {
        const res = await api.get('/tipo-cambio/actual');
        if (res.data?.data) {
          setTipoCambio(res.data.data);
        }
      } catch (e) {
        // Fallback silencioso
      }
    };
    fetchTC();
  }, []);

  return (
    <header className="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-slate-200 bg-white/80 px-4 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/80 sm:px-6">
      {/* Left side: Hamburger & Active Company */}
      <div className="flex items-center gap-3">
        <button
          onClick={onToggleSidebar}
          className="flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 md:hidden"
        >
          <Menu className="h-5 w-5" />
        </button>

        {/* Company Switcher Dropdown */}
        <div className="relative">
          <button
            onClick={() => setShowCompanyMenu(!showCompanyMenu)}
            className="flex items-center gap-2 rounded-xl bg-slate-100/90 hover:bg-slate-200/80 px-3 py-1.5 dark:bg-slate-800/90 dark:hover:bg-slate-700/80 border border-slate-200/70 dark:border-slate-700/70 transition-colors text-left"
          >
            <Building className="h-4 w-4 text-indigo-600 dark:text-indigo-400 shrink-0" />
            <div className="flex flex-col min-w-0">
              <span className="text-xs font-bold text-slate-800 dark:text-slate-100 truncate max-w-[140px] sm:max-w-[210px]">
                {activeEmpresa?.razon_social || user?.empresa?.razon_social || 'Seleccionar Empresa'}
              </span>
              <span className="text-[10px] text-slate-500 dark:text-slate-400">
                RUC: {activeEmpresa?.ruc || user?.empresa?.ruc || 'Sin RUC'}
              </span>
            </div>
            <ChevronDown className="h-3.5 w-3.5 text-slate-400 ml-1" />
          </button>

          {showCompanyMenu && (
            <>
              <div
                className="fixed inset-0 z-40"
                onClick={() => setShowCompanyMenu(false)}
              />
              <div className="absolute left-0 z-50 mt-2 w-72 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <p className="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                  Cambiar Empresa Activa
                </p>
                <div className="space-y-1">
                  {empresas.map((emp) => (
                    <button
                      key={emp.id_empresa}
                      onClick={() => {
                        changeEmpresa(emp);
                        setShowCompanyMenu(false);
                      }}
                      className={`flex w-full items-start gap-2.5 rounded-xl px-3 py-2 text-left text-xs transition-colors ${
                        (activeEmpresa?.id_empresa === emp.id_empresa)
                          ? 'bg-indigo-50 text-indigo-700 font-bold dark:bg-indigo-950/50 dark:text-indigo-300'
                          : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                      }`}
                    >
                      <Building className="h-4 w-4 mt-0.5 text-slate-400 shrink-0" />
                      <div className="flex flex-col min-w-0">
                        <span className="truncate">{emp.razon_social}</span>
                        <span className="text-[10px] text-slate-400 font-normal">
                          RUC: {emp.ruc}
                        </span>
                      </div>
                    </button>
                  ))}
                </div>
              </div>
            </>
          )}
        </div>
      </div>

      {/* Right side: TC, Theme toggle, Profile */}
      <div className="flex items-center gap-2 sm:gap-4">
        {/* Tipo de Cambio Pill */}
        {tipoCambio && (
          <div className="hidden sm:flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40">
            <TrendingUp className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
            <span>USD TC:</span>
            <span className="font-bold">C: {Number(tipoCambio.compra).toFixed(3)}</span>
            <span className="font-bold text-emerald-600 dark:text-emerald-400">|</span>
            <span className="font-bold">V: {Number(tipoCambio.venta).toFixed(3)}</span>
          </div>
        )}

        {/* Theme Switcher */}
        <button
          onClick={toggleTheme}
          aria-label="Alternar tema"
          className="flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
        >
          {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
        </button>

        {/* User Dropdown */}
        <div className="relative">
          <button
            onClick={() => setShowProfileMenu(!showProfileMenu)}
            className="flex items-center gap-2.5 rounded-xl p-1 text-left transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-500 to-indigo-700 text-white font-semibold text-sm shadow-xs">
              {user?.nombre_usuario ? user.nombre_usuario.charAt(0).toUpperCase() : 'A'}
            </div>
            <div className="hidden text-left md:block">
              <p className="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-tight">
                {user?.nombre_usuario || 'Admin'}
              </p>
              <span className="inline-block text-[10px] font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                {user?.rol || 'Administrador'}
              </span>
            </div>
          </button>

          {showProfileMenu && (
            <>
              <div
                className="fixed inset-0 z-40"
                onClick={() => setShowProfileMenu(false)}
              />
              <div className="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <div className="px-3 py-2 border-b border-slate-100 dark:border-slate-800 mb-1">
                  <p className="text-xs font-bold text-slate-900 dark:text-white truncate">
                    {user?.nombre_usuario}
                  </p>
                  <p className="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {user?.email}
                  </p>
                </div>

                <div className="px-3 py-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400 flex items-center gap-2">
                  <Shield className="h-3.5 w-3.5 text-indigo-500" />
                  <span>Rol: {user?.rol || 'Admin'}</span>
                </div>

                <button
                  onClick={() => {
                    setShowProfileMenu(false);
                    logout();
                  }}
                  className="mt-1 flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                >
                  <LogOut className="h-4 w-4" />
                  <span>Cerrar Sesión</span>
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    </header>
  );
}
