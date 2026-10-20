import React, { useState, useEffect, useRef } from 'react';
import {
  Headset,
  Plus,
  Play,
  Pause,
  StopCircle,
  Clock,
  Search,
  CheckCircle,
  AlertCircle,
  Star,
  Users,
  ChevronRight,
  X,
} from 'lucide-react';
import api from '../api/client';
import { useAuth } from '../context/AuthContext';

export default function HelpdeskPage() {
  const { user } = useAuth();
  const [atenciones, setAtenciones] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [clientes, setClientes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [estadoFilter, setEstadoFilter] = useState('');

  // Live Timer State for New Ticket
  const [timerModalOpen, setTimerModalOpen] = useState(false);
  const [timerActive, setTimerActive] = useState(false);
  const [timerPaused, setTimerPaused] = useState(false);
  const [secondsElapsed, setSecondsElapsed] = useState(0);
  const [pausedSeconds, setPausedSeconds] = useState(0);
  const [startTime, setStartTime] = useState(null);

  const timerRef = useRef(null);

  // Form Data
  const [formData, setFormData] = useState({
    id_empresa: user?.empresa?.id_empresa || 2,
    id_cliente: '',
    id_contacto: '',
    tipo_usuario: 'CORPORATIVO',
    documento_solicitante: '',
    nombre_solicitante: '',
    categoria: 'SOFTWARE',
    id_subcategoria: '',
    canal_atencion: 'LLAMADA',
    descripcion_requerimiento: '',
    acciones_realizadas: '',
    estado_cierre: 'CONCLUIDO',
    nivel_satisfaccion: 5,
    fecha_atencion: new Date().toISOString().split('T')[0],
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const fetchAtenciones = async () => {
    setLoading(true);
    try {
      const res = await api.get('/helpdesk/atenciones', {
        params: { search, estado_cierre: estadoFilter || undefined },
      });
      if (res.data?.data) {
        setAtenciones(res.data.data.data || res.data.data);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const loadResources = async () => {
    try {
      const [catRes, cliRes] = await Promise.all([
        api.get('/helpdesk/categorias'),
        api.get('/clientes', { params: { per_page: 100 } }),
      ]);
      if (catRes.data?.data) setCategorias(catRes.data.data);
      if (cliRes.data?.data) setClientes(cliRes.data.data.data || cliRes.data.data);
    } catch (e) {
      console.error(e);
    }
  };

  useEffect(() => {
    loadResources();
  }, []);

  useEffect(() => {
    fetchAtenciones();
  }, [search, estadoFilter]);

  // Stopwatch effect
  useEffect(() => {
    if (timerActive && !timerPaused) {
      timerRef.current = setInterval(() => {
        setSecondsElapsed((prev) => prev + 1);
      }, 1000);
    } else {
      clearInterval(timerRef.current);
    }
    return () => clearInterval(timerRef.current);
  }, [timerActive, timerPaused]);

  const handleStartTimer = () => {
    setSecondsElapsed(0);
    setPausedSeconds(0);
    setStartTime(new Date());
    setTimerActive(true);
    setTimerPaused(false);
    setTimerModalOpen(true);
  };

  const handlePauseToggle = () => {
    setTimerPaused(!timerPaused);
  };

  const formatTimer = (totalSeconds) => {
    const hrs = Math.floor(totalSeconds / 3600);
    const mins = Math.floor((totalSeconds % 3600) / 60);
    const secs = totalSeconds % 60;
    return `${hrs.toString().padStart(2, '0')}:${mins
      .toString()
      .padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  const handleSaveTicket = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    const endTime = new Date();
    const effectiveSec = Math.max(0, secondsElapsed);

    try {
      await api.post('/helpdesk/atenciones', {
        ...formData,
        hora_inicio: startTime ? startTime.toISOString() : new Date().toISOString(),
        hora_fin: endTime.toISOString(),
        tiempo_pausa_segundos: pausedSeconds,
        tiempo_efectivo_segundos: effectiveSec,
      });

      setTimerActive(false);
      setTimerModalOpen(false);
      fetchAtenciones();
    } catch (err) {
      setError(err.response?.data?.message || 'Error al guardar el ticket de atención.');
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
            Mesa de Ayuda & Helpdesk
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Control de soporte técnico en tiempo real, medición de tiempos efectivos y encuestas de satisfacción.
          </p>
        </div>

        <button
          onClick={handleStartTimer}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Play className="h-4 w-4 fill-white" />
          <span>Iniciar Nueva Atención (Cronómetro)</span>
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
            placeholder="Buscar por código de ticket HD, solicitante o cliente..."
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
          />
        </div>

        <select
          value={estadoFilter}
          onChange={(e) => setEstadoFilter(e.target.value)}
          className="rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
        >
          <option value="">Todos los Estados</option>
          <option value="CONCLUIDO">Concluido</option>
          <option value="EN_PROCESO">En Proceso</option>
          <option value="ESCALADO_N2">Escalado N2</option>
          <option value="ESPERA_REPUESTO">Espera de Repuesto</option>
        </select>
      </div>

      {/* Tickets Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
              <tr>
                <th className="py-3 px-4">Código Ticket</th>
                <th className="py-3 px-4">Cliente / Solicitante</th>
                <th className="py-3 px-4">Categoría / Canal</th>
                <th className="py-3 px-4">Técnico Asignado</th>
                <th className="py-3 px-4 text-center">Tiempo Efectivo</th>
                <th className="py-3 px-4 text-center">Satisfacción</th>
                <th className="py-3 px-4 text-center">Estado</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    <div className="flex items-center justify-center gap-2">
                      <div className="h-4 w-4 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
                      <span>Cargando tickets de soporte...</span>
                    </div>
                  </td>
                </tr>
              ) : atenciones.length === 0 ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    No se encontraron tickets de soporte helpdesk.
                  </td>
                </tr>
              ) : (
                atenciones.map((item) => (
                  <tr
                    key={item.id_atencion}
                    className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                  >
                    <td className="py-3 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                      {item.codigo_ticket}
                    </td>
                    <td className="py-3 px-4 font-semibold text-slate-900 dark:text-white max-w-xs">
                      <div>{item.nombre_solicitante}</div>
                      <div className="text-[10px] text-slate-400 font-normal">
                        {item.cliente?.razon_social || 'Cliente'} • DNI/Doc: {item.documento_solicitante}
                      </div>
                    </td>
                    <td className="py-3 px-4 text-slate-600 dark:text-slate-300">
                      <span className="font-semibold text-slate-800 dark:text-slate-200">
                        {item.categoria}
                      </span>
                      <span className="text-[10px] text-slate-400 block">
                        Canal: {item.canal_atencion}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-slate-600 dark:text-slate-400">
                      {item.tecnico?.nombre_usuario || 'Técnico'}
                    </td>
                    <td className="py-3 px-4 text-center font-mono">
                      <span className="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                        {Math.floor(item.tiempo_efectivo_segundos / 60)} min {item.tiempo_efectivo_segundos % 60} seg
                      </span>
                    </td>
                    <td className="py-3 px-4 text-center">
                      <div className="flex items-center justify-center gap-0.5 text-amber-500">
                        {[...Array(item.nivel_satisfaccion || 5)].map((_, i) => (
                          <Star key={i} className="h-3 w-3 fill-amber-400 text-amber-400" />
                        ))}
                      </div>
                    </td>
                    <td className="py-3 px-4 text-center">
                      <span
                        className={`inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold ${
                          item.estado_cierre === 'CONCLUIDO'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                            : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
                        }`}
                      >
                        {item.estado_cierre}
                      </span>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Cronómetro y Registro de Atención */}
      {timerModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
          <div className="relative w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-8">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <div className="flex items-center gap-2">
                <Clock className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                <h3 className="text-base font-bold text-slate-900 dark:text-white">
                  Atención en Curso (Cronómetro en Vivo)
                </h3>
              </div>
              <button
                onClick={() => setTimerModalOpen(false)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {/* Stopwatch Visual Banner */}
            <div className="my-4 flex items-center justify-between rounded-2xl bg-gradient-to-r from-indigo-900 to-indigo-950 p-5 text-white">
              <div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-indigo-300">
                  Tiempo Efectivo Transcurrido
                </p>
                <p className="text-3xl font-mono font-black tracking-tight mt-1">
                  {formatTimer(secondsElapsed)}
                </p>
              </div>

              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={handlePauseToggle}
                  className={`flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold shadow-xs transition-colors ${
                    timerPaused
                      ? 'bg-emerald-500 hover:bg-emerald-600 text-white'
                      : 'bg-amber-500 hover:bg-amber-600 text-white'
                  }`}
                >
                  {timerPaused ? <Play className="h-3.5 w-3.5 fill-white" /> : <Pause className="h-3.5 w-3.5 fill-white" />}
                  <span>{timerPaused ? 'Reanudar' : 'Pausar'}</span>
                </button>
              </div>
            </div>

            {error && (
              <div className="mb-4 flex items-center gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>{error}</span>
              </div>
            )}

            <form onSubmit={handleSaveTicket} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Cliente Corporativo *
                  </label>
                  <select
                    required
                    value={formData.id_cliente}
                    onChange={(e) => {
                      const c = clientes.find((x) => x.id_cliente == e.target.value);
                      setFormData({
                        ...formData,
                        id_cliente: e.target.value,
                        nombre_solicitante: c?.atencion || '',
                      });
                    }}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="">Seleccione Cliente</option>
                    {clientes.map((c) => (
                      <option key={c.id_cliente} value={c.id_cliente}>
                        {c.razon_social}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Nombre del Solicitante *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.nombre_solicitante}
                    onChange={(e) => setFormData({ ...formData, nombre_solicitante: e.target.value })}
                    placeholder="Ej. Ing. Carlos Mendoza"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    DNI / Documento Solicitante *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.documento_solicitante}
                    onChange={(e) => setFormData({ ...formData, documento_solicitante: e.target.value })}
                    placeholder="8 dígitos"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Categoría *
                  </label>
                  <select
                    value={formData.categoria}
                    onChange={(e) => setFormData({ ...formData, categoria: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="HARDWARE">Hardware y Periféricos</option>
                    <option value="SOFTWARE">Software y Sistemas</option>
                    <option value="REDES_CONECTIVIDAD">Redes y Conectividad</option>
                    <option value="SEGURIDAD">Seguridad y CCTV</option>
                    <option value="OTROS">Otros</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Canal de Atención *
                  </label>
                  <select
                    value={formData.canal_atencion}
                    onChange={(e) => setFormData({ ...formData, canal_atencion: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="LLAMADA">Llamada Telefónica</option>
                    <option value="ACCESO_REMOTO">Acceso Remoto (AnyDesk / TeamViewer)</option>
                    <option value="PRESENCIAL">Presencial en Sede</option>
                    <option value="WHATSAPP">WhatsApp</option>
                    <option value="CORREO">Correo Electrónico</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Descripción del Requerimiento / Falla *
                </label>
                <textarea
                  rows="2"
                  required
                  value={formData.descripcion_requerimiento}
                  onChange={(e) => setFormData({ ...formData, descripcion_requerimiento: e.target.value })}
                  placeholder="Detalle de la incidencia reportada por el usuario..."
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Acciones Realizadas / Solución *
                </label>
                <textarea
                  rows="2"
                  required
                  value={formData.acciones_realizadas}
                  onChange={(e) => setFormData({ ...formData, acciones_realizadas: e.target.value })}
                  placeholder="Procedimientos técnicos ejecutados para resolver o mitigar la falla..."
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Estado de Cierre
                  </label>
                  <select
                    value={formData.estado_cierre}
                    onChange={(e) => setFormData({ ...formData, estado_cierre: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 font-bold dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="CONCLUIDO">CONCLUIDO (Resuelto)</option>
                    <option value="EN_PROCESO">EN PROCESO (Pendiente de cierre)</option>
                    <option value="ESCALADO_N2">ESCALADO A NIVEL 2</option>
                    <option value="ESPERA_REPUESTO">EN ESPERA DE REPUESTO</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Nivel de Satisfacción (1 - 5)
                  </label>
                  <select
                    value={formData.nivel_satisfaccion}
                    onChange={(e) => setFormData({ ...formData, nivel_satisfaccion: parseInt(e.target.value) })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value={5}>★★★★★ (Excelente - 5)</option>
                    <option value={4}>★★★★☆ (Bueno - 4)</option>
                    <option value={3}>★★★☆☆ (Regular - 3)</option>
                    <option value={2}>★★☆☆☆ (Deficiente - 2)</option>
                    <option value={1}>★☆☆☆☆ (Malo - 1)</option>
                  </select>
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setTimerModalOpen(false)}
                  className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                  Continuar en segundo plano
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="rounded-xl bg-indigo-600 px-6 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50"
                >
                  {saving ? 'Guardando...' : 'Finalizar y Guardar Ticket'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
