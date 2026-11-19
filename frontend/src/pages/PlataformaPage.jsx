import React, { useState, useEffect } from 'react';
import { Navigate } from 'react-router-dom';
import {
  Plus,
  Edit2,
  X,
  AlertCircle,
  Ban,
  PlayCircle,
  ChevronDown,
  ChevronRight,
  Building2,
  Users,
  KeyRound,
} from 'lucide-react';
import api from '../api/client';
import { useAuth } from '../context/AuthContext';

const inputClass =
  'w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white';
const labelClass = 'block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1';

const cuentaVacia = {
  nombre: '',
  plan: '',
  max_empresas: '',
  max_usuarios: '',
  max_almacenes: '',
  notas: '',
  empresa: { razon_social: '', ruc: '', direccion: '', telefono: '', email: '' },
  admin: { nombre_usuario: '', email: '', password: '' },
};

// Contraseña temporal legible para entregar al cliente (el sistema le pedirá cambiarla)
const generarPassword = () => {
  const caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
  const valores = crypto.getRandomValues(new Uint32Array(10));
  return Array.from(valores, (v) => caracteres[v % caracteres.length]).join('');
};

// Los límites vacíos significan "sin límite"
const limitesParaEnviar = (data) => {
  const resultado = { ...data };
  ['max_empresas', 'max_usuarios', 'max_almacenes'].forEach((campo) => {
    resultado[campo] = data[campo] === '' || data[campo] === null ? null : Number(data[campo]);
  });
  return resultado;
};

// Al editar solo se envían los datos de la cuenta (la empresa y el admin se crean una vez)
const datosDeCuenta = ({ nombre, plan, max_empresas, max_usuarios, max_almacenes, notas }) => ({
  nombre,
  plan,
  max_empresas,
  max_usuarios,
  max_almacenes,
  notas,
});

const uso = (actual, maximo) => (maximo ? `${actual} / ${maximo}` : `${actual}`);

