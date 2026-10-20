import React, { useState, useEffect } from 'react';
import {
  Warehouse,
  Plus,
  MapPin,
  User,
  Building,
  Edit2,
  Trash2,
  X,
  AlertCircle,
} from 'lucide-react';
import api from '../api/client';
import { useAuth } from '../context/AuthContext';

export default function AlmacenesPage() {
  const { user } = useAuth();
  const [almacenes, setAlmacenes] = useState([]);
  const [empresas, setEmpresas] = useState([]);
  const [usuarios, setUsuarios] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingAlmacen, setEditingAlmacen] = useState(null);
  const [formData, setFormData] = useState({
    id_empresa: user?.empresa?.id_empresa || '',
    id_responsable: '',
    nombre_almacen: '',
    ubicacion: '',
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const fetchAlmacenes = async () => {
    setLoading(true);
    try {
      const res = await api.get('/almacenes');
      if (res.data?.data) {
        setAlmacenes(res.data.data);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const loadResources = async () => {
    try {
      const [empRes, usrRes] = await Promise.all([
        api.get('/empresas'),
        api.get('/usuarios'),
      ]);
      if (empRes.data?.data) setEmpresas(empRes.data.data);
      if (usrRes.data?.data) setUsuarios(usrRes.data.data);
    } catch (e) {
      console.error(e);
    }
  };

  useEffect(() => {
    fetchAlmacenes();
    loadResources();
  }, []);

  const handleOpenCreate = () => {
    setEditingAlmacen(null);
    setFormData({
      id_empresa: user?.empresa?.id_empresa || empresas[0]?.id_empresa || '',
      id_responsable: '',
      nombre_almacen: '',
      ubicacion: '',
    });
    setError('');
    setModalOpen(true);
  };

  const handleOpenEdit = (a) => {
    setEditingAlmacen(a);
    setFormData({
      id_empresa: a.id_empresa || '',
      id_responsable: a.id_responsable || '',
      nombre_almacen: a.nombre_almacen || '',
      ubicacion: a.ubicacion || '',
    });
    setError('');
    setModalOpen(true);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    try {
      if (editingAlmacen) {
        await api.put(`/almacenes/${editingAlmacen.id_almacen}`, formData);
      } else {
        await api.post('/almacenes', formData);
      }
      setModalOpen(false);
      fetchAlmacenes();
    } catch (err) {
      setError(err.response?.data?.message || 'Error al guardar el almacén.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('¿Seguro de eliminar este almacén?')) return;
    try {
      await api.delete(`/almacenes/${id}`);
      fetchAlmacenes();
    } catch (e) {
      alert('Error al eliminar almacén.');
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Gestión de Almacenes y Sedes
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Administración de almacenes físicos, responsables de custodia y ubicaciones.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          <span>Crear Nuevo Almacén</span>
        </button>
      </div>

      {/* Grid of Warehouses */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {loading ? (
          <div className="col-span-full py-12 text-center text-slate-400">
            Cargando almacenes...
          </div>
        ) : almacenes.length === 0 ? (
          <div className="col-span-full py-12 text-center text-slate-400">
            No hay almacenes configurados.
          </div>
        ) : (
          almacenes.map((a) => (
            <div
              key={a.id_almacen}
              className="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900 hover:shadow-md transition-shadow"
            >
              <div>
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                      <Warehouse className="h-5 w-5" />
                    </div>
                    <div>
                      <h3 className="text-sm font-bold text-slate-900 dark:text-white">
                        {a.nombre_almacen}
                      </h3>
                      <p className="text-[11px] text-slate-500 dark:text-slate-400">
                        {a.empresa?.razon_social || 'Empresa'}
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center gap-1">
                    <button
                      onClick={() => handleOpenEdit(a)}
                      className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800"
                    >
                      <Edit2 className="h-3.5 w-3.5" />
                    </button>
                    <button
                      onClick={() => handleDelete(a.id_almacen)}
                      className="rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </button>
                  </div>
                </div>

                <div className="mt-4 space-y-2 text-xs text-slate-600 dark:text-slate-400">
                  <div className="flex items-center gap-2">
                    <MapPin className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                    <span className="truncate">{a.ubicacion || 'Sin dirección registrada'}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <User className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                    <span>Responsable: {a.responsable?.nombre_usuario || 'Sin asignar'}</span>
                  </div>
                </div>
              </div>
            </div>
          ))
        )}
      </div>

      {/* Modal Crear / Editar */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
          <div className="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <h3 className="text-base font-bold text-slate-900 dark:text-white">
                {editingAlmacen ? 'Editar Almacén' : 'Nuevo Almacén'}
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
                  Empresa *
                </label>
                <select
                  required
                  value={formData.id_empresa}
                  onChange={(e) => setFormData({ ...formData, id_empresa: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                  <option value="">Seleccione Empresa</option>
                  {empresas.map((e) => (
                    <option key={e.id_empresa} value={e.id_empresa}>
                      {e.razon_social}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Nombre del Almacén *
                </label>
                <input
                  type="text"
                  required
                  value={formData.nombre_almacen}
                  onChange={(e) => setFormData({ ...formData, nombre_almacen: e.target.value })}
                  placeholder="Ej. Almacén Principal, Sede Arequipa"
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Ubicación / Dirección
                </label>
                <input
                  type="text"
                  value={formData.ubicacion}
                  onChange={(e) => setFormData({ ...formData, ubicacion: e.target.value })}
                  placeholder="Ej. Francisco Bolognesi 303"
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Usuario Responsable
                </label>
                <select
                  value={formData.id_responsable}
                  onChange={(e) => setFormData({ ...formData, id_responsable: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                  <option value="">Sin responsable asignado</option>
                  {usuarios.map((u) => (
                    <option key={u.id_usuario} value={u.id_usuario}>
                      {u.nombre_usuario} ({u.cargo || u.rol})
                    </option>
                  ))}
                </select>
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
                  {saving ? 'Guardando...' : 'Guardar Almacén'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
