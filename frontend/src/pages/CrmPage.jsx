import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Target,
  Plus,
  Search,
  CheckCircle,
  XCircle,
  Clock,
  DollarSign,
  Building,
  AlertCircle,
  X,
  FileText,
  TrendingUp,
  Calendar,
  MessageSquare,
  Phone,
  Video,
  Mail,
  ChevronRight,
  ChevronLeft,
  ArrowRight,
  Filter,
  LayoutGrid,
  List,
  Flame,
  Award,
} from 'lucide-react';
import api from '../api/client';
import { useCompany } from '../context/CompanyContext';
import { useAuth } from '../context/AuthContext';

const ETAPAS = [
  { id: 'PROSPECTO', label: 'Prospecto', color: 'border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 text-slate-700 dark:text-slate-300', headerBg: 'bg-slate-100 dark:bg-slate-800' },
  { id: 'CONTACTADO', label: 'Contactado', color: 'border-blue-300 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300', headerBg: 'bg-blue-100/60 dark:bg-blue-900/40' },
  { id: 'PROPUESTA', label: 'Propuesta / Cotización', color: 'border-purple-300 dark:border-purple-800 bg-purple-50/50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-300', headerBg: 'bg-purple-100/60 dark:bg-purple-900/40' },
  { id: 'NEGOCIACION', label: 'Negociación', color: 'border-amber-300 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-300', headerBg: 'bg-amber-100/60 dark:bg-amber-900/40' },
  { id: 'GANADO', label: 'Cerrado Ganado', color: 'border-emerald-300 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300', headerBg: 'bg-emerald-100/60 dark:bg-emerald-900/40' },
  { id: 'PERDIDO', label: 'Perdido', color: 'border-rose-300 dark:border-rose-800 bg-rose-50/50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-300', headerBg: 'bg-rose-100/60 dark:bg-rose-900/40' },
];

