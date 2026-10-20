import React, { useState, useEffect } from 'react';
import {
  DollarSign,
  TrendingUp,
  Plus,
  Calendar,
  AlertCircle,
  CheckCircle,
} from 'lucide-react';
import api from '../api/client';

export default function TipoCambioPage() {
  const [historial, setHistorial] = useState([]);
  const [loading, setLoading] = useState(true);
  const [formData, setFormData] = useState({
    fecha: new Date().toISOString().split('T')[0],
    compra: '3.7500',
    venta: '3.7550',
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const fetchTC = async () => {
    setLoading(true);
    try {
      const res = await api.get('/tipo-cambio');
      if (res.data?.data) {
        setHistorial(res.data.data);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTC();
  }, []);

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    setSuccess('');

    try {
      await api.post('/tipo-cambio', formData);
      setSuccess('Tipo de cambio registrado exitosamente.');
      fetchTC();
    } catch (err) {
      setError(
        err.response?.data?.errors?.fecha?.[0] ||
        err.response?.data?.message ||
        'Error al registrar tipo de cambio.'
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Control de Tipo de Cambio (USD / PEN)
        </h1>
        <p className="text-xs text-slate-500 dark:text-slate-400">
          Registro diario de cotizaciones cambiarias para conversión contable y cotizaciones.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Register Card */}
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <h2 className="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <TrendingUp className="h-4 w-4 text-emerald-500" />
            <span>Registrar Tipo de Cambio</span>
          </h2>

          {error && (
            <div className="mb-4 flex items-center gap-2 rounded-xl bg-rose-50 p-2.5 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
              <AlertCircle className="h-4 w-4 shrink-0" />
              <span>{error}</span>
            </div>
          )}

          {success && (
            <div className="mb-4 flex items-center gap-2 rounded-xl bg-emerald-50 p-2.5 text-xs text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
              <CheckCircle className="h-4 w-4 shrink-0" />
              <span>{success}</span>
            </div>
          )}

          <form onSubmit={handleSave} className="space-y-3.5">
            <div>
              <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Fecha de Vigencia *
              </label>
              <input
                type="date"
                required
                value={formData.fecha}
                onChange={(e) => setFormData({ ...formData, fecha: e.target.value })}
                className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-mono text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
              />
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Precio Compra *
                </label>
                <input
                  type="number"
                  step="0.0001"
                  min="0.0001"
                  required
                  value={formData.compra}
                  onChange={(e) => setFormData({ ...formData, compra: e.target.value })}
                  placeholder="3.7500"
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-mono font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Precio Venta *
                </label>
                <input
                  type="number"
                  step="0.0001"
                  min="0.0001"
                  required
                  value={formData.venta}
                  onChange={(e) => setFormData({ ...formData, venta: e.target.value })}
                  placeholder="3.7550"
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-mono font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={saving}
              className="w-full mt-2 rounded-xl bg-indigo-600 py-2.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 transition-colors disabled:opacity-50"
            >
              {saving ? 'Guardando...' : 'Guardar Tipo de Cambio'}
            </button>
          </form>
        </div>

        {/* History Table */}
        <div className="lg:col-span-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 className="text-sm font-bold text-slate-900 dark:text-white">
              Histórico Reciente de Cotizaciones
            </h2>
            <span className="text-[11px] text-slate-400">Últimos 30 registros</span>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                <tr>
                  <th className="py-3 px-4">Fecha</th>
                  <th className="py-3 px-4 text-right">Compra (S/.)</th>
                  <th className="py-3 px-4 text-right">Venta (S/.)</th>
                  <th className="py-3 px-4 text-right">Diferencial</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {loading ? (
                  <tr>
                    <td colSpan="4" className="py-12 text-center text-slate-400">
                      Cargando histórico...
                    </td>
                  </tr>
                ) : historial.length === 0 ? (
                  <tr>
                    <td colSpan="4" className="py-12 text-center text-slate-400">
                      No hay registros de tipo de cambio.
                    </td>
                  </tr>
                ) : (
                  historial.map((tc) => {
                    const diff = (parseFloat(tc.venta) - parseFloat(tc.compra)).toFixed(4);
                    return (
                      <tr
                        key={tc.id_tc}
                        className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                      >
                        <td className="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white">
                          {tc.fecha}
                        </td>
                        <td className="py-3 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                          {Number(tc.compra).toFixed(4)}
                        </td>
                        <td className="py-3 px-4 text-right font-mono text-indigo-600 dark:text-indigo-400 font-semibold">
                          {Number(tc.venta).toFixed(4)}
                        </td>
                        <td className="py-3 px-4 text-right font-mono text-slate-400">
                          {diff}
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