export default function PlataformaPage() {
  const { user } = useAuth();
  const [cuentas, setCuentas] = useState([]);
  const [loading, setLoading] = useState(true);
  const [detalle, setDetalle] = useState({});
  const [abierta, setAbierta] = useState(null);

  const [modal, setModal] = useState(null); // 'crear' | 'editar'
  const [editando, setEditando] = useState(null);
  const [formData, setFormData] = useState(cuentaVacia);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [creada, setCreada] = useState(null);

  const fetchCuentas = async () => {
    setLoading(true);
    try {
      const res = await api.get('/plataforma/cuentas');
      setCuentas(res.data?.data || []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (user?.is_superadmin) fetchCuentas();
  }, [user?.is_superadmin]);

  if (!user?.is_superadmin) {
    return <Navigate to="/" replace />;
  }

  const toggleDetalle = async (id) => {
    if (abierta === id) {
      setAbierta(null);
      return;
    }
    setAbierta(id);
    try {
      const res = await api.get(`/plataforma/cuentas/${id}`);
      setDetalle((prev) => ({ ...prev, [id]: res.data.data }));
    } catch (e) {
      console.error(e);
    }
  };

  const handleOpenCreate = () => {
    setEditando(null);
    setFormData({ ...cuentaVacia, admin: { ...cuentaVacia.admin, password: generarPassword() } });
    setError('');
    setModal('crear');
  };

  const handleOpenEdit = (c) => {
    setEditando(c);
    setFormData({
      ...cuentaVacia,
      nombre: c.nombre || '',
      plan: c.plan || '',
      max_empresas: c.max_empresas ?? '',
      max_usuarios: c.max_usuarios ?? '',
      max_almacenes: c.max_almacenes ?? '',
      notas: c.notas || '',
    });
    setError('');
    setModal('editar');
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    try {
      if (modal === 'editar') {
        await api.put(`/plataforma/cuentas/${editando.id_cuenta}`, limitesParaEnviar(datosDeCuenta(formData)));
      } else {
        await api.post('/plataforma/cuentas', limitesParaEnviar(formData));
        // Se muestran una sola vez los datos de acceso para entregarlos al cliente
        setCreada({ email: formData.admin.email, password: formData.admin.password });
      }
      setModal(null);
      setDetalle({});
      setAbierta(null);
      fetchCuentas();
    } catch (err) {
      const errores = err.response?.data?.errors;
      setError((errores && Object.values(errores)[0]?.[0]) || err.response?.data?.message || 'Error al guardar la cuenta.');
    } finally {
      setSaving(false);
    }
  };

  const handleEstado = async (c) => {
    const suspender = c.estado === 'ACTIVA';
    const mensaje = suspender
      ? `¿Suspender la cuenta "${c.nombre}"? Sus usuarios no podrán ingresar y se cerrarán las sesiones abiertas.`
      : `¿Reactivar la cuenta "${c.nombre}"?`;
    if (!window.confirm(mensaje)) return;

    try {
      await api.patch(`/plataforma/cuentas/${c.id_cuenta}/estado`, { estado: suspender ? 'SUSPENDIDA' : 'ACTIVA' });
      fetchCuentas();
    } catch (err) {
      alert(err.response?.data?.message || 'Error al cambiar el estado de la cuenta.');
    }
  };

  const setCampo = (campo, valor) => setFormData({ ...formData, [campo]: valor });
  const setAnidado = (grupo, campo, valor) =>
    setFormData({ ...formData, [grupo]: { ...formData[grupo], [campo]: valor } });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Plataforma: Cuentas de Clientes
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Alta de clientes, planes, límites de uso y suspensión de acceso.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          <span>Nueva Cuenta</span>
        </button>
      </div>

      {creada && (
        <div className="flex items-start justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
          <div className="flex items-start gap-3">
            <KeyRound className="h-5 w-5 shrink-0" />
            <div>
              <p className="font-bold">Cuenta creada. Entrega estos datos de acceso al cliente:</p>
              <p className="mt-1 font-mono">Usuario: {creada.email}</p>
              <p className="font-mono">Contraseña temporal: {creada.password}</p>
              <p className="mt-1">Se muestran solo esta vez. El sistema le pedirá cambiar la contraseña al ingresar.</p>
            </div>
          </div>
          <button onClick={() => setCreada(null)} className="rounded-lg p-1 hover:bg-emerald-100 dark:hover:bg-emerald-900/40">
            <X className="h-4 w-4" />
          </button>
        </div>
      )}

      {/* Tabla de cuentas */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-100 bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
              <tr>
                <th className="py-3 px-4">Cuenta</th>
                <th className="py-3 px-4">Plan</th>
                <th className="py-3 px-4 text-center">Empresas</th>
                <th className="py-3 px-4 text-center">Usuarios</th>
                <th className="py-3 px-4 text-center">Máx. Almacenes</th>
                <th className="py-3 px-4 text-center">Estado</th>
                <th className="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan={7} className="py-12 text-center text-slate-400">Cargando cuentas...</td>
                </tr>
              ) : cuentas.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-12 text-center text-slate-400">No hay cuentas registradas.</td>
                </tr>
              ) : (
                cuentas.map((c) => (
                  <React.Fragment key={c.id_cuenta}>
                    <tr className="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                      <td className="py-3 px-4">
                        <button onClick={() => toggleDetalle(c.id_cuenta)} className="flex items-center gap-2 text-left">
                          {abierta === c.id_cuenta ? (
                            <ChevronDown className="h-3.5 w-3.5 text-slate-400" />
                          ) : (
                            <ChevronRight className="h-3.5 w-3.5 text-slate-400" />
                          )}
                          <div>
                            <p className="font-semibold text-slate-900 dark:text-white">
                              {c.nombre}
                              {c.es_plataforma && (
                                <span className="ml-2 rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                  Plataforma
                                </span>
                              )}
                            </p>
                            <p className="text-[11px] text-slate-400">
                              Desde {c.created_at ? new Date(c.created_at).toLocaleDateString('es-PE') : '-'}
                            </p>
                          </div>
                        </button>
                      </td>
                      <td className="py-3 px-4 text-slate-600 dark:text-slate-300">{c.plan || '-'}</td>
                      <td className="py-3 px-4 text-center font-mono text-slate-600 dark:text-slate-300">
                        {uso(c.empresas_count, c.max_empresas)}
                      </td>
                      <td className="py-3 px-4 text-center font-mono text-slate-600 dark:text-slate-300">
                        {uso(c.usuarios_count, c.max_usuarios)}
                      </td>
                      <td className="py-3 px-4 text-center font-mono text-slate-600 dark:text-slate-300">
                        {c.max_almacenes ?? 'Sin límite'}
                      </td>
                      <td className="py-3 px-4 text-center">
                        <span
                          className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ${
                            c.estado === 'ACTIVA'
                              ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                              : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                          }`}
                        >
                          {c.estado === 'ACTIVA' ? 'Activa' : 'Suspendida'}
                        </span>
                      </td>
                      <td className="py-3 px-4">
                        <div className="flex items-center justify-end gap-1">
                          <button
                            onClick={() => handleOpenEdit(c)}
                            title="Editar plan y límites"
                            className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800"
                          >
                            <Edit2 className="h-3.5 w-3.5" />
                          </button>
                          {/* La cuenta de plataforma no se puede suspender */}
                          {!c.es_plataforma && (
                            <button
                              onClick={() => handleEstado(c)}
                              title={c.estado === 'ACTIVA' ? 'Suspender' : 'Reactivar'}
                              className={`rounded-lg p-1 text-slate-400 ${
                                c.estado === 'ACTIVA'
                                  ? 'hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40'
                                  : 'hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-950/40'
                              }`}
                            >
                              {c.estado === 'ACTIVA' ? <Ban className="h-3.5 w-3.5" /> : <PlayCircle className="h-3.5 w-3.5" />}
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>

                    {abierta === c.id_cuenta && (
                      <tr className="bg-slate-50/60 dark:bg-slate-800/30">
                        <td colSpan={7} className="px-10 py-4">
                          {!detalle[c.id_cuenta] ? (
                            <p className="text-slate-400">Cargando...</p>
                          ) : (
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                              <div>
                                <p className="mb-2 flex items-center gap-1.5 font-bold text-slate-700 dark:text-slate-200">
                                  <Building2 className="h-3.5 w-3.5" /> Empresas
                                </p>
                                <ul className="space-y-1 text-slate-600 dark:text-slate-300">
                                  {detalle[c.id_cuenta].empresas.map((e) => (
                                    <li key={e.id_empresa}>
                                      {e.razon_social} <span className="font-mono text-slate-400">({e.ruc})</span>
                                      {e.es_principal && <span className="ml-1 text-[10px] font-bold text-emerald-600">Principal</span>}
                                    </li>
                                  ))}
                                </ul>
                              </div>
                              <div>
                                <p className="mb-2 flex items-center gap-1.5 font-bold text-slate-700 dark:text-slate-200">
                                  <Users className="h-3.5 w-3.5" /> Usuarios
                                </p>
                                <ul className="space-y-1 text-slate-600 dark:text-slate-300">
                                  {detalle[c.id_cuenta].usuarios.map((u) => (
                                    <li key={u.id_usuario}>
                                      {u.nombre_usuario} <span className="text-slate-400">· {u.email} · {u.rol}</span>
                                      {!u.activo && <span className="ml-1 text-[10px] font-bold text-rose-600">Inactivo</span>}
                                    </li>
                                  ))}
                                </ul>
                              </div>
                              {c.notas && (
                                <p className="md:col-span-2 text-slate-500 dark:text-slate-400">
                                  <strong>Notas:</strong> {c.notas}
                                </p>
                              )}
                            </div>
                          )}
                        </td>
                      </tr>
                    )}
                  </React.Fragment>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Crear / Editar */}
      {modal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-xs">
          <div className="relative my-8 w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <h3 className="text-base font-bold text-slate-900 dark:text-white">
                {modal === 'editar' ? `Editar cuenta: ${editando?.nombre}` : 'Nueva cuenta de cliente'}
              </h3>
              <button onClick={() => setModal(null)} className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                <X className="h-5 w-5" />
              </button>
            </div>

            {error && (
              <div className="mt-3 flex items-center gap-2 rounded-xl bg-rose-50 p-2.5 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>{error}</span>
              </div>
            )}

            <form onSubmit={handleSave} className="mt-4 space-y-5">
              {/* Cuenta y plan */}
              <div className="space-y-3">
                <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400">Cuenta y plan</p>
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <div>
                    <label className={labelClass}>Nombre de la cuenta *</label>
                    <input required maxLength={150} value={formData.nombre} onChange={(e) => setCampo('nombre', e.target.value)} placeholder="Ej. Ferretería Sur" className={inputClass} />
                  </div>
                  <div>
                    <label className={labelClass}>Plan</label>
                    <input maxLength={50} value={formData.plan} onChange={(e) => setCampo('plan', e.target.value)} placeholder="Ej. BÁSICO, PRO" className={inputClass} />
                  </div>
                </div>
                <div className="grid grid-cols-3 gap-3">
                  {[
                    ['max_empresas', 'Máx. empresas'],
                    ['max_usuarios', 'Máx. usuarios'],
                    ['max_almacenes', 'Máx. almacenes'],
                  ].map(([campo, label]) => (
                    <div key={campo}>
                      <label className={labelClass}>{label}</label>
                      <input type="number" min={1} value={formData[campo]} onChange={(e) => setCampo(campo, e.target.value)} placeholder="Sin límite" className={inputClass} />
                    </div>
                  ))}
                </div>
                <div>
                  <label className={labelClass}>Notas internas</label>
                  <textarea rows={2} value={formData.notas} onChange={(e) => setCampo('notas', e.target.value)} placeholder="Ej. Contrato anual, contacto de facturación..." className={inputClass} />
                </div>
              </div>

              {modal === 'crear' && (
                <>
                  {/* Empresa principal */}
                  <div className="space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400">Empresa principal</p>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                      <div>
                        <label className={labelClass}>Razón social *</label>
                        <input required maxLength={150} value={formData.empresa.razon_social} onChange={(e) => setAnidado('empresa', 'razon_social', e.target.value)} className={inputClass} />
                      </div>
                      <div>
                        <label className={labelClass}>RUC (11 dígitos) *</label>
                        <input required minLength={11} maxLength={11} value={formData.empresa.ruc} onChange={(e) => setAnidado('empresa', 'ruc', e.target.value)} className={`${inputClass} font-mono`} />
                      </div>
                      <div>
                        <label className={labelClass}>Dirección fiscal</label>
                        <input maxLength={255} value={formData.empresa.direccion} onChange={(e) => setAnidado('empresa', 'direccion', e.target.value)} className={inputClass} />
                      </div>
                      <div>
                        <label className={labelClass}>Correo de la empresa</label>
                        <input type="email" maxLength={100} value={formData.empresa.email} onChange={(e) => setAnidado('empresa', 'email', e.target.value)} className={inputClass} />
                      </div>
                    </div>
                  </div>

                  {/* Administrador */}
                  <div className="space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <p className="text-[11px] font-bold uppercase tracking-wider text-slate-400">Administrador de la cuenta</p>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                      <div>
                        <label className={labelClass}>Nombre *</label>
                        <input required maxLength={100} value={formData.admin.nombre_usuario} onChange={(e) => setAnidado('admin', 'nombre_usuario', e.target.value)} className={inputClass} />
                      </div>
                      <div>
                        <label className={labelClass}>Correo (usuario) *</label>
                        <input required type="email" maxLength={100} value={formData.admin.email} onChange={(e) => setAnidado('admin', 'email', e.target.value)} className={inputClass} />
                      </div>
                      <div>
                        <label className={labelClass}>Contraseña temporal *</label>
                        <div className="flex gap-1">
                          <input required minLength={8} value={formData.admin.password} onChange={(e) => setAnidado('admin', 'password', e.target.value)} className={`${inputClass} font-mono`} />
                          <button
                            type="button"
                            onClick={() => setAnidado('admin', 'password', generarPassword())}
                            title="Generar otra"
                            className="rounded-xl border border-slate-200 px-2 text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                          >
                            <KeyRound className="h-3.5 w-3.5" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </>
              )}

              <div className="flex justify-end gap-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setModal(null)}
                  className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50"
                >
                  {saving ? 'Guardando...' : modal === 'editar' ? 'Guardar cambios' : 'Crear cuenta'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
