import React, { useState, useEffect } from 'react';
import {
  Barcode,
  Plus,
  Search,
  Filter,
  CheckCircle,
  XCircle,
  Warehouse,
  Boxes,
  X,
  AlertCircle,
} from 'lucide-react';
import api from '../api/client';

export default function SeriesPage() {
  const [series, setSeries] = useState([]);
  const [productos, setProductos] = useState([]);
  const [almacenes, setAlmacenes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [estadoFilter, setEstadoFilter] = useState('');

  // Modal State
  const [modalOpen, setModalOpen] = useState(false);
  const [formData, setFormData] = useState({
    id_producto: '',
    id_almacen: '',
    numero_serie: '',
    estado: 'DISPONIBLE',
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const fetchSeries = async () => {
    setLoading(true);
    try {
      const res = await api.get('/series', {
        params: { search, estado: estadoFilter || undefined },
      });
      if (res.data?.data) {
        setSeries(res.data.data.data || res.data.data);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const loadResources = async () => {
    try {
      const [prodRes, almRes] = await Promise.all([
        api.get('/productos', { params: { per_page: 100 } }),
        api.get('/almacenes'),
      ]);
      if (prodRes.data?.data) setProductos(prodRes.data.data.data || prodRes.data.data);
      if (almRes.data?.data) setAlmacenes(almRes.data.data);
    } catch (e) {
      console.error(e);
    }
  };

  useEffect(() => {
    loadResources();
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchSeries();
    }, 300);
    return () => clearTimeout(timer);
  }, [search, estadoFilter]);

  const handleOpenCreate = () => {
    setFormData({
      id_producto: productos[0]?.id_producto || '',
      id_almacen: almacenes[0]?.id_almacen || '',
      numero_serie: '',
      estado: 'DISPONIBLE',
    });
    setError('');
    setModalOpen(true);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    try {
      await api.post('/series', formData);
      setModalOpen(false);
      fetchSeries();
    } catch (err) {
      setError(err.response?.data?.message || 'Error al registrar serie.');
    } finally {
      setSaving(false);
    }
  };

  const handleUpdateStatus = async (id, newEstado) => {
    try {
      await api.put(`/series/${id}/estado`, { estado: newEstado });
      fetchSeries();
    } catch (e) {
      alert('Error al actualizar estado.');
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Trazabilidad de Números de Serie
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Control individual de ítems serializados, ubicación por almacén y estado de ciclo de vida.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          <span>Registrar Número de Serie</span>
        </button>
      </div>

      {/* Filter and Search Bar */}
      <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Buscar por número de serie o producto..."
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
          />
        </div>

        <select
          value={estadoFilter}
          onChange={(e) => setEstadoFilter(e.target.value)}
          className="rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
        >
          <option value="">Todos los Estados</option>
          <option value="DISPONIBLE">Disponible</option>
          <option value="VENDIDO">Vendido</option>
          <option value="TRANSITO">En Tránsito</option>
          <option value="BAJA">Dado de Baja</option>
        </select>
      </div>

      {/* Series Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
              <tr>
                <th className="py-3 px-4">Número de Serie</th>
                <th className="py-3 px-4">Producto Asociado</th>
                <th className="py-3 px-4">Almacén Actual</th>
                <th className="py-3 px-4">Fecha de Registro</th>
                <th className="py-3 px-4 text-center">Estado</th>
                <th className="py-3 px-4 text-center">Cambiar Estado</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan="6" className="py-12 text-center text-slate-400">
                    Cargando series...
                  </td>
                </tr>
              ) : series.length === 0 ? (
                <tr>
                  <td colSpan="6" className="py-12 text-center text-slate-400">
                    No se encontraron números de serie registrados.
                  </td>
                </tr>
              ) : (
                series.map((s) => (
                  <tr
                    key={s.id_serie}
                    className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                  >
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-2">
                        <Barcode className="h-4 w-4 text-indigo-500" />
                        <span className="font-mono font-bold text-slate-900 dark:text-white">
                          {s.numero_serie}
                        </span>
                      </div>
                    </td>
                    <td className="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200">
                      {s.producto?.nombre_producto || 'Producto'}
                    </td>
                    <td className="py-3 px-4 text-slate-600 dark:text-slate-300">
                      {s.almacen?.nombre_almacen || 'Principal'}
                    </td>
                    <td className="py-3 px-4 text-slate-400 font-mono">
                      {s.fecha_registro ? new Date(s.fecha_registro).toLocaleDateString() : '-'}
                    </td>
                    <td className="py-3 px-4 text-center">
                      <span
                        className={`inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold ${
                          s.estado === 'DISPONIBLE'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                            : s.estado === 'VENDIDO'
                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300'
                            : s.estado === 'TRANSITO'
                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                        }`}
                      >
                        {s.estado}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-center">
                      <select
                        value={s.estado}
                        onChange={(e) => handleUpdateStatus(s.id_serie, e.target.value)}
                        className="rounded-lg border border-slate-200 bg-white py-1 px-2 text-[10px] text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                      >
                        <option value="DISPONIBLE">Disponible</option>
                        <option value="VENDIDO">Vendido</option>
                        <option value="TRANSITO">En Tránsito</option>
                        <option value="BAJA">Dar de Baja</option>
                      </select>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Registrar Serie */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
          <div className="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <h3 className="text-base font-bold text-slate-900 dark:text-white">
                Registrar Número de Serie
              </h3>
              <button
                onClick={() => setModalOpen(false)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {error && (
              <div className="mt-3 flex items-center gap-2 rounded-xl bg-rose-50 p-2.5 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>{error}</span>
              </div>
            )}

            <form onSubmit={handleSave} className="mt-4 space-y-3.5">
              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Producto *
                </label>
                <select
                  required
                  value={formData.id_producto}
                  onChange={(e) => setFormData({ ...formData, id_producto: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                  <option value="">Seleccione Producto</option>
                  {productos.map((p) => (
                    <option key={p.id_producto} value={p.id_producto}>
                      {p.nombre_producto}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Almacén *
                </label>
                <select
                  required
                  value={formData.id_almacen}
                  onChange={(e) => setFormData({ ...formData, id_almacen: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                  <option value="">Seleccione Almacén</option>
                  {almacenes.map((a) => (
                    <option key={a.id_almacen} value={a.id_almacen}>
                      {a.nombre_almacen}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Número de Serie (S/N) *
                </label>
                <input
                  type="text"
                  required
                  value={formData.numero_serie}
                  onChange={(e) => setFormData({ ...formData, numero_serie: e.target.value })}
                  placeholder="Ej. WXV1A97RYY4N"
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs font-mono font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setModalOpen(false)}
                  className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50"
                >
                  {saving ? 'Guardando...' : 'Guardar Serie'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