export default function CrmPage() {
  const { activeEmpresa } = useCompany();
  const { user } = useAuth();
  const navigate = useNavigate();

  const [oportunidades, setOportunidades] = useState([]);
  const [metricas, setMetricas] = useState({
    total_pipeline: 0,
    total_ganado: 0,
    total_perdido: 0,
    conteo_abiertas: 0,
    conteo_ganadas: 0,
  });
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [prioridadFilter, setPrioridadFilter] = useState('');
  const [viewMode, setViewMode] = useState('kanban'); // 'kanban' | 'list'

  // Clientes para formularios
  const [clientes, setClientes] = useState([]);

  // Modal Nueva Oportunidad
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [formData, setFormData] = useState({
    id_empresa: activeEmpresa?.id_empresa || '',
    id_cliente: '',
    titulo: '',
    descripcion: '',
    valor_estimado: '',
    moneda: 'USD',
    etapa: 'PROSPECTO',
    prioridad: 'MEDIA',
    fecha_cierre_estimada: '',
  });
  const [savingOportunidad, setSavingOportunidad] = useState(false);
  const [createError, setCreateError] = useState('');

  // Modal Detalle y Actividades
  const [selectedOp, setSelectedOp] = useState(null);
  const [loadingDetail, setLoadingDetail] = useState(false);
  const [newActivity, setNewActivity] = useState({
    tipo: 'NOTA',
    titulo: '',
    descripcion: '',
  });
  const [savingActivity, setSavingActivity] = useState(false);

  const fetchOportunidades = async () => {
    setLoading(true);
    try {
      const res = await api.get('/crm/oportunidades', {
        params: {
          search,
          prioridad: prioridadFilter || undefined,
          id_empresa: activeEmpresa?.id_empresa || undefined,
        },
      });
      if (res.data?.status === 'success') {
        setOportunidades(res.data.data || []);
        if (res.data.metricas) setMetricas(res.data.metricas);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const loadClientes = async () => {
    try {
      const res = await api.get('/clientes', { params: { per_page: 150 } });
      if (res.data?.data) {
        setClientes(res.data.data.data || res.data.data);
      }
    } catch (e) {
      console.error(e);
    }
  };

  useEffect(() => {
    loadClientes();
  }, []);

  useEffect(() => {
    fetchOportunidades();
  }, [search, prioridadFilter, activeEmpresa]);

  const handleOpenCreate = () => {
    setFormData({
      id_empresa: activeEmpresa?.id_empresa || '',
      id_cliente: '',
      titulo: '',
      descripcion: '',
      valor_estimado: '',
      moneda: 'USD',
      etapa: 'PROSPECTO',
      prioridad: 'MEDIA',
      fecha_cierre_estimada: '',
    });
    setCreateError('');
    setCreateModalOpen(true);
  };

  const handleSaveOportunidad = async (e) => {
    e.preventDefault();
    setSavingOportunidad(true);
    setCreateError('');

    try {
      await api.post('/crm/oportunidades', {
        ...formData,
        id_empresa: activeEmpresa?.id_empresa || formData.id_empresa,
      });
      setCreateModalOpen(false);
      fetchOportunidades();
    } catch (err) {
      setCreateError(
        err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(' ')
          : err.response?.data?.message || 'Error al guardar la oportunidad.'
      );
    } finally {
      setSavingOportunidad(false);
    }
  };

  const handleStageChange = async (id, newEtapa) => {
    try {
      await api.patch(`/crm/oportunidades/${id}/etapa`, { etapa: newEtapa });
      fetchOportunidades();
      if (selectedOp && selectedOp.id_oportunidad === id) {
        openDetailModal(id);
      }
    } catch (err) {
      alert('Error al mover de etapa.');
    }
  };

  const openDetailModal = async (id) => {
    setLoadingDetail(true);
    try {
      const res = await api.get(`/crm/oportunidades/${id}`);
      if (res.data?.data) {
        setSelectedOp(res.data.data);
      }
    } catch (e) {
      alert('Error al cargar la oportunidad');
    } finally {
      setLoadingDetail(false);
    }
  };

  const handleAddActivity = async (e) => {
    e.preventDefault();
    if (!selectedOp || !newActivity.titulo) return;
    setSavingActivity(true);
    try {
      await api.post('/crm/actividades', {
        id_empresa: selectedOp.id_empresa,
        id_cliente: selectedOp.id_cliente,
        id_oportunidad: selectedOp.id_oportunidad,
        tipo: newActivity.tipo,
        titulo: newActivity.titulo,
        descripcion: newActivity.descripcion,
      });
      setNewActivity({ tipo: 'NOTA', titulo: '', descripcion: '' });
      openDetailModal(selectedOp.id_oportunidad);
    } catch (err) {
      alert('Error al registrar actividad.');
    } finally {
      setSavingActivity(false);
    }
  };

  // Icono según tipo de actividad
  const getActivityIcon = (tipo) => {
    switch (tipo) {
      case 'LLAMADA': return <Phone className="h-3.5 w-3.5 text-blue-500" />;
      case 'REUNION': return <Video className="h-3.5 w-3.5 text-purple-500" />;
      case 'WHATSAPP': return <MessageSquare className="h-3.5 w-3.5 text-emerald-500" />;
      case 'CORREO': return <Mail className="h-3.5 w-3.5 text-amber-500" />;
      default: return <FileText className="h-3.5 w-3.5 text-slate-500" />;
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
            <Target className="h-7 w-7 text-indigo-600 dark:text-indigo-400" />
            <span>Mini-CRM & Pipeline Comercial</span>
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Seguimiento de prospectos, valor estimado de tratos y bitácora de interacciones con clientes.
          </p>
        </div>

        <div className="flex items-center gap-2">
          {/* View toggle */}
          <div className="flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60">
            <button
              onClick={() => setViewMode('kanban')}
              className={`flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition-colors ${
                viewMode === 'kanban'
                  ? 'bg-white shadow-xs text-slate-900 dark:bg-slate-700 dark:text-white'
                  : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'
              }`}
            >
              <LayoutGrid className="h-3.5 w-3.5" />
              <span>Embudo</span>
            </button>
            <button
              onClick={() => setViewMode('list')}
              className={`flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition-colors ${
                viewMode === 'list'
                  ? 'bg-white shadow-xs text-slate-900 dark:bg-slate-700 dark:text-white'
                  : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'
              }`}
            >
              <List className="h-3.5 w-3.5" />
              <span>Lista</span>
            </button>
          </div>

          <button
            onClick={handleOpenCreate}
            className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
          >
            <Plus className="h-4 w-4" />
            <span>Nueva Oportunidad</span>
          </button>
        </div>
      </div>

      {/* KPI Pipeline Cards */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">
              Valor en Pipeline Activo
            </span>
            <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
              <TrendingUp className="h-4 w-4" />
            </div>
          </div>
          <p className="mt-2 text-xl font-bold tracking-tight text-slate-900 dark:text-white font-mono">
            $ {Number(metricas.total_pipeline).toLocaleString('en-US', { minimumFractionDigits: 2 })}
          </p>
          <span className="text-[10px] text-slate-400">
            {metricas.conteo_abiertas} tratos en negociación
          </span>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">
              Tratos Ganados
            </span>
            <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
              <Award className="h-4 w-4" />
            </div>
          </div>
          <p className="mt-2 text-xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
            $ {Number(metricas.total_ganado).toLocaleString('en-US', { minimumFractionDigits: 2 })}
          </p>
          <span className="text-[10px] text-emerald-600/80 font-medium">
            {metricas.conteo_ganadas} ventas cerradas con éxito
          </span>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">
              Tratos Desestimados / Perdidos
            </span>
            <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
              <XCircle className="h-4 w-4" />
            </div>
          </div>
          <p className="mt-2 text-xl font-bold tracking-tight text-slate-500 dark:text-slate-400 font-mono">
            $ {Number(metricas.total_perdido).toLocaleString('en-US', { minimumFractionDigits: 2 })}
          </p>
          <span className="text-[10px] text-slate-400">Oportunidades archivadas</span>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">
              Tasa de Éxito Estimada
            </span>
            <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
              <Flame className="h-4 w-4" />
            </div>
          </div>
          <p className="mt-2 text-xl font-bold tracking-tight text-amber-600 dark:text-amber-400 font-mono">
            {metricas.conteo_ganadas + metricas.conteo_abiertas > 0
              ? Math.round((metricas.conteo_ganadas / (metricas.conteo_ganadas + metricas.conteo_abiertas)) * 100)
              : 0}%
          </p>
          <span className="text-[10px] text-amber-600/80 font-medium">Conversión sobre tratos</span>
        </div>
      </div>

      {/* Filters bar */}
      <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            placeholder="Buscar por título de proyecto o cliente..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-4 text-xs text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-800 dark:bg-slate-800/50 dark:text-white"
          />
        </div>

        <div className="flex items-center gap-2">
          <select
            value={prioridadFilter}
            onChange={(e) => setPrioridadFilter(e.target.value)}
            className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300"
          >
            <option value="">Todas las prioridades</option>
            <option value="ALTA">Prioridad Alta</option>
            <option value="MEDIA">Prioridad Media</option>
            <option value="BAJA">Prioridad Baja</option>
          </select>
        </div>
      </div>

      {/* View Mode: KANBAN PIPELINE */}
      {viewMode === 'kanban' && (
        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3.5 items-start overflow-x-auto pb-4">
          {ETAPAS.map((etapa) => {
            const etapaOps = oportunidades.filter((op) => op.etapa === etapa.id);
            const etapaTotal = etapaOps.reduce((sum, op) => sum + (parseFloat(op.valor_estimado) || 0), 0);

            return (
              <div
                key={etapa.id}
                className="flex flex-col rounded-2xl border border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-900/60 p-2.5 min-w-[240px]"
              >
                {/* Column Header */}
                <div className={`rounded-xl p-2.5 mb-2.5 border border-slate-200/50 dark:border-slate-700/50 ${etapa.headerBg}`}>
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-slate-800 dark:text-slate-100">
                      {etapa.label}
                    </span>
                    <span className="rounded-md bg-white/80 dark:bg-slate-800 px-1.5 py-0.5 text-[10px] font-bold text-slate-700 dark:text-slate-300 shadow-2xs">
                      {etapaOps.length}
                    </span>
                  </div>
                  <div className="mt-1 text-[11px] font-mono font-semibold text-slate-600 dark:text-slate-300">
                    $ {etapaTotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}
                  </div>
                </div>

                {/* Cards Container */}
                <div className="space-y-2.5 min-h-[150px]">
                  {loading ? (
                    <div className="py-8 text-center text-[11px] text-slate-400">
                      Cargando tratos...
                    </div>
                  ) : etapaOps.length === 0 ? (
                    <div className="py-8 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl text-[10px] text-slate-400">
                      Sin tratos en esta etapa
                    </div>
                  ) : (
                    etapaOps.map((op) => (
                      <div
                        key={op.id_oportunidad}
                        className="group relative rounded-xl border border-slate-200 bg-white p-3 shadow-xs hover:shadow-md transition-all dark:border-slate-700/80 dark:bg-slate-800 hover:border-indigo-300 dark:hover:border-indigo-600 cursor-pointer"
                        onClick={() => openDetailModal(op.id_oportunidad)}
                      >
                        {/* Priority Badge */}
                        <div className="flex items-center justify-between mb-1.5">
                          <span
                            className={`text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded-md ${
                              op.prioridad === 'ALTA'
                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300'
                                : op.prioridad === 'MEDIA'
                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300'
                                : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
                            }`}
                          >
                            {op.prioridad}
                          </span>
                          <span className="text-[10px] font-bold text-slate-400">
                            {op.probabilidad}%
                          </span>
                        </div>

                        {/* Title */}
                        <h4 className="text-xs font-bold text-slate-900 dark:text-white line-clamp-2 leading-snug">
                          {op.titulo}
                        </h4>

                        {/* Client */}
                        <p className="mt-1 text-[11px] text-slate-500 dark:text-slate-400 truncate">
                          {op.cliente?.razon_social}
                        </p>

                        {/* Value and Expected Date */}
                        <div className="mt-2.5 flex items-center justify-between border-t border-slate-100 pt-2 dark:border-slate-700/60">
                          <span className="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                            {op.moneda} {Number(op.valor_estimado).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                          </span>
                          {op.fecha_cierre_estimada && (
                            <span className="flex items-center gap-1 text-[10px] text-slate-400 font-mono">
                              <Calendar className="h-3 w-3" />
                              {op.fecha_cierre_estimada}
                            </span>
                          )}
                        </div>

                        {/* Fast Move Buttons */}
                        <div
                          className="mt-2 flex items-center justify-between gap-1 pt-1 opacity-80 group-hover:opacity-100"
                          onClick={(e) => e.stopPropagation()}
                        >
                          <div className="flex items-center gap-1">
                            {etapa.id !== 'PROSPECTO' && (
                              <button
                                title="Retroceder etapa"
                                onClick={() => {
                                  const idx = ETAPAS.findIndex((e) => e.id === etapa.id);
                                  if (idx > 0) handleStageChange(op.id_oportunidad, ETAPAS[idx - 1].id);
                                }}
                                className="rounded-md p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700"
                              >
                                <ChevronLeft className="h-3.5 w-3.5" />
                              </button>
                            )}
                            {etapa.id !== 'GANADO' && etapa.id !== 'PERDIDO' && (
                              <button
                                title="Avanzar etapa"
                                onClick={() => {
                                  const idx = ETAPAS.findIndex((e) => e.id === etapa.id);
                                  if (idx < ETAPAS.length - 2) handleStageChange(op.id_oportunidad, ETAPAS[idx + 1].id);
                                }}
                                className="rounded-md p-1 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                              >
                                <ChevronRight className="h-3.5 w-3.5" />
                              </button>
                            )}
                          </div>

                          <div className="flex items-center gap-1">
                            {etapa.id !== 'GANADO' && (
                              <button
                                title="Marcar como GANADO"
                                onClick={() => handleStageChange(op.id_oportunidad, 'GANADO')}
                                className="rounded-md p-1 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40"
                              >
                                <CheckCircle className="h-3.5 w-3.5" />
                              </button>
                            )}
                            {etapa.id !== 'PERDIDO' && (
                              <button
                                title="Marcar como PERDIDO"
                                onClick={() => handleStageChange(op.id_oportunidad, 'PERDIDO')}
                                className="rounded-md p-1 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                              >
                                <XCircle className="h-3.5 w-3.5" />
                              </button>
                            )}
                          </div>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* View Mode: LIST / TABLE */}
      {viewMode === 'list' && (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                <tr>
                  <th className="py-3 px-4">Oportunidad</th>
                  <th className="py-3 px-4">Cliente</th>
                  <th className="py-3 px-4">Etapa</th>
                  <th className="py-3 px-4">Prioridad</th>
                  <th className="py-3 px-4">Cierre Previsto</th>
                  <th className="py-3 px-4 text-right">Valor Estimado</th>
                  <th className="py-3 px-4 text-center">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {oportunidades.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="py-10 text-center text-slate-400">
                      No se encontraron oportunidades en el pipeline.
                    </td>
                  </tr>
                ) : (
                  oportunidades.map((op) => (
                    <tr
                      key={op.id_oportunidad}
                      className="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors cursor-pointer"
                      onClick={() => openDetailModal(op.id_oportunidad)}
                    >
                      <td className="py-3 px-4">
                        <div className="font-bold text-slate-900 dark:text-white">{op.titulo}</div>
                        <div className="text-[10px] text-slate-400 line-clamp-1">{op.descripcion || 'Sin descripción'}</div>
                      </td>
                      <td className="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">
                        {op.cliente?.razon_social}
                      </td>
                      <td className="py-3 px-4">
                        <span className="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                          {op.etapa} ({op.probabilidad}%)
                        </span>
                      </td>
                      <td className="py-3 px-4">
                        <span
                          className={`inline-flex rounded-md px-2 py-0.5 text-[10px] font-extrabold ${
                            op.prioridad === 'ALTA'
                              ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                              : op.prioridad === 'MEDIA'
                              ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
                              : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                          }`}
                        >
                          {op.prioridad}
                        </span>
                      </td>
                      <td className="py-3 px-4 font-mono text-slate-500">
                        {op.fecha_cierre_estimada || '-'}
                      </td>
                      <td className="py-3 px-4 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">
                        {op.moneda} {Number(op.valor_estimado).toFixed(2)}
                      </td>
                      <td className="py-3 px-4 text-center" onClick={(e) => e.stopPropagation()}>
                        <button
                          onClick={() => openDetailModal(op.id_oportunidad)}
                          className="rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300"
                        >
                          Ver Bitácora
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Modal: Crear Nueva Oportunidad */}
      {createModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
          <div className="relative w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-8">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <div className="flex items-center gap-2">
                <Target className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                <h3 className="text-base font-bold text-slate-900 dark:text-white">
                  Registrar Trato / Oportunidad
                </h3>
              </div>
              <button
                onClick={() => setCreateModalOpen(false)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {createError && (
              <div className="mt-3 flex items-center gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>{createError}</span>
              </div>
            )}

            <form onSubmit={handleSaveOportunidad} className="mt-4 space-y-3.5">
              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Cliente *
                </label>
                <select
                  required
                  value={formData.id_cliente}
                  onChange={(e) => setFormData({ ...formData, id_cliente: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                >
                  <option value="">-- Seleccionar Cliente --</option>
                  {clientes.map((c) => (
                    <option key={c.id_cliente} value={c.id_cliente}>
                      {c.razon_social} ({c.num_documento})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Título del Trato / Proyecto *
                </label>
                <input
                  type="text"
                  required
                  placeholder="Ej. Desarrollo de App Móvil para Clientes"
                  value={formData.titulo}
                  onChange={(e) => setFormData({ ...formData, titulo: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Valor Estimado *
                  </label>
                  <input
                    type="number"
                    step="any"
                    min="0"
                    required
                    placeholder="2500.00"
                    value={formData.valor_estimado}
                    onChange={(e) => setFormData({ ...formData, valor_estimado: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-mono text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Moneda
                  </label>
                  <select
                    value={formData.moneda}
                    onChange={(e) => setFormData({ ...formData, moneda: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  >
                    <option value="USD">Dólares (USD - $)</option>
                    <option value="PEN">Soles (PEN - S/)</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Etapa Inicial
                  </label>
                  <select
                    value={formData.etapa}
                    onChange={(e) => setFormData({ ...formData, etapa: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  >
                    <option value="PROSPECTO">Prospecto (20%)</option>
                    <option value="CONTACTADO">Contactado (40%)</option>
                    <option value="PROPUESTA">Propuesta Enviada (60%)</option>
                    <option value="NEGOCIACION">En Negociación (80%)</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Prioridad
                  </label>
                  <select
                    value={formData.prioridad}
                    onChange={(e) => setFormData({ ...formData, prioridad: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  >
                    <option value="ALTA">Alta</option>
                    <option value="MEDIA">Media</option>
                    <option value="BAJA">Baja</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Fecha Prevista de Cierre
                </label>
                <input
                  type="date"
                  value={formData.fecha_cierre_estimada}
                  onChange={(e) => setFormData({ ...formData, fecha_cierre_estimada: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Descripción / Necesidades del Cliente
                </label>
                <textarea
                  rows="2"
                  placeholder="Detalles clave conversados en la primera interacción..."
                  value={formData.descripcion}
                  onChange={(e) => setFormData({ ...formData, descripcion: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                />
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setCreateModalOpen(false)}
                  className="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={savingOportunidad}
                  className="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                >
                  {savingOportunidad ? 'Guardando...' : 'Crear Oportunidad'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Detalle de Oportunidad y Bitácora de Actividades */}
      {selectedOp && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
          <div className="relative w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-8">
            <div className="flex items-start justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <div>
                <div className="flex items-center gap-2">
                  <span className="rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                    {selectedOp.etapa} ({selectedOp.probabilidad}%)
                  </span>
                  <span className="text-[10px] uppercase font-bold text-slate-400">
                    Prioridad: {selectedOp.prioridad}
                  </span>
                </div>
                <h3 className="mt-1 text-base font-bold text-slate-900 dark:text-white">
                  {selectedOp.titulo}
                </h3>
                <p className="text-xs text-slate-500 font-medium">
                  Cliente: <strong className="text-slate-800 dark:text-slate-200">{selectedOp.cliente?.razon_social}</strong> ({selectedOp.cliente?.num_documento})
                </p>
              </div>
              <button
                onClick={() => setSelectedOp(null)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {/* Quick Actions & Summary */}
            <div className="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
              <div>
                <span className="text-[10px] text-slate-400 block">Valor Estimado:</span>
                <span className="text-sm font-bold font-mono text-indigo-600 dark:text-indigo-400">
                  {selectedOp.moneda} {Number(selectedOp.valor_estimado).toFixed(2)}
                </span>
              </div>
              <div>
                <span className="text-[10px] text-slate-400 block">Cierre Previsto:</span>
                <span className="text-xs font-mono text-slate-700 dark:text-slate-300">
                  {selectedOp.fecha_cierre_estimada || 'Sin fecha fija'}
                </span>
              </div>
              <div>
                <span className="text-[10px] text-slate-400 block">Asesor Responsable:</span>
                <span className="text-xs font-medium text-slate-700 dark:text-slate-300">
                  {selectedOp.usuario?.nombre_completo || 'Tú'}
                </span>
              </div>
            </div>

            {/* Move stage bar */}
            <div className="mt-4">
              <label className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">
                Cambiar Etapa de Negociación:
              </label>
              <div className="flex flex-wrap gap-1.5">
                {ETAPAS.map((et) => (
                  <button
                    key={et.id}
                    onClick={() => handleStageChange(selectedOp.id_oportunidad, et.id)}
                    className={`rounded-xl px-2.5 py-1 text-xs font-bold transition-all ${
                      selectedOp.etapa === et.id
                        ? 'bg-indigo-600 text-white shadow-xs'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'
                    }`}
                  >
                    {et.label}
                  </button>
                ))}
              </div>
            </div>

            {/* Activities & History */}
            <div className="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
              <h4 className="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 mb-3 flex items-center justify-between">
                <span>Bitácora de Interacciones y Seguimiento</span>
                <span className="text-[10px] text-slate-400 font-normal">
                  {selectedOp.actividades?.length || 0} registros
                </span>
              </h4>

              {/* Add activity form */}
              <form onSubmit={handleAddActivity} className="rounded-2xl border border-slate-200 p-3 bg-white dark:border-slate-700 dark:bg-slate-800/80 mb-4 space-y-2.5">
                <div className="grid grid-cols-3 gap-2">
                  <select
                    value={newActivity.tipo}
                    onChange={(e) => setNewActivity({ ...newActivity, tipo: e.target.value })}
                    className="rounded-xl border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                  >
                    <option value="NOTA">Nota interna</option>
                    <option value="LLAMADA">Llamada telefónica</option>
                    <option value="REUNION">Reunión / Demo</option>
                    <option value="WHATSAPP">WhatsApp</option>
                    <option value="CORREO">Correo electrónico</option>
                  </select>

                  <input
                    type="text"
                    required
                    placeholder="Título (Ej. Cliente pide demo técnica)"
                    value={newActivity.titulo}
                    onChange={(e) => setNewActivity({ ...newActivity, titulo: e.target.value })}
                    className="col-span-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                  />
                </div>

                <div className="flex gap-2">
                  <input
                    type="text"
                    placeholder="Comentarios adicionales o acuerdos..."
                    value={newActivity.descripcion}
                    onChange={(e) => setNewActivity({ ...newActivity, descripcion: e.target.value })}
                    className="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                  />
                  <button
                    type="submit"
                    disabled={savingActivity}
                    className="rounded-xl bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                  >
                    {savingActivity ? 'Guardando...' : 'Añadir'}
                  </button>
                </div>
              </form>

              {/* Timeline list */}
              <div className="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                {selectedOp.actividades?.length === 0 ? (
                  <p className="py-4 text-center text-xs text-slate-400">
                    Aún no hay actividades registradas en esta oportunidad.
                  </p>
                ) : (
                  selectedOp.actividades?.map((act) => (
                    <div
                      key={act.id_actividad}
                      className="flex items-start gap-2.5 rounded-xl bg-slate-50 p-2.5 dark:bg-slate-800/40 text-xs"
                    >
                      <div className="mt-0.5 rounded-lg bg-white p-1 shadow-2xs dark:bg-slate-700">
                        {getActivityIcon(act.tipo)}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between">
                          <span className="font-bold text-slate-800 dark:text-slate-200 truncate">
                            {act.titulo}
                          </span>
                          <span className="text-[10px] text-slate-400 font-mono">
                            {act.fecha_actividad?.slice(0, 16)}
                          </span>
                        </div>
                        {act.descripcion && (
                          <p className="mt-0.5 text-slate-600 dark:text-slate-400 text-[11px]">
                            {act.descripcion}
                          </p>
                        )}
                        <span className="text-[9px] text-slate-400">
                          Por: {act.usuario?.nombre_completo || 'Usuario'}
                        </span>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Direct action to Cotizaciones */}
            <div className="mt-6 flex justify-between items-center pt-3 border-t border-slate-100 dark:border-slate-800">
              <button
                onClick={() => {
                  setSelectedOp(null);
                  navigate('/cotizaciones');
                }}
                className="flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
              >
                <FileText className="h-4 w-4" />
                <span>Generar Cotización formal</span>
                <ArrowRight className="h-3.5 w-3.5" />
              </button>

              <button
                onClick={() => setSelectedOp(null)}
                className="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
