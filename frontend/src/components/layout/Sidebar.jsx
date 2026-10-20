import React from 'react';
import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  FileText,
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
} from 'lucide-react';
import { useAuth } from '../../context/AuthContext';

export default function Sidebar({ isOpen, onClose }) {
  const { user, hasPermission } = useAuth();

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
        { name: 'Cotizaciones', path: '/cotizaciones', icon: FileText, permission: 'cotizaciones' },
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
          className="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs md:hidden"
          onClick={onClose}
        />
      )}

      <aside
        className={`fixed top-0 bottom-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-300 dark:border-slate-800 dark:bg-slate-900 md:static md:translate-x-0 ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {/* Brand Header */}
        <div className="flex h-16 items-center gap-3 px-6 border-b border-slate-200 dark:border-slate-800">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white shadow-md shadow-indigo-500/20">
            <Package className="h-6 w-6" />
          </div>
          <div className="flex flex-col">
            <span className="font-bold text-base tracking-tight text-slate-900 dark:text-white">
              LogiStore <span className="text-indigo-600 dark:text-indigo-400">ERP</span>
            </span>
            <span className="text-xs text-slate-500 dark:text-slate-400 font-medium">
              Gestión de Almacén
            </span>
          </div>
        </div>

        {/* Navigation */}
        <div className="flex-1 overflow-y-auto px-4 py-4 space-y-6">
          {navigationGroups.map((group) => {
            const visibleItems = group.items.filter(
              (item) => !item.permission || hasPermission(item.permission)
            );

            if (visibleItems.length === 0) return null;

            return (
              <div key={group.title} className="space-y-1">
                <p className="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
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
                        `group flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition-all ${
                          isActive
                            ? 'bg-indigo-50 text-indigo-700 shadow-xs dark:bg-indigo-500/10 dark:text-indigo-400'
                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200'
                        }`
                      }
                    >
                      <div className="flex items-center gap-3">
                        <Icon className="h-5 w-5 shrink-0 transition-transform duration-200 group-hover:scale-110" />
                        <span>{item.name}</span>
                      </div>
                      <ChevronRight className="h-4 w-4 opacity-0 transition-opacity group-hover:opacity-100 text-slate-400" />
                    </NavLink>
                  );
                })}
              </div>
            );
          })}
        </div>

        {/* User Card at Bottom */}
        <div className="p-4 border-t border-slate-200 dark:border-slate-800">
          <div className="flex items-center gap-3 rounded-xl bg-slate-50 p-2.5 dark:bg-slate-800/60">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 font-semibold text-sm dark:bg-indigo-900/60 dark:text-indigo-300">
              {user?.nombre_usuario ? user.nombre_usuario.charAt(0).toUpperCase() : 'U'}
            </div>
            <div className="flex flex-col min-w-0 flex-1">
              <span className="truncate text-xs font-semibold text-slate-900 dark:text-white">
                {user?.nombre_usuario || 'Usuario'}
              </span>
              <span className="truncate text-[11px] text-slate-500 dark:text-slate-400 capitalize">
                {user?.cargo || user?.rol || 'Operador'}
              </span>
            </div>
          </div>
        </div>
      </aside>
    </>
  );
}
