import React, { useState, useEffect, useMemo } from 'react';
import {
  Users,
  Plus,
  Search,
  Building2,
  Mail,
  Phone,
  MapPin,
  Edit2,
  Trash2,
  X,
  AlertCircle,
  UserCheck,
  LayoutGrid,
  List,
  Headset,
  Copy,
  Check,
  User,
  ExternalLink,
  ShieldCheck,
  Sparkles,
} from 'lucide-react';
import api from '../api/client';

// Colores consistentes según el nombre del cliente
const AVATAR_GRADIENTS = [
  'from-indigo-500 to-purple-600',
  'from-blue-500 to-cyan-600',
  'from-emerald-500 to-teal-600',
  'from-amber-500 to-orange-600',
  'from-rose-500 to-pink-600',
  'from-violet-500 to-fuchsia-600',
];

function getInitials(name) {
  if (!name) return 'CL';
  // Limpiar términos societarios comunes para obtener iniciales reales
  const cleaned = name
    .replace(/\b(S\.?A\.?C\.?|E\.?I\.?R\.?L\.?|S\.?A\.?|S\.?R\.?L\.?|S\.?A\.?A\.?)\b/gi, '')
    .trim();
  const words = cleaned.split(/\s+/).filter(Boolean);
  if (words.length === 1) return words[0].slice(0, 2).toUpperCase();
  return (words[0][0] + (words[1] ? words[1][0] : '')).toUpperCase();
}

function getAvatarGradient(name) {
  if (!name) return AVATAR_GRADIENTS[0];
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  const index = Math.abs(hash) % AVATAR_GRADIENTS.length;
  return AVATAR_GRADIENTS[index];
}

