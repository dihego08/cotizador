import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  Boxes,
  AlertTriangle,
  FileCheck2,
  TrendingUp,
  Headset,
  PlusCircle,
  ArrowRight,
  ArrowUpRight,
  ArrowDownLeft,
  ArrowLeftRight,
  Clock,
  CheckCircle,
} from 'lucide-react';
import api from '../api/client';

export default function DashboardPage() {
  const [metrics, setMetrics] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchMetrics = async () => {
      try {
        const res = await api.get('/dashboard/metrics');
        if (res.data?.data) {
          setMetrics(res.data.data);
        }
      } catch (err) {
        console.error('Error fetching metrics:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchMetrics();
  }, []);

  if (loading) {
    return (
      <div className="flex h-72 items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent" />
      </div>
    );
  }

  const statCards = [
    {
      title: 'Total de Productos',
      value: metrics?.total_productos ?? 0,
      icon: Boxes,
      color: 'from-blue-600 to-cyan-500',
      bgColor: 'bg-blue-50 dark:bg-blue-950/30',
      textColor: 'text-blue-600 dark:text-blue-400',
      link: '/productos',
    },
    {
      title: 'Alerta Stock Bajo',
      value: metrics?.stock_bajo_alerta ?? 0,
      icon: AlertTriangle,
      color: 'from-amber-500 to-orange-500',
      bgColor: 'bg-amber-50 dark:bg-amber-950/30',
      textColor: 'text-amber-600 dark:text-amber-400',
      badge: (metrics?.stock_bajo_alerta ?? 0) > 0 ? 'Requiere Atención' : 'Stock Óptimo',
      link: '/stock?solo_bajo_stock=true',
    },
    {
      title: 'Cotizaciones Pendientes',
      value: metrics?.cotizaciones_pendientes ?? 0,
      icon: Clock,
      color: 'from-violet-600 to-purple-500',
      bgColor: 'bg-violet-50 dark:bg-violet-950/30',
      textColor: 'text-violet-600 dark:text-violet-400',
      link: '/cotizaciones',
    },
    {
      title: 'Ventas Aprobadas (Mes)',
      value: `$ ${Number(metrics?.total_ventas_mes ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}`,
      icon: TrendingUp,
      color: 'from-emerald-600 to-teal-500',
      bgColor: 'bg-emerald-50 dark:bg-emerald-950/30',
      textColor: 'text-emerald-600 dark:text-emerald-400',
      link: '/cotizaciones',
    },
    {
      title: 'Tickets Helpdesk Activos',
      value: metrics?.tickets_abiertos ?? 0,
      icon: Headset,
      color: 'from-indigo-600 to-violet-500',
      bgColor: 'bg-indigo-50 dark:bg-indigo-950/30',
      textColor: 'text-indigo-600 dark:text-indigo-400',
      link: '/helpdesk',
    },
  ];

  return (
    <div className="space-y-8">
      {/* Welcome Banner */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Panel de Control General
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Resumen en tiempo real del estado de almacenes, ventas y tickets de soporte.
          </p>
        </div>

        {/* Action Shortcuts */}
        <div className="flex flex-wrap items-center gap-2.5">
          <Link
            to="/cotizaciones"
            className="flex items-center gap-2 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
          >
            <PlusCircle className="h-4 w-4" />
            <span>Nueva Cotización</span>
          </Link>
          <Link
            to="/movimientos"
            className="flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors"
          >
            <ArrowLeftRight className="h-4 w-4 text-indigo-500" />
            <span>Movimiento de Stock</span>
          </Link>
          <Link
            to="/helpdesk"
            className="flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors"
          >
            <Headset className="h-4 w-4 text-indigo-500" />
            <span>Ticket Helpdesk</span>
          </Link>
        </div>
      </div>

      {/* KPI Cards Grid */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        {statCards.map((card) => {
          const Icon = card.icon;
          return (
            <Link
              key={card.title}
              to={card.link}
              className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition-all hover:border-slate-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            >
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">
                  {card.title}
                </span>
                <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${card.bgColor} ${card.textColor}`}>
                  <Icon className="h-5 w-5" />
                </div>
              </div>

              <div className="mt-4">
                <span className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                  {card.value}
                </span>
                {card.badge && (
                  <span className="mt-1 block text-[11px] font-medium text-amber-600 dark:text-amber-400">
                    {card.badge}
                  </span>
                )}
              </div>

              <div className="mt-4 flex items-center gap-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 group-hover:translate-x-0.5 transition-transform">
                <span>Ver detalles</span>
                <ArrowRight className="h-3.5 w-3.5" />
              </div>
            </Link>
          );
        })}
      </div>

      {/* Two Columns Grid for Recent Activity */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Recent Quotations */}
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="mb-4 flex items-center justify-between">
            <h2 className="text-base font-bold text-slate-900 dark:text-white">
              Cotizaciones Recientes
            </h2>
            <Link
              to="/cotizaciones"
              className="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
            >
              Ver todas
            </Link>
          </div>

          <div className="divide-y divide-slate-100 dark:divide-slate-800">
            {metrics?.ultimas_cotizaciones?.length > 0 ? (
              metrics.ultimas_cotizaciones.map((cotiz) => (
                <div key={cotiz.id_cotizacion} className="flex items-center justify-between py-3">
                  <div className="flex flex-col">
                    <span className="text-xs font-bold text-slate-800 dark:text-slate-200">
                      {cotiz.numero_cotizacion}
                    </span>
                    <span className="text-xs text-slate-500 dark:text-slate-400">
                      {cotiz.cliente?.razon_social || 'Cliente General'}
                    </span>
                    <span className="text-[10px] text-slate-400">
                      {cotiz.fecha_emision}
                    </span>
                  </div>

                  <div className="flex flex-col items-end gap-1">
                    <span className="text-xs font-bold text-slate-900 dark:text-white">
                      {cotiz.moneda} {Number(cotiz.total).toFixed(2)}
                    </span>
                    <span
                      className={`inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold ${
                        cotiz.estado === 'APROBADA'
                          ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                          : cotiz.estado === 'RECHAZADA'
                          ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                          : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
                      }`}
                    >
                      {cotiz.estado}
                    </span>
                  </div>
                </div>
              ))
            ) : (
              <p className="py-6 text-center text-xs text-slate-400">
                No hay cotizaciones registradas recientemente.
              </p>
            )}
          </div>
        </div>

        {/* Recent Movements */}
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="mb-4 flex items-center justify-between">
            <h2 className="text-base font-bold text-slate-900 dark:text-white">
              Kardex: Últimos Movimientos
            </h2>
            <Link
              to="/movimientos"
              className="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
            >
              Ver todos
            </Link>
          </div>

          <div className="divide-y divide-slate-100 dark:divide-slate-800">
            {metrics?.ultimos_movimientos?.length > 0 ? (
              metrics.ultimos_movimientos.map((mov) => {
                const isEntrada = mov.tipo_movimiento === 'ENTRADA';
                const isSalida = mov.tipo_movimiento === 'SALIDA';

                return (
                  <div key={mov.id_movimiento} className="flex items-center justify-between py-3">
                    <div className="flex items-center gap-3">
                      <div
                        className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${
                          isEntrada
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'
                            : isSalida
                            ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400'
                            : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400'
                        }`}
                      >
                        {isEntrada ? (
                          <ArrowDownLeft className="h-4 w-4" />
                        ) : isSalida ? (
                          <ArrowUpRight className="h-4 w-4" />
                        ) : (
                          <ArrowLeftRight className="h-4 w-4" />
                        )}
                      </div>
                      <div className="flex flex-col">
                        <span className="text-xs font-bold text-slate-800 dark:text-slate-200">
                          {mov.producto?.nombre_producto || 'Producto'}
                        </span>
                        <span className="text-[11px] text-slate-500 dark:text-slate-400">
                          Almacén: {mov.almacen?.nombre_almacen || 'Principal'} • Por: {mov.usuario?.nombre_usuario || 'Sistema'}
                        </span>
                      </div>
                    </div>

                    <div className="flex flex-col items-end">
                      <span
                        className={`text-xs font-bold ${
                          isEntrada
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : isSalida
                            ? 'text-rose-600 dark:text-rose-400'
                            : 'text-indigo-600 dark:text-indigo-400'
                        }`}
                      >
                        {isEntrada ? '+' : isSalida ? '-' : ''}
                        {mov.cantidad} {mov.producto?.unidad_medida || 'UND'}
                      </span>
                      <span className="text-[10px] text-slate-400 uppercase font-medium">
                        {mov.tipo_movimiento}
                      </span>
                    </div>
                  </div>
                );
              })
            ) : (
              <p className="py-6 text-center text-xs text-slate-400">
                No hay movimientos registrados recientemente.
              </p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
