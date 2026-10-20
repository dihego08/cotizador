import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  Layers,
  Search,
  AlertTriangle,
  CheckCircle,
  Sliders,
  Warehouse,
  X,
  AlertCircle,
} from 'lucide-react';
import api from '../api/client';

export default function StockPage() {
  const [searchParams] = useSearchParams();
  const [stock, setStock] = useState([]);
  const [almacenes, setAlmacenes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [almacenFilter, setAlmacenFilter] = useState('');
  const [soloBajoStock, setSoloBajoStock] = useState(
    searchParams.get('solo_bajo_stock') === 'true'
  );

  // Edit limits modal
  const [editingStock, setEditingStock] = useState(null);
  const [minimo, setMinimo] = useState('');
  const [maximo, setMaximo] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const fetchAlmacenes = async () => {
    try {
      const res = await api.get('/almacenes');
      if (res.data?.data) {
        setAlmacenes(res.data.data);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const fetchStock = async () => {
    setLoading(true);
    try {
      const res = await api.get('/stock', {
        params: {
          search,
          id_almacen: almacenFilter || undefined,
          solo_bajo_stock: soloBajoStock ? 1 : 0,
        },
      });
      if (res.data?.data) {
        setStock(res.data.data.data || res.data.data);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAlmacenes();
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchStock();
    }, 300);
    return () => clearTimeout(timer);
  }, [search, almacenFilter, soloBajoStock]);

  const handleOpenEditLimits = (item) => {
    setEditingStock(item);
    setMinimo(item.stock_minimo);
    setMaximo(item.stock_maximo);
    setError('');
  };

  const handleSaveLimits = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    try {
      await api.put(`/stock/${editingStock.id_stock}/limites`, {
        stock_minimo: minimo,
        stock_maximo: maximo,
      });
      setEditingStock(null);
      fetchStock();
    } catch (err) {
      setError(err.response?.data?.message || 'Error al actualizar límites.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Control de Stock y Existencias
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Monitoreo en tiempo real de niveles de inventario por almacén y alertas de reposición.
          </p>
        </div>
      </div>

      {/* Filter and Search Bar */}
      <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Buscar producto o código..."
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
          />
        </div>

        <div className="flex items-center gap-2">
          <select
            value={almacenFilter}
            onChange={(e) => setAlmacenFilter(e.target.value)}
            className="rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
          >
            <option value="">Todos los Almacenes</option>
            {almacenes.map((a) => (
              <option key={a.id_almacen} value={a.id_almacen}>
                {a.nombre_almacen}
              </option>
            ))}
          </select>

          <button
            type="button"
            onClick={() => setSoloBajoStock(!soloBajoStock)}
            className={`flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold transition-colors ${
              soloBajoStock
                ? 'bg-amber-500 text-white shadow-xs'
                : 'border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'
            }`}
          >
            <AlertTriangle className="h-3.5 w-3.5" />
            <span>Solo Bajo Stock</span>
          </button>
        </div>
      </div>

      {/* Stock Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
              <tr>
                <th className="py-3 px-4">Producto</th>
                <th className="py-3 px-4">Almacén</th>
                <th className="py-3 px-4 text-center">Stock Mínimo</th>
                <th className="py-3 px-4 text-center">Stock Máximo</th>
                <th className="py-3 px-4 text-center">Cantidad Actual</th>
                <th className="py-3 px-4 text-center">Estado</th>
                <th className="py-3 px-4 text-center">Límites</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    <div className="flex items-center justify-center gap-2">
                      <div className="h-4 w-4 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
                      <span>Consultando existencias...</span>
                    </div>
                  </td>
                </tr>
              ) : stock.length === 0 ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    No se encontraron registros de stock. Registre un movimiento de inventario para iniciar existencias.
                  </td>
                </tr>
              ) : (
                stock.map((item) => {
                  const isBajo =
                    parseFloat(item.cantidad_actual) <= parseFloat(item.stock_minimo) &&
                    parseFloat(item.stock_minimo) > 0;

                  return (
                    <tr
                      key={item.id_stock}
                      className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                    >
                      <td className="py-3 px-4">
                        <div className="flex flex-col">
                          <span className="font-semibold text-slate-900 dark:text-white">
                            {item.producto?.nombre_producto || 'Producto'}
                          </span>
                          <span className="font-mono text-[10px] text-slate-400">
                            {item.producto?.codigo_interno}
                          </span>
                        </div>
                      </td>
                      <td className="py-3 px-4 text-slate-600 dark:text-slate-300">
                        <div className="flex items-center gap-1.5">
                          <Warehouse className="h-3.5 w-3.5 text-slate-400" />
                          <span>{item.almacen?.nombre_almacen || 'Principal'}</span>
                        </div>
                      </td>
                      <td className="py-3 px-4 text-center font-mono text-slate-600 dark:text-slate-400">
                        {item.stock_minimo} {item.producto?.unidad_medida || 'UND'}
                      </td>
                      <td className="py-3 px-4 text-center font-mono text-slate-600 dark:text-slate-400">
                        {item.stock_maximo} {item.producto?.unidad_medida || 'UND'}
                      </td>
                      <td className="py-3 px-4 text-center">
                        <span
                          className={`font-mono text-sm font-bold ${
                            isBajo
                              ? 'text-amber-600 dark:text-amber-400'
                              : 'text-slate-900 dark:text-white'
                          }`}
                        >
                          {item.cantidad_actual} {item.producto?.unidad_medida || 'UND'}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-center">
                        {isBajo ? (
                          <span className="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                            <AlertTriangle className="h-3 w-3" />
                            Stock Bajo
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <CheckCircle className="h-3 w-3" />
                            Normal
                          </span>
                        )}
                      </td>
                      <td className="py-3 px-4 text-center">
                        <button
                          onClick={() => handleOpenEditLimits(item)}
                          className="flex h-7 w-7 mx-auto items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-slate-800"
                          title="Ajustar límites mínimo/máximo"
                        >
                          <Sliders className="h-3.5 w-3.5" />
                        </button>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Ajustar Límites */}
      {editingStock && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
          <div className="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <h3 className="text-base font-bold text-slate-900 dark:text-white">
                Ajustar Límites de Stock
              </h3>
              <button
                onClick={() => setEditingStock(null)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <div className="mt-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60">
              <p className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                {editingStock.producto?.nombre_producto}
              </p>
              <p className="text-[11px] text-slate-500 dark:text-slate-400">
                Almacén: {editingStock.almacen?.nombre_almacen}
              </p>
            </div>

            {error && (
              <div className="mt-3 flex items-center gap-2 rounded-xl bg-rose-50 p-2.5 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>{error}</span>
              </div>
            )}

            <form onSubmit={handleSaveLimits} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Stock Mínimo (Alerta de reposición)
                </label>
                <input
                  type="number"
                  step="0.01"
                  required
                  value={minimo}
                  onChange={(e) => setMinimo(e.target.value)}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-mono font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Stock Máximo (Capacidad recomendada)
                </label>
                <input
                  type="number"
                  step="0.01"
                  required
                  value={maximo}
                  onChange={(e) => setMaximo(e.target.value)}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-mono font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setEditingStock(null)}
                  className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50"
                >
                  {saving ? 'Guardando...' : 'Actualizar Límites'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
