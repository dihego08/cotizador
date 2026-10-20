import React, { useState, useEffect } from 'react';
import {
  ArrowLeftRight,
  Plus,
  Search,
  ArrowDownLeft,
  ArrowUpRight,
  Filter,
  X,
  AlertCircle,
  CheckCircle,
} from 'lucide-react';
import api from '../api/client';

export default function MovimientosPage() {
  const [movimientos, setMovimientos] = useState([]);
  const [productos, setProductos] = useState([]);
  const [almacenes, setAlmacenes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [tipoFilter, setTipoFilter] = useState('');
  const [almacenFilter, setAlmacenFilter] = useState('');

  // Modal State
  const [modalOpen, setModalOpen] = useState(false);
  const [formData, setFormData] = useState({
    id_producto: '',
    id_almacen_origen: '',
    id_almacen_destino: '',
    tipo_movimiento: 'ENTRADA',
    cantidad: '',
    costo_unitario: '',
    referencia: '',
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const fetchMovimientos = async () => {
    setLoading(true);
    try {
      const res = await api.get('/movimientos', {
        params: {
          tipo_movimiento: tipoFilter || undefined,
          id_almacen: almacenFilter || undefined,
        },
      });
      if (res.data?.data) {
        setMovimientos(res.data.data.data || res.data.data);
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
      if (prodRes.data?.data) {
        setProductos(prodRes.data.data.data || prodRes.data.data);
      }
      if (almRes.data?.data) {
        setAlmacenes(almRes.data.data);
      }
    } catch (e) {
      console.error(e);
    }
  };

  useEffect(() => {
    loadResources();
  }, []);

  useEffect(() => {
    fetchMovimientos();
  }, [tipoFilter, almacenFilter]);

  const handleOpenCreate = () => {
    setFormData({
      id_producto: productos[0]?.id_producto || '',
      id_almacen_origen: almacenes[0]?.id_almacen || '',
      id_almacen_destino: '',
      tipo_movimiento: 'ENTRADA',
      cantidad: '',
      costo_unitario: '',
      referencia: '',
    });
    setError('');
    setSuccess('');
    setModalOpen(true);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    try {
      await api.post('/movimientos', formData);
      setSuccess('Movimiento registrado correctamente y stock actualizado.');
      setTimeout(() => {
        setModalOpen(false);
        fetchMovimientos();
      }, 500);
    } catch (err) {
      setError(
        err.response?.data?.errors?.cantidad?.[0] ||
        err.response?.data?.message ||
        'Error al registrar movimiento.'
      );
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
            Kardex de Movimientos de Inventario
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Registro transaccional de entradas, salidas, transferencias internas y ajustes de stock.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          <span>Registrar Movimiento</span>
        </button>
      </div>

      {/* Filters */}
      <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center">
        <select
          value={tipoFilter}
          onChange={(e) => setTipoFilter(e.target.value)}
          className="rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
        >
          <option value="">Todos los tipos de movimiento</option>
          <option value="ENTRADA">Entradas</option>
          <option value="SALIDA">Salidas</option>
          <option value="TRANSFERENCIA">Transferencias</option>
          <option value="AJUSTE">Ajustes</option>
        </select>

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
      </div>

      {/* Movements Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
              <tr>
                <th className="py-3 px-4">Fecha / Hora</th>
                <th className="py-3 px-4">Tipo</th>
                <th className="py-3 px-4">Producto</th>
                <th className="py-3 px-4">Origen / Destino</th>
                <th className="py-3 px-4 text-right">Cantidad</th>
                <th className="py-3 px-4">Referencia</th>
                <th className="py-3 px-4">Usuario</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    <div className="flex items-center justify-center gap-2">
                      <div className="h-4 w-4 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
                      <span>Cargando movimientos...</span>
                    </div>
                  </td>
                </tr>
              ) : movimientos.length === 0 ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    No se registran movimientos con los filtros seleccionados.
                  </td>
                </tr>
              ) : (
                movimientos.map((mov) => {
                  const isEntrada = mov.tipo_movimiento === 'ENTRADA';
                  const isSalida = mov.tipo_movimiento === 'SALIDA';

                  return (
                    <tr
                      key={mov.id_movimiento}
                      className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                    >
                      <td className="py-3 px-4 text-slate-600 dark:text-slate-400 font-mono">
                        {new Date(mov.fecha_movimiento).toLocaleString()}
                      </td>
                      <td className="py-3 px-4">
                        <span
                          className={`inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold ${
                            isEntrada
                              ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                              : isSalida
                              ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                              : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                          }`}
                        >
                          {isEntrada ? (
                            <ArrowDownLeft className="h-3 w-3" />
                          ) : isSalida ? (
                            <ArrowUpRight className="h-3 w-3" />
                          ) : (
                            <ArrowLeftRight className="h-3 w-3" />
                          )}
                          {mov.tipo_movimiento}
                        </span>
                      </td>
                      <td className="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                        {mov.producto?.nombre_producto || 'Producto'}
                      </td>
                      <td className="py-3 px-4 text-slate-600 dark:text-slate-300">
                        {mov.almacen_origen?.nombre_almacen || mov.almacen?.nombre_almacen}
                        {mov.almacen_destino && ` ➔ ${mov.almacen_destino.nombre_almacen}`}
                      </td>
                      <td className="py-3 px-4 text-right font-mono font-bold">
                        <span
                          className={
                            isEntrada
                              ? 'text-emerald-600 dark:text-emerald-400'
                              : isSalida
                              ? 'text-rose-600 dark:text-rose-400'
                              : 'text-indigo-600 dark:text-indigo-400'
                          }
                        >
                          {isEntrada ? '+' : isSalida ? '-' : ''}
                          {mov.cantidad} {mov.producto?.unidad_medida || 'UND'}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-slate-500 dark:text-slate-400">
                        {mov.referencia || '-'}
                      </td>
                      <td className="py-3 px-4 text-slate-600 dark:text-slate-400">
                        {mov.usuario?.nombre_usuario || 'Admin'}
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Registrar Movimiento */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
          <div className="relative w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <h3 className="text-base font-bold text-slate-900 dark:text-white">
                Registrar Movimiento de Inventario
              </h3>
              <button
                onClick={() => setModalOpen(false)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {error && (
              <div className="mt-3 flex items-center gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>{error}</span>
              </div>
            )}

            {success && (
              <div className="mt-3 flex items-center gap-2 rounded-xl bg-emerald-50 p-3 text-xs text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                <CheckCircle className="h-4 w-4 shrink-0" />
                <span>{success}</span>
              </div>
            )}

            <form onSubmit={handleSave} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Tipo de Movimiento *
                </label>
                <select
                  value={formData.tipo_movimiento}
                  onChange={(e) => setFormData({ ...formData, tipo_movimiento: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 font-semibold dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                  <option value="ENTRADA">ENTRADA (Ingreso a almacén)</option>
                  <option value="SALIDA">SALIDA (Despacho / Venta / Merma)</option>
                  <option value="TRANSFERENCIA">TRANSFERENCIA (Entre dos almacenes)</option>
                  <option value="AJUSTE">AJUSTE (Corrección física de conteo)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Producto *
                </label>
                <select
                  required
                  value={formData.id_producto}
                  onChange={(e) => setFormData({ ...formData, id_producto: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                  <option value="">Seleccione Producto</option>
                  {productos.map((p) => (
                    <option key={p.id_producto} value={p.id_producto}>
                      {p.codigo_interno} - {p.nombre_producto}
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    {formData.tipo_movimiento === 'TRANSFERENCIA' ? 'Almacén Origen *' : 'Almacén *'}
                  </label>
                  <select
                    required
                    value={formData.id_almacen_origen}
                    onChange={(e) => setFormData({ ...formData, id_almacen_origen: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="">Seleccione Almacén</option>
                    {almacenes.map((a) => (
                      <option key={a.id_almacen} value={a.id_almacen}>
                        {a.nombre_almacen}
                      </option>
                    ))}
                  </select>
                </div>

                {formData.tipo_movimiento === 'TRANSFERENCIA' && (
                  <div>
                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                      Almacén Destino *
                    </label>
                    <select
                      required
                      value={formData.id_almacen_destino}
                      onChange={(e) => setFormData({ ...formData, id_almacen_destino: e.target.value })}
                      className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    >
                      <option value="">Seleccione Almacén Destino</option>
                      {almacenes
                        .filter((a) => a.id_almacen != formData.id_almacen_origen)
                        .map((a) => (
                          <option key={a.id_almacen} value={a.id_almacen}>
                            {a.nombre_almacen}
                          </option>
                        ))}
                    </select>
                  </div>
                )}
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Cantidad *
                  </label>
                  <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    required
                    value={formData.cantidad}
                    onChange={(e) => setFormData({ ...formData, cantidad: e.target.value })}
                    placeholder="Ej. 10"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-mono font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Costo Unitario
                  </label>
                  <input
                    type="number"
                    step="0.01"
                    value={formData.costo_unitario}
                    onChange={(e) => setFormData({ ...formData, costo_unitario: e.target.value })}
                    placeholder="0.00"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-mono text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Referencia / Glosa
                </label>
                <input
                  type="text"
                  value={formData.referencia}
                  onChange={(e) => setFormData({ ...formData, referencia: e.target.value })}
                  placeholder="Ej. Guía Remisión N° 001-204, Factura F001-99"
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
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
                  {saving ? 'Procesando...' : 'Guardar y Actualizar Stock'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
