import React from 'react';
import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  Target,
  FileText,
  Receipt,
  Users,
  Headset,
  Boxes,
  Layers,
  ArrowLeftRight,
  Barcode,
  DollarSign,
  Warehouse,
  Building2,
  ShieldCheck,
  ChevronRight,
  Package,
  Activity,
} from 'lucide-react';
import { useAuth } from '../../context/AuthContext';

export default function Sidebar({ isOpen, onClose }) {
  const { hasPermission } = useAuth();

  const navigationGroups = [
    {
      title: 'PRINCIPAL',
      items: [
        { name: 'Dashboard', path: '/', icon: LayoutDashboard, permission: null },
      ],
    },
    {
      title: 'OPERACIONES',
      items: [
        { name: 'CRM & Pipeline', path: '/crm', icon: Target, permission: 'clientes' },
        { name: 'Cotizaciones', path: '/cotizaciones', icon: FileText, permission: 'cotizaciones' },
        { name: 'Ventas y Cobranzas', path: '/ventas', icon: Receipt, permission: 'cotizaciones' },
        { name: 'Clientes', path: '/clientes', icon: Users, permission: 'clientes' },
        { name: 'Helpdesk Soporte', path: '/helpdesk', icon: Headset, permission: 'helpdesk' },
      ],
    },
    {
      title: 'ALMACÉN E INVENTARIO',
      items: [
        { name: 'Productos y Servicios', path: '/productos', icon: Boxes, permission: 'productos' },
        { name: 'Control de Stock', path: '/stock', icon: Layers, permission: 'stock' },
        { name: 'Movimientos', path: '/movimientos', icon: ArrowLeftRight, permission: 'movimientos' },
        { name: 'Series de Productos', path: '/series', icon: Barcode, permission: 'series' },
      ],
    },
    {
      title: 'FINANZAS',
      items: [
        { name: 'Tipo de Cambio', path: '/tipo-cambio', icon: DollarSign, permission: 'tipo_cambio' },
      ],
    },
    {
      title: 'ADMINISTRACIÓN',
      items: [
        { name: 'Almacenes', path: '/almacenes', icon: Warehouse, permission: 'almacenes' },
        { name: 'Empresas', path: '/empresas', icon: Building2, permission: 'empresas' },
        { name: 'Usuarios y Permisos', path: '/usuarios', icon: ShieldCheck, permission: 'usuarios' },
      ],
    },
  ];

  return (
    <>
      {/* Backdrop for mobile */}
      {isOpen && (
        <div
          className="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs md:hidden"
          onClick={onClose}
        />
      )}

      <aside
        className={`fixed top-0 bottom-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200/90 bg-white transition-all duration-300 dark:border-slate-800 dark:bg-slate-900 md:static md:translate-x-0 ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {/* Brand Header */}
        <div className="flex h-16 items-center justify-between px-5 border-b border-slate-100 dark:border-slate-800">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-700 to-violet-600 text-white shadow-md shadow-indigo-500/25">
              <Package className="h-5 w-5" />
            </div>
            <div className="flex flex-col">
              <div className="flex items-center gap-1.5">
                <span className="font-bold text-base tracking-tight text-slate-900 dark:text-white">
                  LogiStore
                </span>
                <span className="rounded-md bg-indigo-50 px-1.5 py-0.2 text-[10px] font-extrabold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400">
                  ERP
                </span>
              </div>
              <span className="text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                Gestión Empresarial
              </span>
            </div>
          </div>
        </div>

        {/* Navigation Items */}
        <div className="flex-1 overflow-y-auto px-3.5 py-4 space-y-6">
          {navigationGroups.map((group) => {
            const visibleItems = group.items.filter(
              (item) => !item.permission || hasPermission(item.permission)
            );

            if (visibleItems.length === 0) return null;

            return (
              <div key={group.title} className="space-y-1">
                <p className="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                  {group.title}
                </p>
                {visibleItems.map((item) => {
                  const Icon = item.icon;
                  return (
                    <NavLink
                      key={item.path}
                      to={item.path}
                      end={item.path === '/'}
                      onClick={() => onClose && onClose()}
                      className={({ isActive }) =>
                        `group relative flex items-center justify-between rounded-xl px-3 py-2 text-xs font-medium transition-all ${
                          isActive
                            ? 'bg-indigo-50/90 text-indigo-700 font-semibold shadow-xs dark:bg-indigo-500/15 dark:text-indigo-300 before:absolute before:left-0 before:top-1.5 before:bottom-1.5 before:w-1 before:rounded-r-full before:bg-indigo-600 dark:before:bg-indigo-400'
                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200'
                        }`
                      }
                    >
                      {({ isActive }) => (
                        <>
                          <div className="flex items-center gap-3">
                            <Icon
                              className={`h-4 w-4 shrink-0 transition-transform duration-200 group-hover:scale-110 ${
                                isActive
                                  ? 'text-indigo-600 dark:text-indigo-400'
                                  : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300'
                              }`}
                            />
                            <span>{item.name}</span>
                          </div>
                          <ChevronRight
                            className={`h-3.5 w-3.5 transition-all ${
                              isActive
                                ? 'opacity-100 text-indigo-500 dark:text-indigo-400 translate-x-0'
                                : 'opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 text-slate-400'
                            }`}
                          />
                        </>
                      )}
                    </NavLink>
                  );
                })}
              </div>
            );
          })}
        </div>

        {/* Footer: Estado del Sistema en lugar de tarjeta de usuario duplicada */}
        <div className="p-3.5 border-t border-slate-100 dark:border-slate-800">
          <div className="flex items-center justify-between rounded-xl bg-slate-50 p-2.5 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-800">
            <div className="flex items-center gap-2">
              <span className="relative flex h-2 w-2">
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
              </span>
              <div className="flex flex-col">
                <span className="text-[11px] font-semibold text-slate-800 dark:text-slate-200">
                  Sistema Online
                </span>
                <span className="text-[9px] text-slate-400 font-mono">
                  API v2.0 • Cloud
                </span>
              </div>
            </div>
            <div className="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100/70 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
              <Activity className="h-3 w-3" />
            </div>
          </div>
        </div>
      </aside>
    </>
  );
}
