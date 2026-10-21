import React, { useState, useEffect } from 'react';
import {
  Menu,
  Sun,
  Moon,
  LogOut,
  Building2,
  TrendingUp,
  Shield,
  ChevronDown,
  Check,
  Sparkles,
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

  const userInitial = user?.nombre_usuario
    ? user.nombre_usuario.charAt(0).toUpperCase()
    : 'U';

  return (
    <header className="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/90 sm:px-6">
      {/* Lado Izquierdo: Botón Menú Móvil & Selector de Empresa */}
      <div className="flex items-center gap-3">
        <button
          onClick={onToggleSidebar}
          aria-label="Abrir menú"
          className="flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 md:hidden transition-colors"
        >
          <Menu className="h-5 w-5" />
        </button>

        {/* Selector de Empresa Activa */}
        <div className="relative">
          <button
            onClick={() => setShowCompanyMenu(!showCompanyMenu)}
            className="group flex items-center gap-2.5 rounded-2xl border border-slate-200/80 bg-slate-50/90 px-3 py-1.5 transition-all hover:border-indigo-300 hover:bg-white hover:shadow-xs dark:border-slate-700/80 dark:bg-slate-800/80 dark:hover:border-slate-600 dark:hover:bg-slate-800"
          >
            <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
              <Building2 className="h-4 w-4" />
            </div>
            <div className="flex flex-col text-left">
              <span className="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[150px] sm:max-w-[220px]">
                {activeEmpresa?.razon_social || user?.empresa?.razon_social || 'Seleccionar Empresa'}
              </span>
              <span className="font-mono text-[10px] text-slate-500 dark:text-slate-400">
                RUC: {activeEmpresa?.ruc || user?.empresa?.ruc || 'Sin RUC'}
              </span>
            </div>
            <ChevronDown className="h-3.5 w-3.5 text-slate-400 transition-transform duration-200 group-hover:text-slate-600 dark:group-hover:text-slate-200" />
          </button>

          {showCompanyMenu && (
            <>
              <div
                className="fixed inset-0 z-40"
                onClick={() => setShowCompanyMenu(false)}
              />
              <div className="absolute left-0 z-50 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-800 dark:bg-slate-900 animate-in fade-in zoom-in-95 duration-100">
                <div className="px-3 py-2 border-b border-slate-100 dark:border-slate-800">
                  <p className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Cambiar Empresa de Operación
                  </p>
                </div>
                <div className="mt-1 space-y-1 max-h-60 overflow-y-auto">
                  {empresas.map((emp) => {
                    const isSelected = activeEmpresa?.id_empresa === emp.id_empresa;
                    return (
                      <button
                        key={emp.id_empresa}
                        onClick={() => {
                          changeEmpresa(emp);
                          setShowCompanyMenu(false);
                        }}
                        className={`flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-xs transition-colors ${
                          isSelected
                            ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/50 dark:text-indigo-300'
                            : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                        }`}
                      >
                        <div className="flex items-center gap-2.5 min-w-0">
                          <Building2 className={`h-4 w-4 shrink-0 ${isSelected ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400'}`} />
                          <div className="flex flex-col min-w-0">
                            <span className="truncate font-semibold">{emp.razon_social}</span>
                            <span className="font-mono text-[10px] text-slate-400 font-normal">
                              RUC: {emp.ruc}
                            </span>
                          </div>
                        </div>
                        {isSelected && (
                          <Check className="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400" />
                        )}
                      </button>
                    );
                  })}
                </div>
              </div>
            </>
          )}
        </div>
      </div>

      {/* Lado Derecho: Tipo de Cambio, Switcher de Tema, Perfil */}
      <div className="flex items-center gap-2 sm:gap-3">
        {/* Tipo de Cambio con Monospace y diseño financiero */}
        {tipoCambio && (
          <div className="hidden sm:flex items-center gap-2 rounded-xl border border-emerald-200/80 bg-emerald-50/80 px-3 py-1.5 text-xs text-emerald-900 shadow-2xs dark:border-emerald-800/50 dark:bg-emerald-950/30 dark:text-emerald-300">
            <div className="flex items-center gap-1.5 font-semibold text-emerald-700 dark:text-emerald-400">
              <TrendingUp className="h-3.5 w-3.5" />
              <span className="text-[11px] font-bold">USD TC</span>
            </div>
            <div className="flex items-center gap-2 font-mono text-[11px]">
              <span>
                C: <strong>{Number(tipoCambio.compra).toFixed(3)}</strong>
              </span>
              <span className="text-emerald-300 dark:text-emerald-700">|</span>
              <span>
                V: <strong>{Number(tipoCambio.venta).toFixed(3)}</strong>
              </span>
            </div>
          </div>
        )}

        {/* Botón Alternar Tema Oscuro / Claro */}
        <button
          onClick={toggleTheme}
          aria-label="Alternar tema"
          title={theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'}
          className="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-500 shadow-2xs transition-colors hover:bg-slate-100 hover:text-slate-800 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
        >
          {theme === 'dark' ? (
            <Sun className="h-4 w-4 text-amber-400" />
          ) : (
            <Moon className="h-4 w-4 text-slate-600" />
          )}
        </button>

        {/* Dropdown de Usuario con Avatar Elevado */}
        <div className="relative">
          <button
            onClick={() => setShowProfileMenu(!showProfileMenu)}
            className="flex items-center gap-2.5 rounded-2xl border border-slate-200/80 bg-white p-1 pr-2.5 shadow-2xs transition-all hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800"
          >
            <div className="relative">
              <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-xs font-bold text-white shadow-xs">
                {userInitial}
              </div>
              <span className="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-slate-900" />
            </div>

            <div className="hidden text-left sm:block">
              <p className="text-xs font-bold leading-tight text-slate-900 dark:text-white">
                {user?.nombre_usuario || 'Diego Aranibar'}
              </p>
              <span className="inline-block text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                {user?.rol || 'ADMIN'}
              </span>
            </div>
            <ChevronDown className="h-3 w-3 text-slate-400 hidden sm:block" />
          </button>

          {showProfileMenu && (
            <>
              <div
                className="fixed inset-0 z-40"
                onClick={() => setShowProfileMenu(false)}
              />
              <div className="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-800 dark:bg-slate-900 animate-in fade-in zoom-in-95 duration-100">
                <div className="px-3 py-2 border-b border-slate-100 dark:border-slate-800 mb-1">
                  <p className="text-xs font-bold text-slate-900 dark:text-white truncate">
                    {user?.nombre_usuario || 'Diego Aranibar'}
                  </p>
                  <p className="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {user?.email || 'admin@incatops.com'}
                  </p>
                </div>

                <div className="px-3 py-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400 flex items-center gap-2">
                  <Shield className="h-3.5 w-3.5 text-indigo-500" />
                  <span>Rol: <strong className="text-slate-700 dark:text-slate-300 capitalize">{user?.rol || 'Administrador'}</strong></span>
                </div>

                <div className="pt-1 mt-1 border-t border-slate-100 dark:border-slate-800">
                  <button
                    onClick={() => {
                      setShowProfileMenu(false);
                      logout();
                    }}
                    className="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                  >
                    <LogOut className="h-4 w-4" />
                    <span>Cerrar Sesión</span>
                  </button>
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </header>
  );
}
