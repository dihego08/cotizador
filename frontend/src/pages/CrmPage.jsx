import React, { useState, useEffect, useRef } from 'react';
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
  Paperclip,
  Download,
  UserPlus,
  Pencil,
  Sparkles,
  Link2,
  Unlink,
  ExternalLink,
  Bell,
  Receipt,
  GripVertical,
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
  const [misTratosOnly, setMisTratosOnly] = useState(false);
  const [seguimientoFilter, setSeguimientoFilter] = useState('TODOS'); // 'TODOS' | 'ATRASADOS' | 'HOY' | 'SIN_TAREA'
  const [viewMode, setViewMode] = useState('kanban'); // 'kanban' | 'list'

  // Drag and drop state
  const [draggedOpId, setDraggedOpId] = useState(null);
  const [dragOverCol, setDragOverCol] = useState(null);

  // Clientes para formularios
  const [clientes, setClientes] = useState([]);

  // Modal Nueva Oportunidad
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [clientMode, setClientMode] = useState('EXISTENTE'); // 'EXISTENTE' | 'NUEVO'
  const [nuevoCliente, setNuevoCliente] = useState({
    nombre: '',
    telefono: '',
    email: '',
    origen: 'PROSPECCION_WEB',
  });
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
  const [cotizacionesCliente, setCotizacionesCliente] = useState([]);
  const [selectedCotizacionToLink, setSelectedCotizacionToLink] = useState('');
  const [savingLinkQuote, setSavingLinkQuote] = useState(false);

  const [newActivity, setNewActivity] = useState({
    tipo: 'NOTA',
    titulo: '',
    descripcion: '',
    proximo_seguimiento: '',
    programar_seguimiento: false,
  });
  const [selectedFile, setSelectedFile] = useState(null);
  const fileInputRef = useRef(null);
  const [savingActivity, setSavingActivity] = useState(false);

  // Edición rápida de monto en modal de detalle
  const [isEditingAmount, setIsEditingAmount] = useState(false);
  const [amountEditValue, setAmountEditValue] = useState('');
  const [amountEditMoneda, setAmountEditMoneda] = useState('USD');
  const [savingAmountEdit, setSavingAmountEdit] = useState(false);

  // Modal para Marcar como PERDIDO con Motivo
  const [lostModalOpen, setLostModalOpen] = useState(false);
  const [opForLost, setOpForLost] = useState(null);
  const [motivoPreset, setMotivoPreset] = useState('PRESUPUESTO');
  const [motivoComentario, setMotivoComentario] = useState('');
  const [savingLost, setSavingLost] = useState(false);

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
    setClientMode('EXISTENTE');
    setNuevoCliente({
      nombre: '',
      telefono: '',
      email: '',
      origen: 'PROSPECCION_WEB',
    });
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
    if (clientMode === 'EXISTENTE' && !formData.id_cliente) {
      setCreateError('Por favor selecciona un cliente de la lista o cambia a "Crear Prospecto Nuevo".');
      return;
    }
    if (clientMode === 'NUEVO' && !nuevoCliente.nombre.trim()) {
      setCreateError('Por favor ingresa el nombre de la empresa o prospecto.');
      return;
    }
    setSavingOportunidad(true);
    setCreateError('');

    try {
      const payload = {
        ...formData,
        id_empresa: activeEmpresa?.id_empresa || formData.id_empresa,
        valor_estimado: formData.valor_estimado ? parseFloat(formData.valor_estimado) : 0,
        id_cliente: clientMode === 'EXISTENTE' ? formData.id_cliente : undefined,
        nuevo_cliente_nombre: clientMode === 'NUEVO' ? nuevoCliente.nombre : undefined,
        nuevo_cliente_telefono: clientMode === 'NUEVO' ? nuevoCliente.telefono : undefined,
        nuevo_cliente_email: clientMode === 'NUEVO' ? nuevoCliente.email : undefined,
        nuevo_cliente_origen: clientMode === 'NUEVO' ? nuevoCliente.origen : undefined,
      };
      await api.post('/crm/oportunidades', payload);
      setCreateModalOpen(false);
      fetchOportunidades();
      loadClientes();
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
    if (newEtapa === 'PERDIDO') {
      const op = oportunidades.find((o) => o.id_oportunidad === id) || selectedOp;
      setOpForLost(op);
      setMotivoPreset('PRESUPUESTO');
      setMotivoComentario('');
      setLostModalOpen(true);
      return;
    }

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

  const handleConfirmLost = async (e) => {
    e.preventDefault();
    if (!opForLost) return;
    setSavingLost(true);

    const presetLabels = {
      PRESUPUESTO: 'Presupuesto insuficiente / Precio percibido alto',
      COMPETENCIA: 'Eligió a otro proveedor / Competencia',
      POSTERGADO: 'Postergado / No es el momento (para retomar después)',
      SIN_CONTACTO: 'Dejó de responder (Sin contacto / Ghosting)',
      NO_COMPATIBLE: 'Requerimiento técnico fuera de alcance',
      OTRO: 'Otro motivo',
    };

    const fullMotivo = motivoComentario.trim()
      ? `${presetLabels[motivoPreset]}: ${motivoComentario.trim()}`
      : presetLabels[motivoPreset];

    try {
      await api.patch(`/crm/oportunidades/${opForLost.id_oportunidad}/etapa`, {
        etapa: 'PERDIDO',
        motivo_perdida: fullMotivo,
      });
      setLostModalOpen(false);
      setOpForLost(null);
      fetchOportunidades();
      if (selectedOp && selectedOp.id_oportunidad === opForLost.id_oportunidad) {
        openDetailModal(opForLost.id_oportunidad);
      }
    } catch (err) {
      alert('Error al registrar motivo de pérdida.');
    } finally {
      setSavingLost(false);
    }
  };

  const handleSaveAmountEdit = async (e) => {
    e.preventDefault();
    if (!selectedOp) return;
    setSavingAmountEdit(true);

    try {
      const val = amountEditValue ? parseFloat(amountEditValue) : 0;
      await api.patch(`/crm/oportunidades/${selectedOp.id_oportunidad}`, {
        valor_estimado: val,
        moneda: amountEditMoneda,
      });
      setIsEditingAmount(false);
      openDetailModal(selectedOp.id_oportunidad);
      fetchOportunidades();
    } catch (err) {
      alert('Error al actualizar el valor estimado.');
    } finally {
      setSavingAmountEdit(false);
    }
  };

  const openDetailModal = async (id) => {
    setLoadingDetail(true);
    try {
      const res = await api.get(`/crm/oportunidades/${id}`);
      if (res.data?.data) {
        setSelectedOp(res.data.data);
        setCotizacionesCliente(res.data.cotizaciones_cliente || []);
        setSelectedCotizacionToLink('');
      }
    } catch (e) {
      alert('Error al cargar la oportunidad');
    } finally {
      setLoadingDetail(false);
    }
  };

  const handleLinkCotizacion = async (idCot) => {
    if (!selectedOp) return;
    setSavingLinkQuote(true);
    try {
      await api.post(`/crm/oportunidades/${selectedOp.id_oportunidad}/vincular-cotizacion`, {
        id_cotizacion: idCot || null,
      });
      openDetailModal(selectedOp.id_oportunidad);
      fetchOportunidades();
    } catch (err) {
      alert('Error al vincular la cotización.');
    } finally {
      setSavingLinkQuote(false);
    }
  };

  const handleAddActivity = async (e) => {
    e.preventDefault();
    if (!selectedOp || !newActivity.titulo) return;
    setSavingActivity(true);
    try {
      const formPayload = new FormData();
      formPayload.append('id_empresa', selectedOp.id_empresa);
      formPayload.append('id_cliente', selectedOp.id_cliente);
      formPayload.append('id_oportunidad', selectedOp.id_oportunidad);
      formPayload.append('tipo', newActivity.tipo);
      formPayload.append('titulo', newActivity.titulo);
      if (newActivity.descripcion) formPayload.append('descripcion', newActivity.descripcion);
      if (newActivity.programar_seguimiento && newActivity.proximo_seguimiento) {
        formPayload.append('proximo_seguimiento', newActivity.proximo_seguimiento);
        formPayload.append('completado', 0);
      } else {
        formPayload.append('completado', 1);
      }
      if (selectedFile) formPayload.append('archivo', selectedFile);

      await api.post('/crm/actividades', formPayload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      setNewActivity({
        tipo: 'NOTA',
        titulo: '',
        descripcion: '',
        proximo_seguimiento: '',
        programar_seguimiento: false,
      });
      setSelectedFile(null);
      if (fileInputRef.current) fileInputRef.current.value = '';
      openDetailModal(selectedOp.id_oportunidad);
      fetchOportunidades();
    } catch (err) {
      alert('Error al registrar actividad.');
    } finally {
      setSavingActivity(false);
    }
  };

  // Drag and Drop handlers
  const handleDragStart = (e, opId) => {
    e.dataTransfer.setData('text/plain', opId.toString());
    e.dataTransfer.effectAllowed = 'move';
    setDraggedOpId(opId);
  };

  const handleDragEnd = () => {
    setDraggedOpId(null);
    setDragOverCol(null);
  };

  const handleDragOver = (e, colId) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (dragOverCol !== colId) {
      setDragOverCol(colId);
    }
  };

  const handleDragLeave = (e, colId) => {
    if (e.currentTarget.contains(e.relatedTarget)) return;
    if (dragOverCol === colId) {
      setDragOverCol(null);
    }
  };

  const handleDrop = async (e, targetEtapa) => {
    e.preventDefault();
    setDragOverCol(null);
    const opIdStr = e.dataTransfer.getData('text/plain');
    if (!opIdStr) return;
    const opId = parseInt(opIdStr, 10);
    if (!opId) return;

    const op = oportunidades.find((o) => o.id_oportunidad === opId);
    if (op && op.etapa === targetEtapa) return;

    handleStageChange(opId, targetEtapa);
  };

  // Helpers de CRM
  const getWhatsAppUrl = (phone, op) => {
    if (!phone) return null;
    const clean = phone.replace(/[^0-9]/g, '');
    const empresaNombre = activeEmpresa?.razon_social || 'nuestro equipo';
    const text = encodeURIComponent(
      `Hola ${op.cliente?.razon_social || ''}, te saluda ${user?.nombre_completo || 'el área comercial'} de ${empresaNombre} respecto a "${op.titulo}". ¿Tienes unos minutos para conversar?`
    );
    return `https://wa.me/${clean}?text=${text}`;
  };

  const getDealAge = (createdAt) => {
    if (!createdAt) return '';
    const diffTime = Math.abs(new Date() - new Date(createdAt));
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return 'Hoy';
    if (diffDays === 1) return '1 d';
    return `${diffDays} d`;
  };

  const getFollowUpStatus = (op) => {
    const acts = op.actividades || [];
    const followUps = acts
      .filter((a) => a.proximo_seguimiento)
      .sort((a, b) => new Date(a.proximo_seguimiento) - new Date(b.proximo_seguimiento));

    if (followUps.length === 0) {
      return {
        type: 'NONE',
        label: 'Sin seguimiento',
        shortLabel: 'Sin tarea',
        badgeClass: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
      };
    }

    const next = followUps[0];
    const today = new Date().toISOString().split('T')[0];
    const nextDate = String(next.proximo_seguimiento).split('T')[0];

    if (nextDate < today) {
      return {
        type: 'OVERDUE',
        label: `Atrasado (${nextDate})`,
        shortLabel: `Atrasado`,
        badgeClass: 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 font-bold',
      };
    } else if (nextDate === today) {
      return {
        type: 'TODAY',
        label: `Hoy: ${next.titulo || 'Seguimiento'}`,
        shortLabel: 'Hoy',
        badgeClass: 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 font-bold',
      };
    } else {
      return {
        type: 'FUTURE',
        label: `Próx: ${nextDate}`,
        shortLabel: nextDate,
        badgeClass: 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300',
      };
    }
  };

  // Filtrado reactivo en pipeline
  const filteredOportunidades = oportunidades.filter((op) => {
    if (misTratosOnly && op.id_usuario !== user?.id_usuario) {
      return false;
    }
    if (seguimientoFilter !== 'TODOS') {
      const followUp = getFollowUpStatus(op);
      if (seguimientoFilter === 'ATRASADOS' && followUp.type !== 'OVERDUE') return false;
      if (seguimientoFilter === 'HOY' && followUp.type !== 'TODAY') return false;
      if (seguimientoFilter === 'SIN_TAREA' && followUp.type !== 'NONE') return false;
    }
    return true;
  });

  const handleDownloadFile = async (actividadId, filename) => {
    try {
      const response = await api.get(`/crm/actividades/${actividadId}/archivo`, {
        responseType: 'blob',
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename || 'archivo.pdf');
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (e) {
      alert('Error al descargar el archivo.');
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

        <div className="flex flex-wrap items-center gap-2">
          {/* Toggle Mis Tratos vs Todos */}
          <button
            type="button"
            onClick={() => setMisTratosOnly(!misTratosOnly)}
            className={`rounded-xl px-3 py-2 text-xs font-semibold transition-colors border ${
              misTratosOnly
                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 dark:bg-indigo-950/50 dark:border-indigo-800 dark:text-indigo-300'
                : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-400'
            }`}
          >
            {misTratosOnly ? '👤 Solo mis tratos' : '👥 Todos los tratos'}
          </button>

          {/* Filtro por Próximo Seguimiento */}
          <select
            value={seguimientoFilter}
            onChange={(e) => setSeguimientoFilter(e.target.value)}
            className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300"
          >
            <option value="TODOS">Todos los seguimientos</option>
            <option value="ATRASADOS">🚨 Tareas Atrasadas</option>
            <option value="HOY">🔔 Tareas para Hoy</option>
            <option value="SIN_TAREA">⚪ Sin Tareas Agendadas</option>
          </select>

          {/* Filtro Prioridad */}
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
            const etapaOps = filteredOportunidades.filter((op) => op.etapa === etapa.id);
            const etapaTotal = etapaOps.reduce((sum, op) => sum + (parseFloat(op.valor_estimado) || 0), 0);

            return (
              <div
                key={etapa.id}
                onDragOver={(e) => handleDragOver(e, etapa.id)}
                onDragLeave={(e) => handleDragLeave(e, etapa.id)}
                onDrop={(e) => handleDrop(e, etapa.id)}
                className={`flex flex-col rounded-2xl border transition-all p-2.5 min-w-[245px] ${
                  dragOverCol === etapa.id
                    ? 'border-indigo-500 bg-indigo-50/70 ring-2 ring-indigo-400/30 dark:border-indigo-400 dark:bg-indigo-950/40'
                    : 'border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-900/60'
                }`}
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
                <div className="space-y-2.5 min-h-[160px]">
                  {loading ? (
                    <div className="py-8 text-center text-[11px] text-slate-400">
                      Cargando tratos...
                    </div>
                  ) : etapaOps.length === 0 ? (
                    <div className="py-8 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl text-[10px] text-slate-400">
                      Arrastra un trato aquí
                    </div>
                  ) : (
                    etapaOps.map((op) => (
                      <div
                        key={op.id_oportunidad}
                        draggable={true}
                        onDragStart={(e) => handleDragStart(e, op.id_oportunidad)}
                        onDragEnd={handleDragEnd}
                        className={`group relative rounded-xl border border-slate-200 bg-white p-3 shadow-xs hover:shadow-md transition-all dark:border-slate-700/80 dark:bg-slate-800 hover:border-indigo-300 dark:hover:border-indigo-600 cursor-grab active:cursor-grabbing ${
                          draggedOpId === op.id_oportunidad ? 'opacity-40 scale-95 border-dashed border-indigo-500' : ''
                        }`}
                        onClick={() => openDetailModal(op.id_oportunidad)}
                      >
                        {/* Priority Badge, Deal Age & Probability */}
                        <div className="flex items-center justify-between mb-1.5">
                          <div className="flex items-center gap-1.5">
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
                            {op.created_at && (
                              <span className="text-[9px] text-slate-400 font-mono flex items-center gap-0.5" title="Tiempo en el pipeline">
                                <Clock className="h-2.5 w-2.5" />
                                {getDealAge(op.created_at)}
                              </span>
                            )}
                          </div>
                          <span className="text-[10px] font-bold text-slate-400">
                            {op.probabilidad}%
                          </span>
                        </div>

                        {/* Title */}
                        <h4 className="text-xs font-bold text-slate-900 dark:text-white line-clamp-2 leading-snug">
                          {op.titulo}
                        </h4>

                        {/* Client & Lead Origin */}
                        <div className="mt-1 flex items-center justify-between gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                          <span className="truncate font-medium">{op.cliente?.razon_social}</span>
                          {op.cliente?.origen_lead && (
                            <span className="text-[9px] font-mono text-slate-400 shrink-0">
                              {op.cliente.origen_lead === 'PROSPECCION_WEB' ? '🌐 Web' : op.cliente.origen_lead === 'REFERIDO' ? '👥 Referido' : ''}
                            </span>
                          )}
                        </div>

                        {/* Follow-up / Next step badge */}
                        <div className="mt-2">
                          {(() => {
                            const followUp = getFollowUpStatus(op);
                            return (
                              <div className={`inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[9px] ${followUp.badgeClass}`}>
                                {followUp.type === 'OVERDUE' ? (
                                  <AlertCircle className="h-2.5 w-2.5 shrink-0" />
                                ) : followUp.type === 'TODAY' ? (
                                  <Bell className="h-2.5 w-2.5 shrink-0" />
                                ) : (
                                  <Clock className="h-2.5 w-2.5 shrink-0" />
                                )}
                                <span className="truncate max-w-[170px]">{followUp.label}</span>
                              </div>
                            );
                          })()}
                        </div>

                        {/* Linked quote (if any) */}
                        {op.cotizacion && (
                          <div className="mt-1.5">
                            <span className="inline-flex items-center gap-1 rounded-md bg-purple-50 px-1.5 py-0.5 text-[9px] font-bold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800/40">
                              <FileText className="h-2.5 w-2.5" />
                              <span>{op.cotizacion.numero_cotizacion}</span>
                              <span className="font-mono">({op.cotizacion.moneda} {Number(op.cotizacion.total).toFixed(2)})</span>
                            </span>
                          </div>
                        )}

                        {/* Value and Expected Date */}
                        <div className="mt-2.5 flex items-center justify-between border-t border-slate-100 pt-2 dark:border-slate-700/60">
                          {parseFloat(op.valor_estimado) > 0 ? (
                            <span className="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                              {op.moneda} {Number(op.valor_estimado).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                            </span>
                          ) : (
                            <span className="inline-flex rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                              Por cotizar
                            </span>
                          )}

                          {op.fecha_cierre_estimada && (
                            <span className="flex items-center gap-1 text-[10px] text-slate-400 font-mono">
                              <Calendar className="h-3 w-3" />
                              {op.fecha_cierre_estimada}
                            </span>
                          )}
                        </div>

                        {/* Omnichannel quick actions & Stage move controls */}
                        <div
                          className="mt-2 flex items-center justify-between gap-1 pt-1 opacity-80 group-hover:opacity-100 border-t border-slate-50 dark:border-slate-800/60"
                          onClick={(e) => e.stopPropagation()}
                        >
                          {/* Omnichannel direct shortcuts */}
                          <div className="flex items-center gap-1">
                            {op.cliente?.telefono && (
                              <a
                                href={getWhatsAppUrl(op.cliente.telefono, op)}
                                target="_blank"
                                rel="noopener noreferrer"
                                title={`WhatsApp: ${op.cliente.telefono}`}
                                className="rounded-md p-1 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors"
                              >
                                <MessageSquare className="h-3.5 w-3.5" />
                              </a>
                            )}
                            {op.cliente?.telefono && (
                              <a
                                href={`tel:${op.cliente.telefono}`}
                                title={`Llamar: ${op.cliente.telefono}`}
                                className="rounded-md p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 transition-colors"
                              >
                                <Phone className="h-3.5 w-3.5" />
                              </a>
                            )}
                            {op.cliente?.email && (
                              <a
                                href={`mailto:${op.cliente.email}?subject=${encodeURIComponent('Seguimiento: ' + op.titulo)}`}
                                title={`Correo: ${op.cliente.email}`}
                                className="rounded-md p-1 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition-colors"
                              >
                                <Mail className="h-3.5 w-3.5" />
                              </a>
                            )}
                          </div>

                          {/* Quick stage transition buttons */}
                          <div className="flex items-center gap-0.5">
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
                  <th className="py-3 px-4">Próximo Seguimiento</th>
                  <th className="py-3 px-4">Cotización</th>
                  <th className="py-3 px-4">Cierre Previsto</th>
                  <th className="py-3 px-4 text-right">Valor Estimado</th>
                  <th className="py-3 px-4 text-center">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {filteredOportunidades.length === 0 ? (
                  <tr>
                    <td colSpan="8" className="py-10 text-center text-slate-400">
                      No se encontraron oportunidades con los filtros seleccionados.
                    </td>
                  </tr>
                ) : (
                  filteredOportunidades.map((op) => {
                    const followUp = getFollowUpStatus(op);
                    return (
                      <tr
                        key={op.id_oportunidad}
                        className="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors cursor-pointer"
                        onClick={() => openDetailModal(op.id_oportunidad)}
                      >
                        <td className="py-3 px-4">
                          <div className="font-bold text-slate-900 dark:text-white">{op.titulo}</div>
                          <div className="text-[10px] text-slate-400 line-clamp-1">{op.descripcion || 'Sin descripción'}</div>
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-medium text-slate-800 dark:text-slate-200">
                            {op.cliente?.razon_social}
                          </div>
                          {op.cliente?.telefono && (
                            <div className="text-[10px] text-slate-400 font-mono">{op.cliente.telefono}</div>
                          )}
                        </td>
                        <td className="py-3 px-4">
                          <span className="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {op.etapa} ({op.probabilidad}%)
                          </span>
                        </td>
                        <td className="py-3 px-4">
                          <span className={`inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] ${followUp.badgeClass}`}>
                            {followUp.type === 'OVERDUE' ? (
                              <AlertCircle className="h-3 w-3 shrink-0" />
                            ) : followUp.type === 'TODAY' ? (
                              <Bell className="h-3 w-3 shrink-0" />
                            ) : (
                              <Clock className="h-3 w-3 shrink-0" />
                            )}
                            <span>{followUp.shortLabel}</span>
                          </span>
                        </td>
                        <td className="py-3 px-4">
                          {op.cotizacion ? (
                            <span className="inline-flex items-center gap-1 rounded-md bg-purple-50 px-2 py-0.5 text-[10px] font-bold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                              <FileText className="h-3 w-3" />
                              {op.cotizacion.numero_cotizacion}
                            </span>
                          ) : (
                            <span className="text-[10px] text-slate-400 italic">Sin vincular</span>
                          )}
                        </td>
                        <td className="py-3 px-4 font-mono text-slate-500">
                          {op.fecha_cierre_estimada || '-'}
                        </td>
                        <td className="py-3 px-4 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">
                          {parseFloat(op.valor_estimado) > 0 ? (
                            `${op.moneda} ${Number(op.valor_estimado).toFixed(2)}`
                          ) : (
                            <span className="text-slate-400 font-normal italic">Por cotizar</span>
                          )}
                        </td>
                        <td className="py-3 px-4 text-center" onClick={(e) => e.stopPropagation()}>
                          <div className="flex items-center justify-center gap-1">
                            {op.cliente?.telefono && (
                              <a
                                href={getWhatsAppUrl(op.cliente.telefono, op)}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="rounded-lg p-1 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40"
                                title="WhatsApp"
                              >
                                <MessageSquare className="h-3.5 w-3.5" />
                              </a>
                            )}
                            <button
                              onClick={() => openDetailModal(op.id_oportunidad)}
                              className="rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300"
                            >
                              Ver Bitácora
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })
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
              {/* Client Selection / Quick Lead toggle */}
              <div>
                <div className="flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 mb-2">
                  <button
                    type="button"
                    onClick={() => setClientMode('EXISTENTE')}
                    className={`flex-1 rounded-lg py-1.5 text-xs font-semibold transition-colors ${
                      clientMode === 'EXISTENTE'
                        ? 'bg-white shadow-xs text-indigo-700 dark:bg-slate-700 dark:text-white'
                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'
                    }`}
                  >
                    Cliente de la Base de Datos
                  </button>
                  <button
                    type="button"
                    onClick={() => setClientMode('NUEVO')}
                    className={`flex-1 flex items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-semibold transition-colors ${
                      clientMode === 'NUEVO'
                        ? 'bg-white shadow-xs text-indigo-700 dark:bg-slate-700 dark:text-white'
                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'
                    }`}
                  >
                    <UserPlus className="h-3.5 w-3.5" />
                    <span>+ Prospecto Nuevo (Rápido)</span>
                  </button>
                </div>

                {clientMode === 'EXISTENTE' ? (
                  <div>
                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                      Seleccionar Cliente *
                    </label>
                    <select
                      value={formData.id_cliente}
                      onChange={(e) => setFormData({ ...formData, id_cliente: e.target.value })}
                      className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                    >
                      <option value="">-- Seleccionar de la lista --</option>
                      {clientes.map((c) => (
                        <option key={c.id_cliente} value={c.id_cliente}>
                          {c.razon_social} ({c.num_documento})
                        </option>
                      ))}
                    </select>
                  </div>
                ) : (
                  <div className="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-3 dark:border-indigo-900/40 dark:bg-indigo-950/20 space-y-2.5">
                    <div>
                      <label className="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Nombre de la Empresa o Contacto *
                      </label>
                      <input
                        type="text"
                        placeholder="Ej. Radio Fórmula / Juan Pérez"
                        value={nuevoCliente.nombre}
                        onChange={(e) => setNuevoCliente({ ...nuevoCliente, nombre: e.target.value })}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                      />
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                      <div>
                        <label className="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                          Teléfono / WhatsApp
                        </label>
                        <input
                          type="text"
                          placeholder="999 999 999"
                          value={nuevoCliente.telefono}
                          onChange={(e) => setNuevoCliente({ ...nuevoCliente, telefono: e.target.value })}
                          className="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                        />
                      </div>
                      <div>
                        <label className="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                          Correo Electrónico
                        </label>
                        <input
                          type="email"
                          placeholder="contacto@empresa.com"
                          value={nuevoCliente.email}
                          onChange={(e) => setNuevoCliente({ ...nuevoCliente, email: e.target.value })}
                          className="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                        />
                      </div>
                    </div>
                    <div>
                      <label className="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Origen del Prospecto
                      </label>
                      <select
                        value={nuevoCliente.origen}
                        onChange={(e) => setNuevoCliente({ ...nuevoCliente, origen: e.target.value })}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                      >
                        <option value="PROSPECCION_WEB">Prospección Web / Outreach en frío</option>
                        <option value="REFERIDO">Recomendación de un conocido</option>
                        <option value="LINKEDIN">LinkedIn / Redes Sociales</option>
                        <option value="CLIENTE_ANTIGUO">Cliente recurrente</option>
                        <option value="OTROS">Otro canal</option>
                      </select>
                    </div>
                  </div>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Título del Trato / Proyecto *
                </label>
                <input
                  type="text"
                  required
                  placeholder="Ej. Rediseño Web y Renovación Tecnológica"
                  value={formData.titulo}
                  onChange={(e) => setFormData({ ...formData, titulo: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <div className="flex items-center justify-between mb-1">
                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                      Valor Estimado <span className="text-[10px] text-slate-400 font-normal">(Opcional)</span>
                    </label>
                  </div>
                  <input
                    type="number"
                    step="any"
                    min="0"
                    placeholder="0.00 (Por definir)"
                    value={formData.valor_estimado}
                    onChange={(e) => setFormData({ ...formData, valor_estimado: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-mono text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  />
                  <p className="mt-1 text-[10px] text-slate-400">
                    Puedes dejarlo vacío al inicio y fijar el precio al enviar la cotización.
                  </p>
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

                {/* Omnichannel direct action buttons */}
                <div className="flex items-center gap-1.5 mt-2">
                  {selectedOp.cliente?.telefono && (
                    <a
                      href={getWhatsAppUrl(selectedOp.cliente.telefono, selectedOp)}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 transition-colors"
                    >
                      <MessageSquare className="h-3 w-3 text-emerald-600" />
                      <span>WhatsApp</span>
                    </a>
                  )}
                  {selectedOp.cliente?.telefono && (
                    <a
                      href={`tel:${selectedOp.cliente.telefono}`}
                      className="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 transition-colors"
                    >
                      <Phone className="h-3 w-3 text-blue-600" />
                      <span>Llamar</span>
                    </a>
                  )}
                  {selectedOp.cliente?.email && (
                    <a
                      href={`mailto:${selectedOp.cliente.email}?subject=${encodeURIComponent('Seguimiento: ' + selectedOp.titulo)}`}
                      className="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 transition-colors"
                    >
                      <Mail className="h-3 w-3 text-amber-600" />
                      <span>Correo</span>
                    </a>
                  )}
                </div>
              </div>
              <button
                onClick={() => setSelectedOp(null)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {/* Quick Actions & Summary */}
            <div className="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3 rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
              <div>
                <div className="flex items-center justify-between">
                  <span className="text-[10px] text-slate-400 block font-semibold">Valor del Trato:</span>
                  {!isEditingAmount && (
                    <button
                      type="button"
                      onClick={() => {
                        setAmountEditValue(selectedOp.valor_estimado > 0 ? selectedOp.valor_estimado : '');
                        setAmountEditMoneda(selectedOp.moneda || 'USD');
                        setIsEditingAmount(true);
                      }}
                      className="text-[10px] text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1 font-semibold"
                    >
                      <Pencil className="h-2.5 w-2.5" />
                      <span>{selectedOp.valor_estimado > 0 ? 'Modificar' : '+ Fijar monto'}</span>
                    </button>
                  )}
                </div>

                {isEditingAmount ? (
                  <form onSubmit={handleSaveAmountEdit} className="mt-1 flex items-center gap-1">
                    <select
                      value={amountEditMoneda}
                      onChange={(e) => setAmountEditMoneda(e.target.value)}
                      className="rounded-lg border border-slate-300 bg-white p-1 text-[11px] font-mono dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    >
                      <option value="USD">USD</option>
                      <option value="PEN">PEN</option>
                    </select>
                    <input
                      type="number"
                      step="any"
                      min="0"
                      placeholder="0.00"
                      value={amountEditValue}
                      onChange={(e) => setAmountEditValue(e.target.value)}
                      className="w-20 rounded-lg border border-slate-300 bg-white p-1 text-[11px] font-mono dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                    <button
                      type="submit"
                      disabled={savingAmountEdit}
                      className="rounded-lg bg-indigo-600 px-2 py-1 text-[10px] font-bold text-white hover:bg-indigo-700"
                    >
                      OK
                    </button>
                    <button
                      type="button"
                      onClick={() => setIsEditingAmount(false)}
                      className="rounded-lg p-1 text-slate-400 hover:text-slate-600"
                    >
                      <X className="h-3 w-3" />
                    </button>
                  </form>
                ) : (
                  <span className="text-sm font-bold font-mono text-indigo-600 dark:text-indigo-400 block mt-0.5">
                    {parseFloat(selectedOp.valor_estimado) > 0 ? (
                      `${selectedOp.moneda} ${Number(selectedOp.valor_estimado).toFixed(2)}`
                    ) : (
                      <span className="text-xs text-slate-400 font-normal italic">Sin cotizar aún</span>
                    )}
                  </span>
                )}
              </div>

              <div>
                <span className="text-[10px] text-slate-400 block font-semibold">Cierre Previsto:</span>
                <span className="text-xs font-mono text-slate-700 dark:text-slate-300 block mt-0.5">
                  {selectedOp.fecha_cierre_estimada || 'Sin fecha fija'}
                </span>
              </div>

              <div>
                <span className="text-[10px] text-slate-400 block font-semibold">Asesor / Responsable:</span>
                <span className="text-xs font-medium text-slate-700 dark:text-slate-300 block mt-0.5 truncate">
                  {selectedOp.usuario?.nombre_completo || 'Tú'}
                </span>
              </div>
            </div>

            {/* WON Deal Commercial Flow banner */}
            {selectedOp.etapa === 'GANADO' && (
              <div className="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent p-3.5 border border-emerald-300 dark:border-emerald-800">
                <div className="flex items-center gap-2.5">
                  <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-xs shrink-0">
                    <Award className="h-5 w-5" />
                  </div>
                  <div>
                    <h4 className="text-xs font-bold text-emerald-900 dark:text-emerald-300">
                      ¡Trato Cerrado Ganado con Éxito!
                    </h4>
                    <p className="text-[11px] text-emerald-700/80 dark:text-emerald-400">
                      Convierte esta oportunidad ganada en una Factura, Boleta o Nota de Venta.
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => {
                    setSelectedOp(null);
                    navigate('/ventas', {
                      state: {
                        nuevo: true,
                        cliente_id: selectedOp.id_cliente,
                        titulo: selectedOp.titulo,
                      },
                    });
                  }}
                  className="flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition-colors shrink-0"
                >
                  <Receipt className="h-4 w-4" />
                  <span>Emitir Comprobante / Venta</span>
                </button>
              </div>
            )}

            {/* Commercial Quote Linking Card */}
            <div className="mt-4 rounded-2xl border border-slate-200 bg-white p-3.5 dark:border-slate-800 dark:bg-slate-900/60">
              <div className="flex items-center justify-between mb-2">
                <span className="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                  <FileText className="h-4 w-4 text-purple-600 dark:text-purple-400" />
                  <span>Cotización Formal del Trato</span>
                </span>
                {selectedOp.cotizacion && (
                  <button
                    type="button"
                    onClick={() => handleLinkCotizacion(null)}
                    disabled={savingLinkQuote}
                    className="text-[10px] text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-1"
                  >
                    <Unlink className="h-3 w-3" />
                    <span>Desvincular</span>
                  </button>
                )}
              </div>

              {selectedOp.cotizacion ? (
                <div className="space-y-2">
                  <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-xl bg-purple-50/60 p-3 dark:bg-purple-950/20 border border-purple-200/60 dark:border-purple-800/40">
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-xs text-purple-900 dark:text-purple-300">
                          {selectedOp.cotizacion.numero_cotizacion}
                        </span>
                        <span className={`text-[9px] font-bold px-1.5 py-0.5 rounded-md ${
                          selectedOp.cotizacion.estado === 'APROBADA'
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300'
                            : 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300'
                        }`}>
                          {selectedOp.cotizacion.estado}
                        </span>
                      </div>
                      <p className="mt-0.5 text-[11px] text-slate-500 font-mono">
                        Total: <strong className="text-slate-800 dark:text-slate-200">{selectedOp.cotizacion.moneda} {Number(selectedOp.cotizacion.total).toFixed(2)}</strong>
                      </p>
                    </div>

                    <button
                      type="button"
                      onClick={() => {
                        setSelectedOp(null);
                        navigate('/cotizaciones');
                      }}
                      className="flex items-center justify-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-purple-700 shadow-2xs border border-purple-200 hover:bg-purple-50 dark:bg-slate-800 dark:text-purple-300 dark:border-purple-800"
                    >
                      <span>Ver Cotización</span>
                      <ExternalLink className="h-3 w-3" />
                    </button>
                  </div>

                  {selectedOp.cotizacion.estado === 'APROBADA' && selectedOp.etapa !== 'GANADO' && (
                    <div className="flex items-center justify-between gap-2 rounded-xl bg-emerald-50 p-2 text-xs text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                      <span className="text-[11px] font-medium">
                        ✨ ¡La cotización fue aprobada! ¿Avanzar trato a Cerrado Ganado?
                      </span>
                      <button
                        type="button"
                        onClick={() => handleStageChange(selectedOp.id_oportunidad, 'GANADO')}
                        className="rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-emerald-700"
                      >
                        Marcar Ganado
                      </button>
                    </div>
                  )}
                </div>
              ) : (
                <div className="space-y-2.5">
                  <p className="text-[11px] text-slate-500 dark:text-slate-400">
                    No hay ninguna cotización vinculada a este trato. Puedes crear una nueva o asociar una existente del cliente.
                  </p>
                  
                  <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    {cotizacionesCliente.length > 0 && (
                      <div className="flex-1 flex items-center gap-1.5">
                        <select
                          value={selectedCotizacionToLink}
                          onChange={(e) => setSelectedCotizacionToLink(e.target.value)}
                          className="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                          <option value="">-- Seleccionar cotización previa ({cotizacionesCliente.length}) --</option>
                          {cotizacionesCliente.map((c) => (
                            <option key={c.id_cotizacion} value={c.id_cotizacion}>
                              {c.numero_cotizacion} - {c.moneda} {Number(c.total).toFixed(2)} ({c.estado})
                            </option>
                          ))}
                        </select>
                        <button
                          type="button"
                          disabled={!selectedCotizacionToLink || savingLinkQuote}
                          onClick={() => handleLinkCotizacion(selectedCotizacionToLink)}
                          className="flex items-center gap-1 rounded-xl bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700 disabled:opacity-50 transition-colors shrink-0"
                        >
                          <Link2 className="h-3.5 w-3.5" />
                          <span>Vincular</span>
                        </button>
                      </div>
                    )}

                    <button
                      type="button"
                      onClick={() => {
                        setSelectedOp(null);
                        navigate('/cotizaciones', {
                          state: {
                            nuevo: true,
                            cliente_id: selectedOp.id_cliente,
                            asunto: selectedOp.titulo,
                          },
                        });
                      }}
                      className="flex items-center justify-center gap-1.5 rounded-xl border border-dashed border-indigo-300 bg-indigo-50/50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 transition-colors shrink-0"
                    >
                      <Plus className="h-3.5 w-3.5" />
                      <span>+ Crear Nueva Cotización</span>
                    </button>
                  </div>
                </div>
              )}
            </div>

            {selectedOp.motivo_perdida && (
              <div className="mt-3 flex items-start gap-2 rounded-xl bg-rose-50 p-2.5 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800/40">
                <XCircle className="h-4 w-4 shrink-0 mt-0.5 text-rose-500" />
                <div>
                  <span className="font-bold">Motivo de Descarte / Pérdida: </span>
                  <span>{selectedOp.motivo_perdida}</span>
                </div>
              </div>
            )}

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

                <div className="flex flex-col sm:flex-row sm:items-center gap-2">
                  <input
                    type="text"
                    placeholder="Comentarios adicionales o acuerdos..."
                    value={newActivity.descripcion}
                    onChange={(e) => setNewActivity({ ...newActivity, descripcion: e.target.value })}
                    className="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                  />
                  
                  <div className="flex items-center gap-2">
                    <input
                      type="file"
                      ref={fileInputRef}
                      onChange={(e) => setSelectedFile(e.target.files[0] || null)}
                      className="hidden"
                      accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                    />
                    <button
                      type="button"
                      onClick={() => fileInputRef.current?.click()}
                      className={`flex items-center gap-1.5 rounded-xl border px-2.5 py-1.5 text-xs font-semibold transition-colors ${
                        selectedFile
                          ? 'border-indigo-400 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                          : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
                      }`}
                    >
                      <Paperclip className="h-3.5 w-3.5" />
                      <span className="max-w-[130px] truncate">{selectedFile ? selectedFile.name : 'Adjuntar PDF / Mockup'}</span>
                    </button>
                    {selectedFile && (
                      <button
                        type="button"
                        onClick={() => {
                          setSelectedFile(null);
                          if (fileInputRef.current) fileInputRef.current.value = '';
                        }}
                        className="text-slate-400 hover:text-rose-600"
                      >
                        <X className="h-4 w-4" />
                      </button>
                    )}

                    <button
                      type="submit"
                      disabled={savingActivity}
                      className="rounded-xl bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 shrink-0"
                    >
                      {savingActivity ? 'Guardando...' : 'Añadir'}
                    </button>
                  </div>
                </div>

                {/* Schedule next follow-up toggle */}
                <div className="rounded-xl border border-slate-200/80 bg-slate-50/60 p-2 dark:border-slate-700 dark:bg-slate-900/40">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={newActivity.programar_seguimiento}
                      onChange={(e) => setNewActivity({ ...newActivity, programar_seguimiento: e.target.checked })}
                      className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span className="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                      <Bell className="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                      <span>¿Programar Próximo Seguimiento / Tarea en el Pipeline?</span>
                    </span>
                  </label>

                  {newActivity.programar_seguimiento && (
                    <div className="mt-2 flex items-center gap-2 pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                      <Calendar className="h-4 w-4 text-slate-400 shrink-0" />
                      <input
                        type="date"
                        required={newActivity.programar_seguimiento}
                        value={newActivity.proximo_seguimiento}
                        onChange={(e) => setNewActivity({ ...newActivity, proximo_seguimiento: e.target.value })}
                        className="rounded-xl border border-slate-200 bg-white px-2.5 py-1 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                      />
                      <span className="text-[10px] text-slate-400">
                        Aparecerá en el embudo con alerta de color.
                      </span>
                    </div>
                  )}
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
                      <div className="mt-0.5 rounded-lg bg-white p-1 shadow-2xs dark:bg-slate-700 shrink-0">
                        {getActivityIcon(act.tipo)}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between">
                          <span className="font-bold text-slate-800 dark:text-slate-200 truncate">
                            {act.titulo}
                          </span>
                          <span className="text-[10px] text-slate-400 font-mono shrink-0">
                            {act.fecha_actividad?.slice(0, 16)}
                          </span>
                        </div>
                        {act.descripcion && (
                          <p className="mt-0.5 text-slate-600 dark:text-slate-400 text-[11px]">
                            {act.descripcion}
                          </p>
                        )}
                        {act.proximo_seguimiento && (
                          <div className="mt-1">
                            <span className="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-1.5 py-0.5 text-[9px] font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                              <Bell className="h-2.5 w-2.5" />
                              <span>Próx. Seguimiento: {String(act.proximo_seguimiento).slice(0, 10)}</span>
                            </span>
                          </div>
                        )}
                        {act.archivo_path && (
                          <div className="mt-2 flex items-center gap-2">
                            <button
                              type="button"
                              onClick={() => handleDownloadFile(act.id_actividad, act.archivo_nombre)}
                              className="flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50/70 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 transition-colors"
                            >
                              <Paperclip className="h-3 w-3" />
                              <span className="truncate max-w-[200px]">{act.archivo_nombre || 'Ver archivo adjunto'}</span>
                              {act.archivo_size && (
                                <span className="text-[9px] text-indigo-400 font-mono">
                                  ({(act.archivo_size / 1024).toFixed(0)} KB)
                                </span>
                              )}
                              <Download className="h-3 w-3 ml-1" />
                            </button>
                          </div>
                        )}
                        <span className="text-[9px] text-slate-400 block mt-1">
                          Por: {act.usuario?.nombre_completo || 'Usuario'}
                        </span>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Direct action footer */}
            <div className="mt-6 flex justify-end items-center pt-3 border-t border-slate-100 dark:border-slate-800">
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

      {/* Modal: Marcar como Perdido con Motivo */}
      {lostModalOpen && opForLost && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
          <div className="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-8">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <div className="flex items-center gap-2">
                <XCircle className="h-5 w-5 text-rose-500" />
                <h3 className="text-base font-bold text-slate-900 dark:text-white">
                  Marcar Trato como Perdido
                </h3>
              </div>
              <button
                onClick={() => setLostModalOpen(false)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <p className="mt-3 text-xs text-slate-500 dark:text-slate-400">
              Registrar el motivo te permite filtrar en el futuro qué clientes postergaron su proyecto para reactivarlos.
            </p>

            <form onSubmit={handleConfirmLost} className="mt-4 space-y-3.5">
              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Motivo Principal de Descarte
                </label>
                <select
                  value={motivoPreset}
                  onChange={(e) => setMotivoPreset(e.target.value)}
                  className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                >
                  <option value="PRESUPUESTO">Presupuesto insuficiente / Precio percibido alto</option>
                  <option value="COMPETENCIA">Eligió a otro proveedor / Competencia</option>
                  <option value="POSTERGADO">Postergado / No es el momento (para retomar después)</option>
                  <option value="SIN_CONTACTO">Dejó de responder (Sin contacto / Ghosting)</option>
                  <option value="NO_COMPATIBLE">Requerimiento fuera de alcance técnico</option>
                  <option value="OTRO">Otro motivo</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Detalles u Observaciones (Opcional)
                </label>
                <textarea
                  rows="2"
                  placeholder="Ej. Dijeron que lo retomarán el próximo trimestre..."
                  value={motivoComentario}
                  onChange={(e) => setMotivoComentario(e.target.value)}
                  className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                />
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setLostModalOpen(false)}
                  className="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={savingLost}
                  className="rounded-xl bg-rose-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50"
                >
                  {savingLost ? 'Guardando...' : 'Confirmar y Archivar'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
