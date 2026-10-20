import React, { useState, useEffect } from 'react';
import {
  Building2,
  Plus,
  Mail,
  Phone,
  MapPin,
  UserCheck,
  CheckCircle,
  Edit2,
  X,
  AlertCircle,
} from 'lucide-react';
import api from '../api/client';

export default function EmpresasPage() {
  const [empresas, setEmpresas] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingEmpresa, setEditingEmpresa] = useState(null);
  const [formData, setFormData] = useState({
    razon_social: '',
    ruc: '',
    direccion: '',
    telefono: '',
    email: '',
    nombre_gerente: '',
    cargo_gerente: '',
    cel_gerente: '',
    es_principal: false,
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const fetchEmpresas = async () => {
    setLoading(true);
    try {
      const res = await api.get('/empresas');
      if (res.data?.data) {
        setEmpresas(res.data.data);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEmpresas();
  }, []);

  const handleOpenCreate = () => {
    setEditingEmpresa(null);
    setFormData({
      razon_social: '',
      ruc: '',
      direccion: '',
      telefono: '',
      email: '',
      nombre_gerente: '',
      cargo_gerente: '',
      cel_gerente: '',
      es_principal: false,
    });
    setError('');
    setModalOpen(true);
  };

  const handleOpenEdit = (emp) => {
    setEditingEmpresa(emp);
    setFormData({
      razon_social: emp.razon_social || '',
      ruc: emp.ruc || '',
      direccion: emp.direccion || '',
      telefono: emp.telefono || '',
      email: emp.email || '',
      nombre_gerente: emp.nombre_gerente || '',
      cargo_gerente: emp.cargo_gerente || '',
      cel_gerente: emp.cel_gerente || '',
      es_principal: !!emp.es_principal,
    });
    setError('');
    setModalOpen(true);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    try {
      if (editingEmpresa) {
        await api.put(`/empresas/${editingEmpresa.id_empresa}`, formData);
      } else {
        await api.post('/empresas', formData);
      }
      setModalOpen(false);
      fetchEmpresas();
    } catch (err) {
      setError(err.response?.data?.message || 'Error al guardar empresa.');
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
            Gestión de Empresas & Razón Social
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Configuración multi-empresa, datos de facturación, representantes legales y logotipos.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          <span>Registrar Empresa</span>
        </button>
      </div>

      {/* Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {loading ? (
          <div className="col-span-full py-12 text-center text-slate-400">
            Cargando empresas...
          </div>
        ) : (
          empresas.map((emp) => (
            <div
              key={emp.id_empresa}
              className="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900"
            >
              <div>
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-3">
                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                      <Building2 className="h-6 w-6" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <h3 className="text-base font-bold text-slate-900 dark:text-white">
                          {emp.razon_social}
                        </h3>
                        {emp.es_principal && (
                          <span className="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                            Principal
                          </span>
                        )}
                      </div>
                      <p className="font-mono text-xs text-slate-500 dark:text-slate-400">
                        RUC: {emp.ruc}
                      </p>
                    </div>
                  </div>

                  <button
                    onClick={() => handleOpenEdit(emp)}
                    className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800"
                  >
                    <Edit2 className="h-4 w-4" />
                  </button>
                </div>

                <div className="mt-5 space-y-2 text-xs text-slate-600 dark:text-slate-400">
                  {emp.direccion && (
                    <div className="flex items-center gap-2">
                      <MapPin className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                      <span>{emp.direccion}</span>
                    </div>
                  )}
                  {emp.telefono && (
                    <div className="flex items-center gap-2">
                      <Phone className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                      <span>{emp.telefono}</span>
                    </div>
                  )}
                  {emp.email && (
                    <div className="flex items-center gap-2">
                      <Mail className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                      <span>{emp.email}</span>
                    </div>
                  )}
                  {emp.nombre_gerente && (
                    <div className="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                      <UserCheck className="h-3.5 w-3.5 text-indigo-500 shrink-0" />
                      <span>
                        <strong className="text-slate-800 dark:text-slate-200">
                          {emp.nombre_gerente}
                        </strong>{' '}
                        {emp.cargo_gerente ? `(${emp.cargo_gerente})` : ''}
                      </span>
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))
        )}
      </div>

      {/* Modal Crear / Editar */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
          <div className="relative w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <h3 className="text-base font-bold text-slate-900 dark:text-white">
                {editingEmpresa ? 'Editar Empresa' : 'Registrar Empresa'}
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
                  Razón Social *
                </label>
                <input
                  type="text"
                  required
                  value={formData.razon_social}
                  onChange={(e) => setFormData({ ...formData, razon_social: e.target.value })}
                  placeholder="Ej. RUVINDUSTRIAS S.A.C."
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    RUC (11 dígitos) *
                  </label>
                  <input
                    type="text"
                    required
                    maxLength={11}
                    value={formData.ruc}
                    onChange={(e) => setFormData({ ...formData, ruc: e.target.value })}
                    placeholder="20606370149"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs font-mono text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Teléfono
                  </label>
                  <input
                    type="text"
                    value={formData.telefono}
                    onChange={(e) => setFormData({ ...formData, telefono: e.target.value })}
                    placeholder="978401982"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Dirección Fiscal
                </label>
                <input
                  type="text"
                  value={formData.direccion}
                  onChange={(e) => setFormData({ ...formData, direccion: e.target.value })}
                  placeholder="Sor Ana de los Ángeles - Cerro Colorado - Arequipa"
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Nombre del Gerente / Rep.
                  </label>
                  <input
                    type="text"
                    value={formData.nombre_gerente}
                    onChange={(e) => setFormData({ ...formData, nombre_gerente: e.target.value })}
                    placeholder="RUIZ VIZCARRA HERNAN"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Cargo del Gerente
                  </label>
                  <input
                    type="text"
                    value={formData.cargo_gerente}
                    onChange={(e) => setFormData({ ...formData, cargo_gerente: e.target.value })}
                    placeholder="GERENTE DE OPERACIONES"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              <div className="flex items-center gap-2 pt-1">
                <input
                  type="checkbox"
                  id="chkPrincipal"
                  checked={formData.es_principal}
                  onChange={(e) => setFormData({ ...formData, es_principal: e.target.checked })}
                  className="h-4 w-4 rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                <label htmlFor="chkPrincipal" className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Establecer como Empresa Principal por Defecto
                </label>
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
                  {saving ? 'Guardando...' : 'Guardar Empresa'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
