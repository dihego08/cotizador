import React, { useState, useEffect } from 'react';
import { Search, ChevronLeft, ChevronRight } from 'lucide-react';
import api from '../api/client';

const ENTIDADES = [
  ['', 'Todas las entidades'],
  ['usuario', 'Usuarios y accesos'],
  ['empresa', 'Empresas'],
  ['venta', 'Ventas y cobranzas'],
  ['cotizacion', 'Cotizaciones'],
  ['movimiento', 'Movimientos de stock'],
  ['producto', 'Productos'],
  ['cliente', 'Clientes'],
  ['almacen', 'Almacenes'],
  ['cuenta', 'Cuentas (plataforma)'],
];

// Acciones que conviene resaltar al revisar el registro
const ACCIONES_CRITICAS = ['LOGIN_FALLIDO', 'ELIMINAR', 'ANULAR', 'DESACTIVAR', 'SUSPENDER', 'AJUSTE'];

export default function AuditoriaPage() {
  const [registros, setRegistros] = useState([]);
  const [paginacion, setPaginacion] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [entidad, setEntidad] = useState('');
  const [pagina, setPagina] = useState(1);

  const fetchRegistros = async () => {
    setLoading(true);
    try {
      const res = await api.get('/auditoria', {
        params: { search: search || undefined, entidad: entidad || undefined, page: pagina },
      });
      const data = res.data?.data;
      setRegistros(data?.data || []);
      setPaginacion({ current_page: data?.current_page || 1, last_page: data?.last_page || 1, total: data?.total || 0 });
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const timer = setTimeout(fetchRegistros, 300);
    return () => clearTimeout(timer);
  }, [search, entidad, pagina]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Registro de Actividad</h1>
        <p className="text-xs text-slate-500 dark:text-slate-400">
          Quién hizo qué en las operaciones sensibles: accesos, usuarios, empresas, ventas, anulaciones y stock.
        </p>
      </div>

      <div className="flex flex-col gap-3 sm:flex-row">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPagina(1);
            }}
            placeholder="Buscar en la descripción..."
            className="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
          />
        </div>
        <select
          value={entidad}
          onChange={(e) => {
            setEntidad(e.target.value);
            setPagina(1);
          }}
          className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
        >
          {ENTIDADES.map(([valor, texto]) => (
            <option key={valor} value={valor}>{texto}</option>
          ))}
        </select>
      </div>

      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-100 bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
              <tr>
                <th className="py-3 px-4">Fecha</th>
                <th className="py-3 px-4">Usuario</th>
                <th className="py-3 px-4">Acción</th>
                <th className="py-3 px-4">Descripción</th>
                <th className="py-3 px-4">IP</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan={5} className="py-12 text-center text-slate-400">Cargando registro...</td>
                </tr>
              ) : registros.length === 0 ? (
                <tr>
                  <td colSpan={5} className="py-12 text-center text-slate-400">No hay actividad registrada.</td>
                </tr>
              ) : (
                registros.map((r) => (
                  <tr key={r.id_auditoria} className="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                    <td className="py-2.5 px-4 whitespace-nowrap font-mono text-slate-500 dark:text-slate-400">
                      {new Date(r.created_at).toLocaleString('es-PE')}
                    </td>
                    <td className="py-2.5 px-4 text-slate-700 dark:text-slate-200">
                      {r.usuario?.nombre_usuario || <span className="text-slate-400">Usuario eliminado</span>}
                    </td>
                    <td className="py-2.5 px-4">
                      <span
                        className={`rounded-md px-2 py-0.5 text-[10px] font-bold ${
                          ACCIONES_CRITICAS.includes(r.accion)
                            ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                            : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                        }`}
                      >
                        {r.accion}
                      </span>
                    </td>
                    <td className="py-2.5 px-4 text-slate-600 dark:text-slate-300">{r.descripcion}</td>
                    <td className="py-2.5 px-4 font-mono text-slate-400">{r.ip || '-'}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
          <span>{paginacion.total} registro(s)</span>
          <div className="flex items-center gap-2">
            <button
              disabled={paginacion.current_page <= 1}
              onClick={() => setPagina(pagina - 1)}
              className="rounded-lg p-1 hover:bg-slate-100 disabled:opacity-40 dark:hover:bg-slate-800"
            >
              <ChevronLeft className="h-4 w-4" />
            </button>
            <span>
              Página {paginacion.current_page} de {paginacion.last_page}
            </span>
            <button
              disabled={paginacion.current_page >= paginacion.last_page}
              onClick={() => setPagina(pagina + 1)}
              className="rounded-lg p-1 hover:bg-slate-100 disabled:opacity-40 dark:hover:bg-slate-800"
            >
              <ChevronRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