export default function ClientesPage() {
  const [clientes, setClientes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [activeFilter, setActiveFilter] = useState('ALL'); // ALL, HELPDESK, RUC, DNI
  const [viewMode, setViewMode] = useState('cards'); // 'cards' | 'table'
  const [copiedId, setCopiedId] = useState(null);

  // Modal Crear / Editar
  const [modalOpen, setModalOpen] = useState(false);
  const [editingClient, setEditingClient] = useState(null);
  const [formData, setFormData] = useState({
    tipo_documento: 'RUC',
    num_documento: '',
    razon_social: '',
    direccion: '',
    email: '',
    telefono: '',
    atencion: '',
    es_cliente_helpdesk: false,
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  // Modal Confirmar Eliminación
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchClientes = async () => {
    setLoading(true);
    try {
      const res = await api.get('/clientes', { params: { search } });
      if (res.data?.data) {
        setClientes(res.data.data.data || res.data.data);
      }
    } catch (e) {
      console.error('Error al cargar clientes:', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchClientes();
    }, 300);
    return () => clearTimeout(timer);
  }, [search]);

  // KPIs calculados
  const stats = useMemo(() => {
    const total = clientes.length;
    const helpdesk = clientes.filter((c) => c.es_cliente_helpdesk).length;
    const rucCount = clientes.filter((c) => c.tipo_documento === 'RUC').length;
    const conContacto = clientes.filter(
      (c) => c.atencion && c.atencion.trim() !== '' && c.atencion.trim() !== '--'
    ).length;
    return { total, helpdesk, rucCount, conContacto };
  }, [clientes]);

  // Clientes filtrados por chip rápido
  const filteredClientes = useMemo(() => {
    return clientes.filter((c) => {
      if (activeFilter === 'HELPDESK') return c.es_cliente_helpdesk;
      if (activeFilter === 'RUC') return c.tipo_documento === 'RUC';
      if (activeFilter === 'DNI') return c.tipo_documento !== 'RUC';
      return true;
    });
  }, [clientes, activeFilter]);

  const handleCopy = (text, id) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 1800);
  };

  const handleOpenCreate = () => {
    setEditingClient(null);
    setFormData({
      tipo_documento: 'RUC',
      num_documento: '',
      razon_social: '',
      direccion: '',
      email: '',
      telefono: '',
      atencion: '',
      es_cliente_helpdesk: false,
    });
    setError('');
    setModalOpen(true);
  };

  const handleOpenEdit = (c) => {
    setEditingClient(c);
    setFormData({
      tipo_documento: c.tipo_documento || 'RUC',
      num_documento: c.num_documento || '',
      razon_social: c.razon_social || '',
      direccion: c.direccion || '',
      email: c.email || '',
      telefono: c.telefono || '',
      atencion: c.atencion || '',
      es_cliente_helpdesk: !!c.es_cliente_helpdesk,
    });
    setError('');
    setModalOpen(true);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    try {
      if (editingClient) {
        await api.put(`/clientes/${editingClient.id_cliente}`, formData);
      } else {
        await api.post('/clientes', formData);
      }
      setModalOpen(false);
      fetchClientes();
    } catch (err) {
      setError(err.response?.data?.message || 'Error al guardar el cliente.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await api.delete(`/clientes/${deleteTarget.id_cliente}`);
      setDeleteTarget(null);
      fetchClientes();
    } catch (err) {
      alert(err.response?.data?.message || 'No se pudo eliminar el cliente.');
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* 1. Header Principal */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div className="flex items-center gap-2.5">
            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
              Directorio de Clientes
            </h1>
            <span className="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
              {stats.total} {stats.total === 1 ? 'cliente' : 'clientes'}
            </span>
          </div>
          <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
            Base de datos corporativa de clientes, razones sociales y contactos autorizados.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 active:scale-98 transition-all"
        >
          <Plus className="h-4 w-4" />
          <span>Registrar Nuevo Cliente</span>
        </button>
      </div>

      {/* 2. Tarjetas de Métricas Rápidas (KPIs) */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {/* Total Clientes */}
        <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
              Total Clientes
            </span>
            <div className="rounded-lg bg-indigo-50 p-1.5 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
              <Users className="h-4 w-4" />
            </div>
          </div>
          <div className="mt-2 flex items-baseline gap-2">
            <span className="text-2xl font-bold text-slate-900 dark:text-white">
              {stats.total}
            </span>
            <span className="text-[11px] text-slate-400">activos</span>
          </div>
        </div>

        {/* Clientes Helpdesk */}
        <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
              Helpdesk VIP
            </span>
            <div className="rounded-lg bg-emerald-50 p-1.5 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
              <Headset className="h-4 w-4" />
            </div>
          </div>
          <div className="mt-2 flex items-baseline gap-2">
            <span className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {stats.helpdesk}
            </span>
            <span className="text-[11px] text-slate-400">con soporte</span>
          </div>
        </div>

        {/* Empresas con RUC */}
        <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
              Personas Jurídicas
            </span>
            <div className="rounded-lg bg-blue-50 p-1.5 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400">
              <Building2 className="h-4 w-4" />
            </div>
          </div>
          <div className="mt-2 flex items-baseline gap-2">
            <span className="text-2xl font-bold text-slate-900 dark:text-white">
              {stats.rucCount}
            </span>
            <span className="text-[11px] text-slate-400">con RUC</span>
          </div>
        </div>

        {/* Contactos Asignados */}
        <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-medium text-slate-500 dark:text-slate-400">
              Contacto Directo
            </span>
            <div className="rounded-lg bg-amber-50 p-1.5 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
              <UserCheck className="h-4 w-4" />
            </div>
          </div>
          <div className="mt-2 flex items-baseline gap-2">
            <span className="text-2xl font-bold text-slate-900 dark:text-white">
              {stats.conContacto}
            </span>
            <span className="text-[11px] text-slate-400">registrados</span>
          </div>
        </div>
      </div>

      {/* 3. Barra de Controles: Búsqueda, Filtros y Toggle de Vista */}
      <div className="rounded-2xl border border-slate-200/80 bg-white p-3 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          {/* Input de Búsqueda */}
          <div className="relative flex-1">
            <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Buscar por Razón Social, RUC, DNI, correo o contacto..."
              className="w-full rounded-xl border border-slate-200 bg-slate-50/80 py-2 pl-10 pr-9 text-xs text-slate-900 placeholder-slate-400 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800/80 dark:text-white dark:focus:bg-slate-800"
            />
            {search && (
              <button
                onClick={() => setSearch('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
              >
                <X className="h-3.5 w-3.5" />
              </button>
            )}
          </div>

          {/* Filtros Rápidos (Chips) & Toggle de Vista */}
          <div className="flex flex-wrap items-center gap-2">
            <div className="flex items-center rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
              <button
                onClick={() => setActiveFilter('ALL')}
                className={`rounded-lg px-2.5 py-1 text-xs font-medium transition-all ${
                  activeFilter === 'ALL'
                    ? 'bg-white text-slate-900 shadow-xs font-semibold dark:bg-slate-700 dark:text-white'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                }`}
              >
                Todos ({clientes.length})
              </button>
              <button
                onClick={() => setActiveFilter('HELPDESK')}
                className={`flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium transition-all ${
                  activeFilter === 'HELPDESK'
                    ? 'bg-white text-emerald-600 shadow-xs font-semibold dark:bg-slate-700 dark:text-emerald-400'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                }`}
              >
                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                Helpdesk ({stats.helpdesk})
              </button>
              <button
                onClick={() => setActiveFilter('RUC')}
                className={`rounded-lg px-2.5 py-1 text-xs font-medium transition-all ${
                  activeFilter === 'RUC'
                    ? 'bg-white text-indigo-600 shadow-xs font-semibold dark:bg-slate-700 dark:text-indigo-400'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                }`}
              >
                Solo RUC
              </button>
              <button
                onClick={() => setActiveFilter('DNI')}
                className={`rounded-lg px-2.5 py-1 text-xs font-medium transition-all ${
                  activeFilter === 'DNI'
                    ? 'bg-white text-slate-900 shadow-xs font-semibold dark:bg-slate-700 dark:text-white'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                }`}
              >
                DNI / Otros
              </button>
            </div>

            {/* Alternar Cuadrícula vs Tabla */}
            <div className="flex items-center rounded-xl border border-slate-200 bg-slate-50 p-0.5 dark:border-slate-700 dark:bg-slate-800">
              <button
                onClick={() => setViewMode('cards')}
                title="Vista en tarjetas"
                className={`rounded-lg p-1.5 transition-all ${
                  viewMode === 'cards'
                    ? 'bg-white text-indigo-600 shadow-xs dark:bg-slate-700 dark:text-indigo-400'
                    : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'
                }`}
              >
                <LayoutGrid className="h-4 w-4" />
              </button>
              <button
                onClick={() => setViewMode('table')}
                title="Vista en lista / tabla"
                className={`rounded-lg p-1.5 transition-all ${
                  viewMode === 'table'
                    ? 'bg-white text-indigo-600 shadow-xs dark:bg-slate-700 dark:text-indigo-400'
                    : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'
                }`}
              >
                <List className="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* 4. Contenido Principal: Grilla de Tarjetas o Tabla */}
      {loading ? (
        <div className="flex flex-col items-center justify-center py-20 text-slate-400">
          <div className="h-8 w-8 animate-spin rounded-full border-3 border-indigo-600 border-t-transparent" />
          <span className="mt-3 text-xs font-medium">Cargando directorio de clientes...</span>
        </div>
      ) : filteredClientes.length === 0 ? (
        <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900">
          <div className="rounded-2xl bg-indigo-50 p-3 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
            <Users className="h-6 w-6" />
          </div>
          <h3 className="mt-3 text-sm font-bold text-slate-800 dark:text-white">
            No se encontraron clientes
          </h3>
          <p className="mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400">
            {search
              ? 'No hay registros que coincidan con los criterios de búsqueda.'
              : 'Empieza registrando tu primer cliente en el sistema.'}
          </p>
          {search ? (
            <button
              onClick={() => {
                setSearch('');
                setActiveFilter('ALL');
              }}
              className="mt-4 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
            >
              Limpiar filtros de búsqueda
            </button>
          ) : (
            <button
              onClick={handleOpenCreate}
              className="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700"
            >
              + Registrar Cliente
            </button>
          )}
        </div>
      ) : viewMode === 'cards' ? (
        /* VISTA TARJETAS (CARDS) */
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {filteredClientes.map((c) => {
            const initials = getInitials(c.razon_social);
            const gradient = getAvatarGradient(c.razon_social);
            const hasContact =
              c.atencion && c.atencion.trim() !== '' && c.atencion.trim() !== '--';

            return (
              <div
                key={c.id_cliente}
                className="group relative flex flex-col justify-between rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/50"
              >
                <div>
                  {/* Header de la Tarjeta con Avatar */}
                  <div className="flex items-start gap-3">
                    {/* Avatar corporativo con iniciales */}
                    <div
                      className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br ${gradient} text-xs font-extrabold text-white shadow-xs`}
                    >
                      {initials}
                    </div>

                    {/* Título & Badge de Documento */}
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-1.5">
                        <span className="inline-flex items-center rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                          {c.tipo_documento}
                        </span>
                        <button
                          onClick={() => handleCopy(c.num_documento, c.id_cliente)}
                          title="Copiar número de documento"
                          className="inline-flex items-center gap-1 font-mono text-xs font-semibold text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-300 transition-colors"
                        >
                          <span>{c.num_documento}</span>
                          {copiedId === c.id_cliente ? (
                            <Check className="h-3 w-3 text-emerald-500" />
                          ) : (
                            <Copy className="h-2.5 w-2.5 opacity-60 hover:opacity-100" />
                          )}
                        </button>
                      </div>

                      <h3
                        title={c.razon_social}
                        className="mt-1 line-clamp-2 text-xs font-bold leading-snug text-slate-900 group-hover:text-indigo-600 transition-colors dark:text-white dark:group-hover:text-indigo-400"
                      >
                        {c.razon_social}
                      </h3>
                    </div>

                    {/* Botones de Acción */}
                    <div className="flex items-center gap-1">
                      <button
                        onClick={() => handleOpenEdit(c)}
                        title="Editar cliente"
                        className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800 dark:hover:text-indigo-400 transition-colors"
                      >
                        <Edit2 className="h-3.5 w-3.5" />
                      </button>
                      <button
                        onClick={() => setDeleteTarget(c)}
                        title="Eliminar cliente"
                        className="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition-colors"
                      >
                        <Trash2 className="h-3.5 w-3.5" />
                      </button>
                    </div>
                  </div>

                  {/* Cuerpo con Información de Contacto */}
                  <div className="mt-4 space-y-2 border-t border-slate-100 pt-3 text-xs text-slate-600 dark:border-slate-800/80 dark:text-slate-400">
                    {/* Persona de Contacto */}
                    <div className="flex items-center gap-2">
                      <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <User className="h-3 w-3" />
                      </div>
                      <span className="truncate">
                        {hasContact ? (
                          <span>
                            <strong className="font-semibold text-slate-700 dark:text-slate-300">
                              {c.atencion}
                            </strong>
                          </span>
                        ) : (
                          <span className="italic text-slate-400">Sin contacto asignado</span>
                        )}
                      </span>
                    </div>

                    {/* Email */}
                    {c.email ? (
                      <div className="flex items-center gap-2">
                        <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                          <Mail className="h-3 w-3" />
                        </div>
                        <a
                          href={`mailto:${c.email}`}
                          title={`Enviar correo a ${c.email}`}
                          className="truncate hover:text-indigo-600 hover:underline dark:hover:text-indigo-400"
                        >
                          {c.email}
                        </a>
                      </div>
                    ) : null}

                    {/* Teléfono */}
                    {c.telefono ? (
                      <div className="flex items-center gap-2">
                        <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                          <Phone className="h-3 w-3" />
                        </div>
                        <a
                          href={`tel:${c.telefono}`}
                          title={`Llamar a ${c.telefono}`}
                          className="font-mono text-slate-700 hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-400"
                        >
                          {c.telefono}
                        </a>
                      </div>
                    ) : null}

                    {/* Dirección */}
                    {c.direccion ? (
                      <div className="flex items-center gap-2">
                        <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                          <MapPin className="h-3 w-3" />
                        </div>
                        <span title={c.direccion} className="truncate text-[11px]">
                          {c.direccion}
                        </span>
                      </div>
                    ) : null}
                  </div>
                </div>

                {/* Footer Estandarizado de la Tarjeta */}
                <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
                  {c.es_cliente_helpdesk ? (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                      <span className="relative flex h-1.5 w-1.5">
                        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                      </span>
                      Cliente Corporativo Helpdesk
                    </span>
                  ) : (
                    <span className="inline-flex items-center gap-1 text-[10px] font-medium text-slate-400">
                      <span className="h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-slate-600" />
                      Cliente Estándar
                    </span>
                  )}

                  <button
                    onClick={() => handleOpenEdit(c)}
                    className="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                  >
                    Detalles →
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      ) : (
        /* VISTA TABLA (TABLE) */
        <div className="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-slate-100 bg-slate-50/75 text-[11px] font-semibold text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                <tr>
                  <th className="py-3 pl-4 pr-3">Cliente / Razón Social</th>
                  <th className="px-3 py-3">Documento</th>
                  <th className="px-3 py-3">Contacto Principal</th>
                  <th className="px-3 py-3">Correo & Teléfono</th>
                  <th className="px-3 py-3">Dirección</th>
                  <th className="px-3 py-3">Tipo / Estado</th>
                  <th className="py-3 pl-3 pr-4 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {filteredClientes.map((c) => {
                  const initials = getInitials(c.razon_social);
                  const gradient = getAvatarGradient(c.razon_social);

                  return (
                    <tr
                      key={c.id_cliente}
                      className="hover:bg-slate-50/80 transition-colors dark:hover:bg-slate-800/50"
                    >
                      {/* Cliente / Razón Social */}
                      <td className="py-3.5 pl-4 pr-3">
                        <div className="flex items-center gap-3">
                          <div
                            className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br ${gradient} text-xs font-bold text-white shadow-xs`}
                          >
                            {initials}
                          </div>
                          <div>
                            <div className="font-bold text-slate-900 dark:text-white">
                              {c.razon_social}
                            </div>
                            <div className="text-[10px] text-slate-400">
                              ID: #{c.id_cliente}
                            </div>
                          </div>
                        </div>
                      </td>

                      {/* Documento */}
                      <td className="whitespace-nowrap px-3 py-3.5">
                        <div className="flex items-center gap-1.5">
                          <span className="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {c.tipo_documento}
                          </span>
                          <span className="font-mono text-slate-600 dark:text-slate-300">
                            {c.num_documento}
                          </span>
                        </div>
                      </td>

                      {/* Contacto */}
                      <td className="px-3 py-3.5 text-slate-700 dark:text-slate-300">
                        {c.atencion && c.atencion !== '--' ? (
                          <div className="flex items-center gap-1.5 font-medium">
                            <User className="h-3.5 w-3.5 text-slate-400" />
                            <span>{c.atencion}</span>
                          </div>
                        ) : (
                          <span className="italic text-slate-400">No asignado</span>
                        )}
                      </td>

                      {/* Correo y Teléfono */}
                      <td className="px-3 py-3.5">
                        <div className="space-y-0.5">
                          {c.email && (
                            <a
                              href={`mailto:${c.email}`}
                              className="flex items-center gap-1.5 text-slate-600 hover:text-indigo-600 hover:underline dark:text-slate-400 dark:hover:text-indigo-400"
                            >
                              <Mail className="h-3 w-3 text-slate-400" />
                              <span className="truncate max-w-[160px]">{c.email}</span>
                            </a>
                          )}
                          {c.telefono && (
                            <a
                              href={`tel:${c.telefono}`}
                              className="flex items-center gap-1.5 font-mono text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400"
                            >
                              <Phone className="h-3 w-3 text-slate-400" />
                              <span>{c.telefono}</span>
                            </a>
                          )}
                        </div>
                      </td>

                      {/* Dirección */}
                      <td className="px-3 py-3.5 text-slate-600 dark:text-slate-400">
                        <span title={c.direccion} className="truncate max-w-[180px] block text-[11px]">
                          {c.direccion || '-'}
                        </span>
                      </td>

                      {/* Estado */}
                      <td className="whitespace-nowrap px-3 py-3.5">
                        {c.es_cliente_helpdesk ? (
                          <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                            Helpdesk
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                            Estándar
                          </span>
                        )}
                      </td>

                      {/* Acciones */}
                      <td className="whitespace-nowrap py-3.5 pl-3 pr-4 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <button
                            onClick={() => handleOpenEdit(c)}
                            title="Editar cliente"
                            className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800 dark:hover:text-indigo-400"
                          >
                            <Edit2 className="h-3.5 w-3.5" />
                          </button>
                          <button
                            onClick={() => setDeleteTarget(c)}
                            title="Eliminar cliente"
                            className="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                          >
                            <Trash2 className="h-3.5 w-3.5" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* 5. Modal Crear / Editar Cliente */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
          <div className="relative w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 animate-in fade-in zoom-in-95 duration-150">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <div className="flex items-center gap-2.5">
                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                  <Building2 className="h-5 w-5" />
                </div>
                <div>
                  <h3 className="text-base font-bold text-slate-900 dark:text-white">
                    {editingClient ? 'Editar Cliente' : 'Registrar Nuevo Cliente'}
                  </h3>
                  <p className="text-[11px] text-slate-500 dark:text-slate-400">
                    {editingClient
                      ? 'Actualiza los datos corporativos y de contacto'
                      : 'Ingresa la información fiscal y comercial del cliente'}
                  </p>
                </div>
              </div>
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
              <div className="grid grid-cols-3 gap-2">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Tipo Doc *
                  </label>
                  <select
                    value={formData.tipo_documento}
                    onChange={(e) => setFormData({ ...formData, tipo_documento: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs font-medium text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="RUC">RUC</option>
                    <option value="DNI">DNI</option>
                    <option value="CE">C. Extranjería</option>
                    <option value="PASAPORTE">Pasaporte</option>
                    <option value="OTROS">Otros</option>
                  </select>
                </div>
                <div className="col-span-2">
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Número de Documento *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.num_documento}
                    onChange={(e) => setFormData({ ...formData, num_documento: e.target.value })}
                    placeholder="Ej. 20100199743"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 font-mono focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Razón Social / Nombre Completo *
                </label>
                <input
                  type="text"
                  required
                  value={formData.razon_social}
                  onChange={(e) => setFormData({ ...formData, razon_social: e.target.value })}
                  placeholder="Ej. INCA TOPS S.A."
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Persona de Contacto / Atención
                </label>
                <input
                  type="text"
                  value={formData.atencion}
                  onChange={(e) => setFormData({ ...formData, atencion: e.target.value })}
                  placeholder="Ej. Alex Carcasi"
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Correo Electrónico
                  </label>
                  <input
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    placeholder="contacto@cliente.com"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
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
                    placeholder="999 999 999"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
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
                  placeholder="Av. Principal Nro 123, Arequipa"
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div className="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3 dark:border-slate-700/80 dark:bg-slate-800/60">
                <label className="flex items-start gap-2.5 cursor-pointer">
                  <input
                    type="checkbox"
                    id="chkHelpdesk"
                    checked={formData.es_cliente_helpdesk}
                    onChange={(e) =>
                      setFormData({ ...formData, es_cliente_helpdesk: e.target.checked })
                    }
                    className="mt-0.5 h-4 w-4 rounded-md border-slate-300 text-indigo-600 focus:ring-indigo-500"
                  />
                  <div>
                    <span className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                      Habilitar como Cliente Corporativo Helpdesk
                    </span>
                    <p className="text-[11px] text-slate-500 dark:text-slate-400">
                      Permite asociar tickets de soporte, SLA prioritario y seguimiento técnico directo.
                    </p>
                  </div>
                </label>
              </div>

              <div className="flex justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
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
                  className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                >
                  {saving ? 'Guardando...' : editingClient ? 'Guardar Cambios' : 'Registrar Cliente'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* 6. Modal de Confirmación de Eliminación */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
          <div className="relative w-full max-w-sm rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 animate-in fade-in zoom-in-95 duration-150">
            <div className="flex flex-col items-center text-center">
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                <Trash2 className="h-6 w-6" />
              </div>
              <h3 className="mt-3 text-sm font-bold text-slate-900 dark:text-white">
                ¿Eliminar este cliente?
              </h3>
              <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Estás a punto de eliminar a{' '}
                <strong className="text-slate-700 dark:text-slate-200">
                  {deleteTarget.razon_social}
                </strong>
                . Esta acción no se puede deshacer.
              </p>

              <div className="mt-5 flex w-full justify-center gap-2.5">
                <button
                  type="button"
                  onClick={() => setDeleteTarget(null)}
                  disabled={deleting}
                  className="flex-1 rounded-xl border border-slate-200 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                  Cancelar
                </button>
                <button
                  type="button"
                  onClick={handleDelete}
                  disabled={deleting}
                  className="flex-1 rounded-xl bg-rose-600 py-2 text-xs font-semibold text-white shadow-xs hover:bg-rose-700 disabled:opacity-50 transition-colors"
                >
                  {deleting ? 'Eliminando...' : 'Sí, eliminar'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
