import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import {
  FileText,
  Plus,
  Search,
  CheckCircle,
  XCircle,
  Clock,
  Printer,
  Eye,
  Trash2,
  X,
  AlertCircle,
  Building,
  Receipt,
  ArrowRight,
} from 'lucide-react';
import api from '../api/client';
import { useAuth } from '../context/AuthContext';
import { useCompany } from '../context/CompanyContext';

export default function CotizacionesPage() {
  const { user } = useAuth();
  const { activeEmpresa } = useCompany();
  const navigate = useNavigate();
  const location = useLocation();
  const [cotizaciones, setCotizaciones] = useState([]);
  const [clientes, setClientes] = useState([]);
  const [productos, setProductos] = useState([]);
  const [almacenes, setAlmacenes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [estadoFilter, setEstadoFilter] = useState('');

  // Create Modal
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [detalles, setDetalles] = useState([
    { id_producto: '', descripcion: '', marca: '', cantidad: 1, unidad_medida: 'UND', precio_unitario: 0 },
  ]);
  const [formData, setFormData] = useState({
    id_empresa: activeEmpresa?.id_empresa || user?.empresa?.id_empresa || 2,
    id_cliente: '',
    asunto: '',
    atencion: '',
    forma_pago: 'Contado',
    moneda: 'USD',
    tipo_cambio: 3.75,
    validez_dias: 15,
    vigencia: '15 días calendario',
    tiempo_entrega: 'Inmediata',
    observacion: '',
    condiciones_comerciales: '',
    alcance: '',
    restricciones: '',
    fecha_emision: new Date().toISOString().split('T')[0],
  });
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState('');

  // Emitir Venta Modal
  const [emitirModalOpen, setEmitirModalOpen] = useState(false);
  const [selectedQuoteForVenta, setSelectedQuoteForVenta] = useState(null);
  const [emitirData, setEmitirData] = useState({
    tipo_documento: 'FACTURA',
    serie: 'F001',
    forma_pago: 'Contado',
    fecha_emision: new Date().toISOString().split('T')[0],
    fecha_vencimiento: '',
    descontar_stock_almacen_id: '',
    observaciones: '',
  });
  const [emitirSaving, setEmitirSaving] = useState(false);
  const [emitirError, setEmitirError] = useState('');

  // Print/View Modal
  const [selectedQuote, setSelectedQuote] = useState(null);

  const fetchCotizaciones = async () => {
    setLoading(true);
    try {
      const res = await api.get('/cotizaciones', {
        params: { search, estado: estadoFilter || undefined },
      });
      if (res.data?.data) {
        setCotizaciones(res.data.data.data || res.data.data);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const loadResources = async () => {
    try {
      const [cliRes, prodRes, almRes] = await Promise.all([
        api.get('/clientes', { params: { per_page: 100 } }),
        api.get('/productos', { params: { per_page: 100 } }),
        api.get('/almacenes'),
      ]);
      if (cliRes.data?.data) setClientes(cliRes.data.data.data || cliRes.data.data);
      if (prodRes.data?.data) setProductos(prodRes.data.data.data || prodRes.data.data);
      if (almRes.data?.data) setAlmacenes(almRes.data.data);
    } catch (e) {
      console.error(e);
    }
  };

  useEffect(() => {
    loadResources();
  }, []);

  useEffect(() => {
    fetchCotizaciones();
  }, [search, estadoFilter]);

  useEffect(() => {
    if (location.state?.nuevo) {
      setFormData((prev) => ({
        ...prev,
        id_cliente: location.state.cliente_id || prev.id_cliente,
        asunto: location.state.asunto || prev.asunto,
      }));
      setCreateModalOpen(true);
      window.history.replaceState({}, document.title);
    }
  }, [location.state]);

  // Line item handlers
  const handleAddItem = () => {
    setDetalles([
      ...detalles,
      { id_producto: '', descripcion: '', marca: '', cantidad: 1, unidad_medida: 'UND', precio_unitario: 0 },
    ]);
  };

  const handleRemoveItem = (index) => {
    if (detalles.length === 1) return;
    setDetalles(detalles.filter((_, i) => i !== index));
  };

  const handleItemProductSelect = (index, prodId) => {
    const prod = productos.find((p) => p.id_producto == prodId);
    const updated = [...detalles];
    if (prod) {
      updated[index] = {
        ...updated[index],
        id_producto: prod.id_producto,
        descripcion: prod.nombre_producto,
        marca: prod.marca || '',
        unidad_medida: prod.unidad_medida || 'UND',
        precio_unitario: parseFloat(prod.precio_venta) || 0,
      };
    } else {
      updated[index].id_producto = '';
    }
    setDetalles(updated);
  };

  const handleItemChange = (index, field, value) => {
    const updated = [...detalles];
    updated[index][field] = value;
    setDetalles(updated);
  };

  // Live Totals calculation
  const subtotal = detalles.reduce((sum, item) => {
    const cant = parseFloat(item.cantidad) || 0;
    const precio = parseFloat(item.precio_unitario) || 0;
    return sum + cant * precio;
  }, 0);
  const igv = subtotal * 0.18;
  const total = subtotal + igv;

  const handleSaveCotizacion = async (e) => {
    e.preventDefault();
    setSaving(true);
    setFormError('');

    try {
      await api.post('/cotizaciones', {
        ...formData,
        detalles,
      });
      setCreateModalOpen(false);
      fetchCotizaciones();
    } catch (err) {
      setFormError(err.response?.data?.message || 'Error al guardar cotización.');
    } finally {
      setSaving(false);
    }
  };

  const handleStatusChange = async (id, newStatus) => {
    try {
      await api.patch(`/cotizaciones/${id}/estado`, { estado: newStatus });
      fetchCotizaciones();
    } catch (err) {
      alert('Error al cambiar estado.');
    }
  };

  const handleViewQuote = async (id) => {
    try {
      const res = await api.get(`/cotizaciones/${id}`);
      if (res.data?.data) {
        setSelectedQuote(res.data.data);
      }
    } catch (e) {
      alert('Error al cargar detalle de cotización.');
    }
  };

  const handleOpenEmitirVenta = (quote) => {
    setSelectedQuoteForVenta(quote);
    setEmitirData({
      tipo_documento: 'FACTURA',
      serie: 'F001',
      forma_pago: quote.forma_pago || 'Contado',
      fecha_emision: new Date().toISOString().split('T')[0],
      fecha_vencimiento: '',
      descontar_stock_almacen_id: '',
      observaciones: `Emisión de venta originada de la Cotización ${quote.numero_cotizacion}`,
    });
    setEmitirError('');
    setEmitirModalOpen(true);
  };

  const handleTipoDocChange = (tipo) => {
    const defaultSerie = tipo === 'FACTURA' ? 'F001' : tipo === 'BOLETA' ? 'B001' : 'NV01';
    setEmitirData({
      ...emitirData,
      tipo_documento: tipo,
      serie: defaultSerie,
    });
  };

  const handleEmitirVentaSubmit = async (e) => {
    e.preventDefault();
    setEmitirSaving(true);
    setEmitirError('');

    try {
      await api.post(`/ventas/from-cotizacion/${selectedQuoteForVenta.id_cotizacion}`, emitirData);
      setEmitirModalOpen(false);
      navigate('/ventas');
    } catch (err) {
      setEmitirError(
        err.response?.data?.message ||
        'Error al emitir el comprobante de venta a partir de la cotización.'
      );
    } finally {
      setEmitirSaving(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Módulo de Cotizaciones Comerciales
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Generación de propuestas técnico-económicas, cálculo de IGV y exportación de cotizaciones.
          </p>
        </div>

        <button
          onClick={() => {
            setDetalles([
              { id_producto: '', descripcion: '', marca: '', cantidad: 1, unidad_medida: 'UND', precio_unitario: 0 },
            ]);
            setCreateModalOpen(true);
          }}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          <span>Generar Nueva Cotización</span>
        </button>
      </div>

      {/* Filter and Search */}
      <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Buscar por correlativo COT, cliente o asunto..."
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
          />
        </div>

        <select
          value={estadoFilter}
          onChange={(e) => setEstadoFilter(e.target.value)}
          className="rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
        >
          <option value="">Todos los Estados</option>
          <option value="PENDIENTE">Pendientes</option>
          <option value="APROBADA">Aprobadas</option>
          <option value="RECHAZADA">Rechazadas</option>
        </select>
      </div>

      {/* Quotations Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
              <tr>
                <th className="py-3 px-4">N° Cotización</th>
                <th className="py-3 px-4">Cliente / Razón Social</th>
                <th className="py-3 px-4">Asunto / Descripción</th>
                <th className="py-3 px-4">Fecha Emisión</th>
                <th className="py-3 px-4 text-right">Total</th>
                <th className="py-3 px-4 text-center">Estado</th>
                <th className="py-3 px-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    <div className="flex items-center justify-center gap-2">
                      <div className="h-4 w-4 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
                      <span>Cargando cotizaciones...</span>
                    </div>
                  </td>
                </tr>
              ) : cotizaciones.length === 0 ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    No se encontraron cotizaciones registradas.
                  </td>
                </tr>
              ) : (
                cotizaciones.map((q) => (
                  <tr
                    key={q.id_cotizacion}
                    className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                  >
                    <td className="py-3 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                      {q.numero_cotizacion}
                    </td>
                    <td className="py-3 px-4 font-semibold text-slate-900 dark:text-white max-w-xs">
                      <div>{q.cliente?.razon_social || 'Cliente General'}</div>
                      <div className="text-[10px] text-slate-400 font-mono">
                        {q.cliente?.num_documento}
                      </div>
                    </td>
                    <td className="py-3 px-4 text-slate-600 dark:text-slate-300 max-w-xs truncate">
                      {q.asunto || 'Sin asunto'}
                    </td>
                    <td className="py-3 px-4 text-slate-500 dark:text-slate-400 font-mono">
                      {q.fecha_emision}
                    </td>
                    <td className="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                      {q.moneda} {Number(q.total).toFixed(2)}
                    </td>
                    <td className="py-3 px-4 text-center">
                      <span
                        className={`inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold ${
                          q.estado === 'APROBADA'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                            : q.estado === 'RECHAZADA'
                            ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                            : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
                        }`}
                      >
                        {q.estado}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-center">
                      <div className="flex items-center justify-center gap-1.5">
                        <button
                          onClick={() => handleViewQuote(q.id_cotizacion)}
                          title="Vista de Impresión / PDF"
                          className="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-slate-800"
                        >
                          <Printer className="h-3.5 w-3.5" />
                        </button>
                        {q.estado === 'PENDIENTE' && (
                          <>
                            <button
                              onClick={() => handleStatusChange(q.id_cotizacion, 'APROBADA')}
                              title="Aprobar Cotización"
                              className="flex h-7 w-7 items-center justify-center rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40"
                            >
                              <CheckCircle className="h-3.5 w-3.5" />
                            </button>
                            <button
                              onClick={() => handleStatusChange(q.id_cotizacion, 'RECHAZADA')}
                              title="Rechazar Cotización"
                              className="flex h-7 w-7 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                            >
                              <XCircle className="h-3.5 w-3.5" />
                            </button>
                          </>
                        )}
                        {q.estado === 'APROBADA' && (
                          <button
                            onClick={() => handleOpenEmitirVenta(q)}
                            title="Emitir Comprobante (Factura / Boleta / Nota de Venta)"
                            className="flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50 transition-colors"
                          >
                            <Receipt className="h-3.5 w-3.5" />
                            <span className="hidden sm:inline">Emitir Venta</span>
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Crear Cotización */}
      {createModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
          <div className="relative w-full max-w-4xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-8">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <h3 className="text-base font-bold text-slate-900 dark:text-white">
                Generar Nueva Cotización Comercial
              </h3>
              <button
                onClick={() => setCreateModalOpen(false)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {formError && (
              <div className="mt-3 flex items-center gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>{formError}</span>
              </div>
            )}

            <form onSubmit={handleSaveCotizacion} className="mt-4 space-y-4">
              {/* Header Details */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Cliente *
                  </label>
                  <select
                    required
                    value={formData.id_cliente}
                    onChange={(e) => {
                      const cli = clientes.find((c) => c.id_cliente == e.target.value);
                      setFormData({
                        ...formData,
                        id_cliente: e.target.value,
                        atencion: cli?.atencion || '',
                      });
                    }}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="">Seleccione Cliente</option>
                    {clientes.map((c) => (
                      <option key={c.id_cliente} value={c.id_cliente}>
                        {c.razon_social} ({c.num_documento})
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Atención / Contacto
                  </label>
                  <input
                    type="text"
                    value={formData.atencion}
                    onChange={(e) => setFormData({ ...formData, atencion: e.target.value })}
                    placeholder="Persona de contacto"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Asunto de la Cotización
                  </label>
                  <input
                    type="text"
                    value={formData.asunto}
                    onChange={(e) => setFormData({ ...formData, asunto: e.target.value })}
                    placeholder="Ej. Suministro de Equipos de Cómputo"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Moneda
                  </label>
                  <select
                    value={formData.moneda}
                    onChange={(e) => setFormData({ ...formData, moneda: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="USD">Dólares (USD)</option>
                    <option value="PEN">Soles (PEN)</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Forma de Pago
                  </label>
                  <select
                    value={formData.forma_pago}
                    onChange={(e) => setFormData({ ...formData, forma_pago: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="Contado">Contado</option>
                    <option value="Crédito 15 días">Crédito 15 días</option>
                    <option value="Crédito 30 días">Crédito 30 días</option>
                    <option value="50% Adelanto, 50% Entrega">50% Adelanto, 50% Entrega</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Validez / Vigencia
                  </label>
                  <input
                    type="text"
                    value={formData.vigencia}
                    onChange={(e) => setFormData({ ...formData, vigencia: e.target.value })}
                    placeholder="15 días calendario"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Tiempo de Entrega
                  </label>
                  <input
                    type="text"
                    value={formData.tiempo_entrega}
                    onChange={(e) => setFormData({ ...formData, tiempo_entrega: e.target.value })}
                    placeholder="2 a 3 días hábiles"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              {/* Items Section */}
              <div className="border-t border-slate-200 pt-4 dark:border-slate-800">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Ítems y Productos a Cotizar
                  </span>
                  <button
                    type="button"
                    onClick={handleAddItem}
                    className="flex items-center gap-1 rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-400"
                  >
                    <Plus className="h-3.5 w-3.5" />
                    <span>Agregar Ítem</span>
                  </button>
                </div>

                <div className="space-y-2.5">
                  {detalles.map((item, idx) => (
                    <div
                      key={idx}
                      className="grid grid-cols-12 gap-2 items-center rounded-xl bg-slate-50 p-2.5 dark:bg-slate-800/60"
                    >
                      <div className="col-span-3">
                        <select
                          value={item.id_producto}
                          onChange={(e) => handleItemProductSelect(idx, e.target.value)}
                          className="w-full rounded-lg border border-slate-200 bg-white p-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        >
                          <option value="">Seleccionar del Catálogo (Opcional)</option>
                          {productos.map((p) => (
                            <option key={p.id_producto} value={p.id_producto}>
                              {p.nombre_producto}
                            </option>
                          ))}
                        </select>
                      </div>

                      <div className="col-span-4">
                        <input
                          type="text"
                          required
                          value={item.descripcion}
                          onChange={(e) => handleItemChange(idx, 'descripcion', e.target.value)}
                          placeholder="Descripción del ítem *"
                          className="w-full rounded-lg border border-slate-200 bg-white p-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                      </div>

                      <div className="col-span-1">
                        <input
                          type="number"
                          step="0.01"
                          min="0.01"
                          required
                          value={item.cantidad}
                          onChange={(e) => handleItemChange(idx, 'cantidad', e.target.value)}
                          placeholder="Cant."
                          className="w-full rounded-lg border border-slate-200 bg-white p-1.5 text-xs font-mono text-center dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                      </div>

                      <div className="col-span-1">
                        <input
                          type="text"
                          value={item.unidad_medida}
                          onChange={(e) => handleItemChange(idx, 'unidad_medida', e.target.value)}
                          placeholder="UND"
                          className="w-full rounded-lg border border-slate-200 bg-white p-1.5 text-xs text-center dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                      </div>

                      <div className="col-span-2">
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          required
                          value={item.precio_unitario}
                          onChange={(e) => handleItemChange(idx, 'precio_unitario', e.target.value)}
                          placeholder="P. Unit"
                          className="w-full rounded-lg border border-slate-200 bg-white p-1.5 text-xs font-mono text-right dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                      </div>

                      <div className="col-span-1 flex justify-center">
                        <button
                          type="button"
                          onClick={() => handleRemoveItem(idx)}
                          disabled={detalles.length === 1}
                          className="rounded-lg p-1 text-slate-400 hover:text-rose-600 disabled:opacity-30"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Subtotal & IGV summary */}
                <div className="mt-4 flex justify-end">
                  <div className="w-64 space-y-1.5 rounded-2xl bg-slate-100/70 p-4 dark:bg-slate-800/80">
                    <div className="flex justify-between text-xs text-slate-600 dark:text-slate-400">
                      <span>Subtotal:</span>
                      <span className="font-mono font-semibold text-slate-800 dark:text-slate-200">
                        {formData.moneda} {subtotal.toFixed(2)}
                      </span>
                    </div>
                    <div className="flex justify-between text-xs text-slate-600 dark:text-slate-400">
                      <span>IGV (18%):</span>
                      <span className="font-mono font-semibold text-slate-800 dark:text-slate-200">
                        {formData.moneda} {igv.toFixed(2)}
                      </span>
                    </div>
                    <div className="flex justify-between border-t border-slate-200 pt-1.5 text-sm font-bold text-slate-900 dark:border-slate-700 dark:text-white">
                      <span>Total:</span>
                      <span className="font-mono text-indigo-600 dark:text-indigo-400">
                        {formData.moneda} {total.toFixed(2)}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setCreateModalOpen(false)}
                  className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="rounded-xl bg-indigo-600 px-6 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50"
                >
                  {saving ? 'Guardando...' : 'Emitir Cotización'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Printable Quotation View Modal */}
      {selectedQuote && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
          <div className="relative w-full max-w-3xl rounded-3xl border border-slate-200 bg-white p-8 shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-8">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800 mb-6">
              <div className="flex items-center gap-2">
                <span className="text-base font-bold text-slate-900 dark:text-white">
                  Vista Previa de Cotización
                </span>
                <span className="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                  {selectedQuote.numero_cotizacion}
                </span>
              </div>
              <div className="flex items-center gap-2">
                <button
                  onClick={() => window.print()}
                  className="flex items-center gap-1.5 rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-400"
                >
                  <Printer className="h-4 w-4" />
                  <span>Imprimir / PDF</span>
                </button>
                <button
                  onClick={() => setSelectedQuote(null)}
                  className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                  <X className="h-5 w-5" />
                </button>
              </div>
            </div>

            {/* Document Sheet */}
            <div className="rounded-2xl border border-slate-200 p-6 bg-white text-slate-900 space-y-6">
              {/* Header */}
              <div className="flex justify-between items-start border-b border-slate-200 pb-4">
                <div className="flex items-start gap-4">
                  {selectedQuote.empresa?.logo_url && (
                    <img
                      src={selectedQuote.empresa.logo_url}
                      alt={selectedQuote.empresa.razon_social}
                      className="h-16 max-w-[160px] object-contain"
                    />
                  )}
                  <div>
                    <h2 className="text-lg font-black text-indigo-950">
                      {selectedQuote.empresa?.razon_social || 'RUVINDUSTRIAS S.A.C.'}
                    </h2>
                    <p className="text-xs text-slate-600">
                      RUC: {selectedQuote.empresa?.ruc || '20606370149'}
                    </p>
                    <p className="text-xs text-slate-500">
                      {selectedQuote.empresa?.direccion || 'Arequipa, Perú'}
                    </p>
                  </div>
                </div>
                <div className="text-right">
                  <div className="rounded-xl bg-indigo-50 p-2 border border-indigo-100 text-center">
                    <p className="text-[10px] uppercase font-bold text-indigo-700">COTIZACIÓN N°</p>
                    <p className="text-sm font-mono font-black text-indigo-950">
                      {selectedQuote.numero_cotizacion}
                    </p>
                  </div>
                  <p className="mt-1 text-xs text-slate-500">
                    Fecha: {selectedQuote.fecha_emision}
                  </p>
                </div>
              </div>

              {/* Client Info */}
              <div className="grid grid-cols-2 gap-4 rounded-xl bg-slate-50 p-3 text-xs">
                <div>
                  <p className="text-slate-500 font-medium">SEÑOR(ES):</p>
                  <p className="font-bold text-slate-900">{selectedQuote.cliente?.razon_social}</p>
                  <p className="text-slate-600">RUC/DNI: {selectedQuote.cliente?.num_documento}</p>
                  <p className="text-slate-500">{selectedQuote.cliente?.direccion}</p>
                </div>
                <div>
                  <p className="text-slate-500 font-medium">CONDICIONES:</p>
                  <p>Forma de Pago: <span className="font-semibold">{selectedQuote.forma_pago}</span></p>
                  <p>Moneda: <span className="font-semibold">{selectedQuote.moneda}</span></p>
                  <p>Validez: <span className="font-semibold">{selectedQuote.vigencia}</span></p>
                  <p>Entrega: <span className="font-semibold">{selectedQuote.tiempo_entrega || 'Inmediata'}</span></p>
                </div>
              </div>

              {/* Items Table */}
              <table className="w-full text-xs">
                <thead>
                  <tr className="border-b border-slate-300 bg-slate-100 text-slate-700 font-bold uppercase text-[10px]">
                    <th className="py-2 px-2 text-center w-8">Ítem</th>
                    <th className="py-2 px-2 text-left">Descripción</th>
                    <th className="py-2 px-2 text-center w-16">Cant.</th>
                    <th className="py-2 px-2 text-center w-16">U.M.</th>
                    <th className="py-2 px-2 text-right w-24">P. Unit</th>
                    <th className="py-2 px-2 text-right w-24">Total</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                  {selectedQuote.detalles?.map((item, idx) => (
                    <tr key={item.id_detalle}>
                      <td className="py-2 px-2 text-center text-slate-500">{idx + 1}</td>
                      <td className="py-2 px-2 font-medium text-slate-800">
                        {item.descripcion}
                        {item.marca && <span className="text-slate-400"> ({item.marca})</span>}
                      </td>
                      <td className="py-2 px-2 text-center font-mono">{item.cantidad}</td>
                      <td className="py-2 px-2 text-center text-slate-500">{item.unidad_medida}</td>
                      <td className="py-2 px-2 text-right font-mono">
                        {Number(item.precio_unitario).toFixed(2)}
                      </td>
                      <td className="py-2 px-2 text-right font-mono font-bold">
                        {Number(item.subtotal || item.cantidad * item.precio_unitario).toFixed(2)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              {/* Totals */}
              <div className="flex justify-end pt-2">
                <div className="w-48 text-xs space-y-1">
                  <div className="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span className="font-mono">{selectedQuote.moneda} {Number(selectedQuote.subtotal).toFixed(2)}</span>
                  </div>
                  <div className="flex justify-between text-slate-600">
                    <span>IGV (18%):</span>
                    <span className="font-mono">{selectedQuote.moneda} {Number(selectedQuote.igv).toFixed(2)}</span>
                  </div>
                  <div className="flex justify-between border-t border-slate-300 pt-1 font-bold text-sm text-slate-900">
                    <span>TOTAL:</span>
                    <span className="font-mono text-indigo-700">{selectedQuote.moneda} {Number(selectedQuote.total).toFixed(2)}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
      {/* Modal Emitir Comprobante de Venta desde Cotización */}
      {emitirModalOpen && selectedQuoteForVenta && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
          <div className="relative w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-8">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <div className="flex items-center gap-2.5">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                  <Receipt className="h-5 w-5" />
                </div>
                <div>
                  <h3 className="text-base font-bold text-slate-900 dark:text-white">
                    Emitir Documento de Venta
                  </h3>
                  <p className="text-xs text-slate-400 font-mono">
                    Cotización: {selectedQuoteForVenta.numero_cotizacion}
                  </p>
                </div>
              </div>
              <button
                onClick={() => setEmitirModalOpen(false)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {emitirError && (
              <div className="mt-3 flex items-center gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>{emitirError}</span>
              </div>
            )}

            {/* Summary card */}
            <div className="mt-4 rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 space-y-2 text-xs">
              <div className="flex justify-between items-center">
                <span className="text-slate-500">Cliente / Razón Social:</span>
                <span className="font-bold text-slate-800 dark:text-slate-200">
                  {selectedQuoteForVenta.cliente?.razon_social}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-slate-500">Documento / RUC:</span>
                <span className="font-mono text-slate-700 dark:text-slate-300">
                  {selectedQuoteForVenta.cliente?.num_documento}
                </span>
              </div>
              <div className="flex justify-between items-center border-t border-slate-200 dark:border-slate-700 pt-2 font-bold">
                <span className="text-slate-700 dark:text-slate-300">Total a Facturar:</span>
                <span className="font-mono text-indigo-600 dark:text-indigo-400 text-sm">
                  {selectedQuoteForVenta.moneda} {Number(selectedQuoteForVenta.total).toFixed(2)}
                </span>
              </div>
            </div>

            <form onSubmit={handleEmitirVentaSubmit} className="mt-4 space-y-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Tipo de Comprobante
                  </label>
                  <select
                    value={emitirData.tipo_documento}
                    onChange={(e) => handleTipoDocChange(e.target.value)}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  >
                    <option value="FACTURA">Factura Electrónica (IGV 18%)</option>
                    <option value="BOLETA">Boleta de Venta (IGV 18%)</option>
                    <option value="NOTA_VENTA">Nota de Venta (Interno - Sin IGV)</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Serie
                  </label>
                  <input
                    type="text"
                    required
                    value={emitirData.serie}
                    onChange={(e) => setEmitirData({ ...emitirData, serie: e.target.value.toUpperCase() })}
                    placeholder="Ej. F001, B001, NV01"
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  >
                  </input>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Fecha de Emisión
                  </label>
                  <input
                    type="date"
                    required
                    value={emitirData.fecha_emision}
                    onChange={(e) => setEmitirData({ ...emitirData, fecha_emision: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Fecha Vencimiento (Opcional)
                  </label>
                  <input
                    type="date"
                    value={emitirData.fecha_vencimiento}
                    onChange={(e) => setEmitirData({ ...emitirData, fecha_vencimiento: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Condición de Pago
                  </label>
                  <input
                    type="text"
                    value={emitirData.forma_pago}
                    onChange={(e) => setEmitirData({ ...emitirData, forma_pago: e.target.value })}
                    placeholder="Ej. Contado / Crédito 15 días"
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Descontar Stock Físico
                  </label>
                  <select
                    value={emitirData.descontar_stock_almacen_id}
                    onChange={(e) => setEmitirData({ ...emitirData, descontar_stock_almacen_id: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  >
                    <option value="">No descontar (Solo Servicios / Sin Kardex)</option>
                    {almacenes.map((a) => (
                      <option key={a.id_almacen} value={a.id_almacen}>
                        {a.nombre_almacen}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Observaciones / Notas para el Documento
                </label>
                <textarea
                  rows="2"
                  value={emitirData.observaciones}
                  onChange={(e) => setEmitirData({ ...emitirData, observaciones: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                />
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setEmitirModalOpen(false)}
                  className="rounded-xl px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={emitirSaving}
                  className="flex items-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50"
                >
                  <Receipt className="h-4 w-4" />
                  <span>{emitirSaving ? 'Emitiendo comprobante...' : 'Generar Comprobante'}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
