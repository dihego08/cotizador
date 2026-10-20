import React, { useState, useEffect } from 'react';
import {
  ShieldCheck,
  Plus,
  Mail,
  Building,
  UserCheck,
  Shield,
  Edit2,
  Lock,
  X,
  AlertCircle,
  CheckCircle,
} from 'lucide-react';
import api from '../api/client';

export default function UsuariosPage() {
  const [usuarios, setUsuarios] = useState([]);
  const [empresas, setEmpresas] = useState([]);
  const [modulos, setModulos] = useState([]);
  const [loading, setLoading] = useState(true);

  // Modal State
  const [modalOpen, setModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState(null);
  const [formData, setFormData] = useState({
    id_empresa: '',
    nombre_usuario: '',
    rol: 'OPERADOR',
    email: '',
    password: '',
    cargo: '',
    celular: '',
    permisos: [],
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const fetchUsuarios = async () => {
    setLoading(true);
    try {
      const res = await api.get('/usuarios');
      if (res.data?.data) {
        setUsuarios(res.data.data);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const loadResources = async () => {
    try {
      const [empRes, modRes] = await Promise.all([
        api.get('/empresas'),
        api.get('/modulos'),
      ]);
      if (empRes.data?.data) setEmpresas(empRes.data.data);
      if (modRes.data?.data) setModulos(modRes.data.data);
    } catch (e) {
      console.error(e);
    }
  };

  useEffect(() => {
    fetchUsuarios();
    loadResources();
  }, []);

  const handleOpenCreate = () => {
    setEditingUser(null);
    setFormData({
      id_empresa: empresas[0]?.id_empresa || '',
      nombre_usuario: '',
      rol: 'OPERADOR',
      email: '',
      password: '',
      cargo: '',
      celular: '',
      permisos: [],
    });
    setError('');
    setModalOpen(true);
  };

  const handleOpenEdit = (u) => {
    setEditingUser(u);
    setFormData({
      id_empresa: u.id_empresa || '',
      nombre_usuario: u.nombre_usuario || '',
      rol: u.rol || 'OPERADOR',
      email: u.email || '',
      password: '',
      cargo: u.cargo || '',
      celular: u.celular || '',
      permisos: u.permisos ? u.permisos.map((p) => p.id_modulo) : [],
    });
    setError('');
    setModalOpen(true);
  };

  const handleTogglePermiso = (moduloId) => {
    setFormData((prev) => {
      const exists = prev.permisos.includes(moduloId);
      return {
        ...prev,
        permisos: exists
          ? prev.permisos.filter((id) => id !== moduloId)
          : [...prev.permisos, moduloId],
      };
    });
  };

  const handleToggleEstado = async (id) => {
    try {
      await api.patch(`/usuarios/${id}/toggle-estado`);
      fetchUsuarios();
    } catch (err) {
      alert('Error al cambiar estado.');
    }
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    try {
      if (editingUser) {
        await api.put(`/usuarios/${editingUser.id_usuario}`, formData);
      } else {
        await api.post('/usuarios', formData);
      }
      setModalOpen(false);
      fetchUsuarios();
    } catch (err) {
      setError(err.response?.data?.message || 'Error al guardar usuario.');
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
            Usuarios del Sistema & Permisos
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Control de cuentas de acceso, roles administrativos y matriz de permisos por módulo.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          <span>Crear Usuario</span>
        </button>
      </div>

      {/* Users Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
              <tr>
                <th className="py-3 px-4">Usuario</th>
                <th className="py-3 px-4">Empresa Asignada</th>
                <th className="py-3 px-4">Cargo / Puesto</th>
                <th className="py-3 px-4 text-center">Rol</th>
                <th className="py-3 px-4 text-center">Permisos</th>
                <th className="py-3 px-4 text-center">Estado</th>
                <th className="py-3 px-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    Cargando usuarios...
                  </td>
                </tr>
              ) : (
                usuarios.map((u) => (
                  <tr
                    key={u.id_usuario}
                    className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                  >
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 font-bold dark:bg-indigo-950 dark:text-indigo-300">
                          {u.nombre_usuario.charAt(0).toUpperCase()}
                        </div>
                        <div>
                          <p className="font-semibold text-slate-900 dark:text-white">
                            {u.nombre_usuario}
                          </p>
                          <p className="text-[11px] text-slate-400 font-mono">
                            {u.email}
                          </p>
                        </div>
                      </div>
                    </td>
                    <td className="py-3 px-4 text-slate-600 dark:text-slate-300">
                      {u.empresa?.razon_social || 'Todas las Empresas'}
                    </td>
                    <td className="py-3 px-4 text-slate-500 dark:text-slate-400">
                      {u.cargo || '-'}
                    </td>
                    <td className="py-3 px-4 text-center">
                      <span className="rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                        {u.rol}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-center font-mono text-slate-600 dark:text-slate-400">
                      {u.rol === 'ADMIN' ? 'Total (Admin)' : `${u.permisos?.length || 0} módulos`}
                    </td>
                    <td className="py-3 px-4 text-center">
                      <button
                        onClick={() => handleToggleEstado(u.id_usuario)}
                        className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold transition-colors ${
                          u.activo
                            ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300'
                            : 'bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400'
                        }`}
                      >
                        {u.activo ? 'Activo' : 'Inactivo'}
                      </button>
                    </td>
                    <td className="py-3 px-4 text-center">
                      <button
                        onClick={() => handleOpenEdit(u)}
                        className="flex h-7 w-7 mx-auto items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-slate-800"
                      >
                        <Edit2 className="h-3.5 w-3.5" />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Crear / Editar */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
          <div className="relative w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-8">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <h3 className="text-base font-bold text-slate-900 dark:text-white">
                {editingUser ? 'Editar Cuenta de Usuario' : 'Crear Nueva Cuenta de Usuario'}
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
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Nombre Completo *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.nombre_usuario}
                    onChange={(e) => setFormData({ ...formData, nombre_usuario: e.target.value })}
                    placeholder="Ej. Juan Pérez"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Correo Electrónico *
                  </label>
                  <input
                    type="email"
                    required
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    placeholder="usuario@empresa.com"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
                    Rol *
                  </label>
                  <select
                    value={formData.rol}
                    onChange={(e) => setFormData({ ...formData, rol: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 font-bold dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="ADMIN">ADMIN (Acceso Total)</option>
                    <option value="VENDEDOR">VENDEDOR</option>
                    <option value="TECNICO">TÉCNICO SOPORTE</option>
                    <option value="ALMACENERO">ALMACENERO</option>
                    <option value="OPERADOR">OPERADOR</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    {editingUser ? 'Nueva Contraseña (Opcional)' : 'Contraseña *'}
                  </label>
                  <input
                    type="password"
                    required={!editingUser}
                    value={formData.password}
                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    placeholder="••••••••"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              {/* Modules / Permissions Matrix */}
              {formData.rol !== 'ADMIN' && (
                <div className="pt-2 border-t border-slate-100 dark:border-slate-800">
                  <p className="text-xs font-bold text-slate-900 dark:text-white mb-2">
                    Módulos y Permisos de Acceso Asignados:
                  </p>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                    {modulos.map((m) => {
                      const allSub = m.submodulos && m.submodulos.length > 0 ? m.submodulos : [m];
                      return allSub.map((sub) => (
                        <label
                          key={sub.id_modulo}
                          className="flex items-center gap-2 p-1 text-xs cursor-pointer text-slate-700 dark:text-slate-300 hover:text-indigo-600"
                        >
                          <input
                            type="checkbox"
                            checked={formData.permisos.includes(sub.id_modulo)}
                            onChange={() => handleTogglePermiso(sub.id_modulo)}
                            className="h-3.5 w-3.5 rounded-sm border-slate-300 text-indigo-600 focus:ring-indigo-500"
                          />
                          <span className="truncate">{sub.nombre}</span>
                        </label>
                      ));
                    })}
                  </div>
                </div>
              )}

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
                  {saving ? 'Guardando...' : 'Guardar Usuario'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
