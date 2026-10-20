import React, { useState, useEffect } from 'react';
import {
  Receipt,
  Plus,
  Search,
  CheckCircle,
  Clock,
  Printer,
  DollarSign,
  CreditCard,
  Building,
  AlertCircle,
  X,
  FileText,
  TrendingUp,
  Wallet,
  Calendar,
  Trash2,
} from 'lucide-react';
import api from '../api/client';
import { useCompany } from '../context/CompanyContext';

export default function VentasPage() {
  const { activeEmpresa } = useCompany();
  const [ventas, setVentas] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [tipoFilter, setTipoFilter] = useState('');
  const [pagoFilter, setPagoFilter] = useState('');

  // Resources
  const [clientes, setClientes] = useState([]);
  const [productos, setProductos] = useState([]);
  const [almacenes, setAlmacenes] = useState([]);

  // Create Direct Sale Modal
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [createFormData, setCreateFormData] = useState({
    id_empresa: activeEmpresa?.id_empresa || '',
    id_cliente: '',
    tipo_documento: 'FACTURA',
    serie: 'F001',
    moneda: 'PEN',
    tipo_cambio: 1.000,
    forma_pago: 'Contado',
    fecha_emision: new Date().toISOString().split('T')[0],
    fecha_vencimiento: '',
    descontar_stock_almacen_id: '',
    observaciones: '',
  });
  const [detalles, setDetalles] = useState([
    { id_producto: '', descripcion: '', marca: '', cantidad: 1, unidad_medida: 'UND', precio_unitario: 0 },
  ]);
  const [pagoInmediato, setPagoInmediato] = useState(false);
  const [pagoInicialData, setPagoInicialData] = useState({
    monto: '',
    metodo_pago: 'EFECTIVO',
    referencia_operacion: '',
    banco: '',
    notas: 'Pago al contado en emisión',
  });
  const [savingVenta, setSavingVenta] = useState(false);
  const [createError, setCreateError] = useState('');

  // Payment Modal
  const [paymentModalOpen, setPaymentModalOpen] = useState(false);
  const [selectedVentaForPayment, setSelectedVentaForPayment] = useState(null);
  const [paymentData, setPaymentData] = useState({
    monto: '',
    fecha_pago: new Date().toISOString().split('T')[0],
    metodo_pago: 'TRANSFERENCIA',
    referencia_operacion: '',
    banco: 'BCP',
    notas: '',
  });
  const [savingPayment, setSavingPayment] = useState(false);
  const [paymentError, setPaymentError] = useState('');

  // Print/View Modal
  const [selectedVenta, setSelectedVenta] = useState(null);

  const fetchVentas = async () => {
    setLoading(true);
    try {
      const res = await api.get('/ventas', {
        params: {
          search,
          tipo_documento: tipoFilter || undefined,
          estado_pago: pagoFilter || undefined,
          id_empresa: activeEmpresa?.id_empresa || undefined,
        },
      });
      if (res.data?.data) {
        setVentas(res.data.data.data || res.data.data);
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
        api.get('/clientes', { params: { per_page: 150 } }),
        api.get('/productos', { params: { per_page: 150 } }),
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
    fetchVentas();
  }, [search, tipoFilter, pagoFilter, activeEmpresa]);

  const handleOpenPayment = (venta) => {
    setSelectedVentaForPayment(venta);
    setPaymentData({
      monto: venta.saldo_pendiente,
      fecha_pago: new Date().toISOString().split('T')[0],
      metodo_pago: 'TRANSFERENCIA',
      referencia_operacion: '',
      banco: 'BCP',
      notas: '',
    });
    setPaymentError('');
    setPaymentModalOpen(true);
  };

  const handleSavePayment = async (e) => {
    e.preventDefault();
    setSavingPayment(true);
    setPaymentError('');

    try {
      await api.post(`/ventas/${selectedVentaForPayment.id_venta}/pagos`, paymentData);
      setPaymentModalOpen(false);
      fetchVentas();
    } catch (err) {
      setPaymentError(
        err.response?.data?.errors?.monto?.[0] ||
        err.response?.data?.message ||
        'Error al registrar el cobro.'
      );
    } finally {
      setSavingPayment(false);
    }
  };

  const handleViewVenta = async (id) => {
    try {
      const res = await api.get(`/ventas/${id}`);
      if (res.data?.data) {
        setSelectedVenta(res.data.data);
      }
    } catch (e) {
      alert('Error al cargar detalle de venta.');
    }
  };

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

  const handleTipoDocChange = (tipo) => {
    const defaultSerie = tipo === 'FACTURA' ? 'F001' : tipo === 'BOLETA' ? 'B001' : 'NV01';
    setCreateFormData({
      ...createFormData,
      tipo_documento: tipo,
      serie: defaultSerie,
    });
  };

  const handleOpenCreateVenta = () => {
    setCreateFormData({
      id_empresa: activeEmpresa?.id_empresa || '',
      id_cliente: '',
      tipo_documento: 'FACTURA',
      serie: 'F001',
      moneda: 'PEN',
      tipo_cambio: 1.000,
      forma_pago: 'Contado',
      fecha_emision: new Date().toISOString().split('T')[0],
      fecha_vencimiento: '',
      descontar_stock_almacen_id: '',
      observaciones: '',
    });
    setDetalles([
      { id_producto: '', descripcion: '', marca: '', cantidad: 1, unidad_medida: 'UND', precio_unitario: 0 },
    ]);
    setPagoInmediato(false);
    setPagoInicialData({
      monto: '',
      metodo_pago: 'EFECTIVO',
      referencia_operacion: '',
      banco: '',
      notas: 'Pago al contado en emisión',
    });
    setCreateError('');
    setCreateModalOpen(true);
  };

  const handleSaveDirectVenta = async (e) => {
    e.preventDefault();
    if (!createFormData.id_cliente) {
      setCreateError('Por favor selecciona un cliente para emitir el comprobante.');
      return;
    }
    setSavingVenta(true);
    setCreateError('');

    try {
      const payload = {
        ...createFormData,
        id_empresa: activeEmpresa?.id_empresa || createFormData.id_empresa,
        detalles,
        pago_inicial: pagoInmediato && parseFloat(pagoInicialData.monto) > 0 ? pagoInicialData : undefined,
      };
      await api.post('/ventas', payload);
      setCreateModalOpen(false);
      fetchVentas();
    } catch (err) {
      setCreateError(
        err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(' ')
          : err.response?.data?.message || 'Error al emitir el documento de venta.'
      );
    } finally {
      setSavingVenta(false);
    }
  };

  // Computations for live new direct sale
  const subtotalNuevo = detalles.reduce((sum, item) => {
    const cant = parseFloat(item.cantidad) || 0;
    const pu = parseFloat(item.precio_unitario) || 0;
    return sum + (cant * pu);
  }, 0);
  const igvNuevo = (createFormData.tipo_documento === 'NOTA_VENTA') ? 0 : (subtotalNuevo * 0.18);
  const totalNuevo = subtotalNuevo + igvNuevo;

  // Calculations for KPI Cards
  const totalFacturado = ventas.reduce((sum, v) => sum + (v.estado_anulado ? 0 : parseFloat(v.total) || 0), 0);
  const totalCobrado = ventas.reduce((sum, v) => sum + (v.estado_anulado ? 0 : parseFloat(v.total_pagado) || 0), 0);
  const totalSaldoPendiente = ventas.reduce((sum, v) => sum + (v.estado_anulado ? 0 : parseFloat(v.saldo_pendiente) || 0), 0);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Documentos de Venta y Control de Cobranzas
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Facturas, Boletas y Notas de Venta con seguimiento de amortizaciones y saldos por cobrar.
          </p>
        </div>

        <button
          onClick={handleOpenCreateVenta}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          <span>Nueva Venta Directa</span>
        </button>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">
              Total Facturado / Emitido
            </span>
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
              <Receipt className="h-4 w-4" />
            </div>
          </div>
          <p className="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            S/ {totalFacturado.toFixed(2)}
          </p>
          <span className="text-[11px] text-slate-400 font-medium">En empresa activa</span>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">
              Total Cobrado (Ingresado)
            </span>
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
              <CheckCircle className="h-4 w-4" />
            </div>
          </div>
          <p className="mt-3 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
            S/ {totalCobrado.toFixed(2)}
          </p>
          <span className="text-[11px] text-slate-400 font-medium">Cobranzas efectivas recibidas</span>
        </div>

        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">
              Saldo Pendiente por Cobrar
            </span>
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
              <Clock className="h-4 w-4" />
            </div>
          </div>
          <p className="mt-3 text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
            S/ {totalSaldoPendiente.toFixed(2)}
          </p>
          <span className="text-[11px] text-amber-600/80 dark:text-amber-400/80 font-medium">
            Cartera de cuentas por cobrar
          </span>
        </div>
      </div>

      {/* Filter and Search Bar */}
      <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Buscar por N° de comprobante (F001, B001, NV), cliente o RUC..."
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
          />
        </div>

        <select
          value={tipoFilter}
          onChange={(e) => setTipoFilter(e.target.value)}
          className="rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
        >
          <option value="">Todos los Comprobantes</option>
          <option value="FACTURA">Facturas Electrónicas (F001)</option>
          <option value="BOLETA">Boletas de Venta (B001)</option>
          <option value="NOTA_VENTA">Notas de Venta Internas (NV01)</option>
        </select>

        <select
          value={pagoFilter}
          onChange={(e) => setPagoFilter(e.target.value)}
          className="rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
        >
          <option value="">Todos los Estados de Pago</option>
          <option value="PENDIENTE">Pendientes de Pago</option>
          <option value="PARCIAL">Pagos Parciales (Amortizados)</option>
          <option value="PAGADO">Cobrados Totalmente</option>
        </select>
      </div>

      {/* Sales Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
              <tr>
                <th className="py-3 px-4">Comprobante</th>
                <th className="py-3 px-4">Cliente / RUC</th>
                <th className="py-3 px-4">Fecha Emisión</th>
                <th className="py-3 px-4 text-right">Total</th>
                <th className="py-3 px-4 w-44">Progreso de Cobro</th>
                <th className="py-3 px-4 text-center">Estado Pago</th>
                <th className="py-3 px-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    <div className="flex items-center justify-center gap-2">
                      <div className="h-4 w-4 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
                      <span>Cargando documentos de venta...</span>
                    </div>
                  </td>
                </tr>
              ) : ventas.length === 0 ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-slate-400">
                    No hay documentos de venta registrados. Puedes generar uno desde una Cotización Aprobada.
                  </td>
                </tr>
              ) : (
                ventas.map((v) => {
                  const pct = v.total > 0 ? Math.min(100, Math.round((v.total_pagado / v.total) * 100)) : 0;

                  return (
                    <tr
                      key={v.id_venta}
                      className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                    >
                      <td className="py-3 px-4">
                        <div className="flex items-center gap-2">
                          <span
                            className={`rounded-md px-1.5 py-0.5 text-[9px] font-black uppercase ${
                              v.tipo_documento === 'FACTURA'
                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300'
                                : v.tipo_documento === 'BOLETA'
                                ? 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300'
                                : 'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-300'
                            }`}
                          >
                            {v.tipo_documento === 'FACTURA' ? 'FAC' : v.tipo_documento === 'BOLETA' ? 'BOL' : 'NV'}
                          </span>
                          <span className="font-mono font-bold text-slate-900 dark:text-white">
                            {v.serie_correlativo}
                          </span>
                        </div>
                      </td>
                      <td className="py-3 px-4 font-semibold text-slate-900 dark:text-white max-w-xs">
                        <div>{v.cliente?.razon_social || 'Cliente'}</div>
                        <div className="text-[10px] text-slate-400 font-mono">
                          {v.cliente?.tipo_documento}: {v.cliente?.num_documento}
                        </div>
                      </td>
                      <td className="py-3 px-4 text-slate-500 dark:text-slate-400 font-mono">
                        {v.fecha_emision}
                      </td>
                      <td className="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                        {v.moneda} {Number(v.total).toFixed(2)}
                      </td>
                      <td className="py-3 px-4">
                        <div className="space-y-1">
                          <div className="flex justify-between text-[10px] font-mono">
                            <span className="text-emerald-600 dark:text-emerald-400 font-bold">
                              {v.moneda} {Number(v.total_pagado).toFixed(2)}
                            </span>
                            <span className="text-slate-400">
                              Saldo: {v.moneda} {Number(v.saldo_pendiente).toFixed(2)}
                            </span>
                          </div>
                          <div className="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div
                              className={`h-full rounded-full transition-all duration-500 ${
                                pct === 100 ? 'bg-emerald-500' : pct > 0 ? 'bg-amber-500' : 'bg-slate-300'
                              }`}
                              style={{ width: `${pct}%` }}
                            />
                          </div>
                        </div>
                      </td>
                      <td className="py-3 px-4 text-center">
                        <span
                          className={`inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold ${
                            v.estado_pago === 'PAGADO'
                              ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                              : v.estado_pago === 'PARCIAL'
                              ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
                              : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                          }`}
                        >
                          {v.estado_pago}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-center">
                        <div className="flex items-center justify-center gap-1.5">
                          {v.estado_pago !== 'PAGADO' && (
                            <button
                              onClick={() => handleOpenPayment(v)}
                              title="Registrar Cobranza / Amortización"
                              className="flex items-center gap-1 rounded-lg bg-emerald-50 px-2 py-1 text-[11px] font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-400"
                            >
                              <DollarSign className="h-3.5 w-3.5" />
                              <span>Cobrar</span>
                            </button>
                          )}
                          <button
                            onClick={() => handleViewVenta(v.id_venta)}
                            title="Ver / Imprimir Comprobante"
                            className="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-slate-800"
                          >
                            <Printer className="h-3.5 w-3.5" />
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

      {/* Modal Registrar Cobro */}
      {paymentModalOpen && selectedVentaForPayment && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
          <div className="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <div>
                <h3 className="text-base font-bold text-slate-900 dark:text-white">
                  Registrar Cobro / Amortización
                </h3>
                <span className="font-mono text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                  {selectedVentaForPayment.serie_correlativo}
                </span>
              </div>
              <button
                onClick={() => setPaymentModalOpen(false)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {/* Financial Overview Card */}
            <div className="mt-4 grid grid-cols-2 gap-2 rounded-2xl bg-slate-50 p-3.5 text-xs dark:bg-slate-800/60">
              <div>
                <span className="text-slate-500 dark:text-slate-400">Total Venta:</span>
                <p className="font-mono font-bold text-slate-900 dark:text-white">
                  {selectedVentaForPayment.moneda} {Number(selectedVentaForPayment.total).toFixed(2)}
                </p>
              </div>
              <div>
                <span className="text-slate-500 dark:text-slate-400">Saldo Pendiente:</span>
                <p className="font-mono font-bold text-amber-600 dark:text-amber-400">
                  {selectedVentaForPayment.moneda} {Number(selectedVentaForPayment.saldo_pendiente).toFixed(2)}
                </p>
              </div>
            </div>

            {paymentError && (
              <div className="mt-3 flex items-center gap-2 rounded-xl bg-rose-50 p-2.5 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>{paymentError}</span>
              </div>
            )}

            <form onSubmit={handleSavePayment} className="mt-4 space-y-3.5">
              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Monto a Cobrar ({selectedVentaForPayment.moneda}) *
                </label>
                <input
                  type="number"
                  step="0.01"
                  min="0.01"
                  max={selectedVentaForPayment.saldo_pendiente}
                  required
                  value={paymentData.monto}
                  onChange={(e) => setPaymentData({ ...paymentData, monto: e.target.value })}
                  className="w-full rounded-xl border border-emerald-300 bg-white p-2.5 text-sm font-mono font-bold text-emerald-700 focus:border-emerald-500 dark:border-emerald-700 dark:bg-slate-800 dark:text-emerald-300"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Método de Pago *
                  </label>
                  <select
                    value={paymentData.metodo_pago}
                    onChange={(e) => setPaymentData({ ...paymentData, metodo_pago: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="TRANSFERENCIA">Transferencia</option>
                    <option value="EFECTIVO">Efectivo</option>
                    <option value="YAPE_PLIN">Yape / Plin</option>
                    <option value="TARJETA">Tarjeta Débito/Crédito</option>
                    <option value="CHEQUE">Cheque</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Fecha de Cobro *
                  </label>
                  <input
                    type="date"
                    required
                    value={paymentData.fecha_pago}
                    onChange={(e) => setPaymentData({ ...paymentData, fecha_pago: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs font-mono text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Banco de Destino
                  </label>
                  <input
                    type="text"
                    value={paymentData.banco}
                    onChange={(e) => setPaymentData({ ...paymentData, banco: e.target.value })}
                    placeholder="BCP, BBVA, Interbank"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    N° Operación / Voucher
                  </label>
                  <input
                    type="text"
                    value={paymentData.referencia_operacion}
                    onChange={(e) => setPaymentData({ ...paymentData, referencia_operacion: e.target.value })}
                    placeholder="Ej. OP-120492"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs font-mono text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setPaymentModalOpen(false)}
                  className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={savingPayment}
                  className="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 disabled:opacity-50"
                >
                  {savingPayment ? 'Registrando...' : 'Confirmar Cobro'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Printable Sheet Modal */}
      {selectedVenta && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
          <div className="relative w-full max-w-3xl rounded-3xl border border-slate-200 bg-white p-8 shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-8">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800 mb-6">
              <div className="flex items-center gap-2">
                <span className="text-base font-bold text-slate-900 dark:text-white">
                  Comprobante de Venta
                </span>
                <span className="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                  {selectedVenta.serie_correlativo}
                </span>
              </div>
              <div className="flex items-center gap-2">
                <button
                  onClick={() => window.print()}
                  className="flex items-center gap-1.5 rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-400"
                >
                  <Printer className="h-4 w-4" />
                  <span>Imprimir Comprobante</span>
                </button>
                <button
                  onClick={() => setSelectedVenta(null)}
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
                <div>
                  <h2 className="text-lg font-black text-indigo-950">
                    {selectedVenta.empresa?.razon_social || 'Empresa Emisora'}
                  </h2>
                  <p className="text-xs text-slate-600">
                    RUC: {selectedVenta.empresa?.ruc}
                  </p>
                  <p className="text-xs text-slate-500">
                    {selectedVenta.empresa?.direccion || 'Arequipa, Perú'}
                  </p>
                </div>
                <div className="text-right">
                  <div className="rounded-xl bg-slate-50 p-2.5 border-2 border-slate-800 text-center">
                    <p className="text-[10px] uppercase font-bold text-slate-700">
                      R.U.C. {selectedVenta.empresa?.ruc}
                    </p>
                    <p className="text-xs font-black text-indigo-950 uppercase tracking-wider my-0.5">
                      {selectedVenta.tipo_documento.replace('_', ' ')}
                    </p>
                    <p className="text-sm font-mono font-black text-slate-900">
                      N° {selectedVenta.serie_correlativo}
                    </p>
                  </div>
                  <p className="mt-1 text-xs text-slate-500">
                    Fecha Emisión: {selectedVenta.fecha_emision}
                  </p>
                </div>
              </div>

              {/* Client Info */}
              <div className="rounded-xl bg-slate-50 p-3 text-xs grid grid-cols-2 gap-4">
                <div>
                  <p className="text-slate-500 font-medium">SEÑOR(ES):</p>
                  <p className="font-bold text-slate-900">{selectedVenta.cliente?.razon_social}</p>
                  <p className="text-slate-600">RUC/DNI: {selectedVenta.cliente?.num_documento}</p>
                  <p className="text-slate-500">{selectedVenta.cliente?.direccion}</p>
                </div>
                <div>
                  <p className="text-slate-500 font-medium">DETALLES DE PAGO:</p>
                  <p>Forma de Pago: <span className="font-semibold">{selectedVenta.forma_pago}</span></p>
                  <p>Moneda: <span className="font-semibold">{selectedVenta.moneda}</span></p>
                  <p>Estado: <span className="font-bold text-emerald-700">{selectedVenta.estado_pago}</span></p>
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
                  {selectedVenta.detalles?.map((item, idx) => (
                    <tr key={item.id_detalle}>
                      <td className="py-2 px-2 text-center text-slate-500">{idx + 1}</td>
                      <td className="py-2 px-2 font-medium text-slate-800">{item.descripcion}</td>
                      <td className="py-2 px-2 text-center font-mono">{item.cantidad}</td>
                      <td className="py-2 px-2 text-center text-slate-500">{item.unidad_medida}</td>
                      <td className="py-2 px-2 text-right font-mono">
                        {Number(item.precio_unitario).toFixed(2)}
                      </td>
                      <td className="py-2 px-2 text-right font-mono font-bold">
                        {Number(item.subtotal).toFixed(2)}
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
                    <span className="font-mono">{selectedVenta.moneda} {Number(selectedVenta.subtotal).toFixed(2)}</span>
                  </div>
                  <div className="flex justify-between text-slate-600">
                    <span>IGV (18%):</span>
                    <span className="font-mono">{selectedVenta.moneda} {Number(selectedVenta.igv).toFixed(2)}</span>
                  </div>
                  <div className="flex justify-between border-t border-slate-300 pt-1 font-bold text-sm text-slate-900">
                    <span>TOTAL:</span>
                    <span className="font-mono text-indigo-700">{selectedVenta.moneda} {Number(selectedVenta.total).toFixed(2)}</span>
                  </div>
                </div>
              </div>

              {/* Payments History on the receipt */}
              {selectedVenta.pagos?.length > 0 && (
                <div className="pt-4 border-t border-slate-200">
                  <p className="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
                    Amortizaciones y Pagos Registrados
                  </p>
                  <table className="w-full text-[11px]">
                    <thead>
                      <tr className="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <th className="py-1 px-2 text-left">Fecha</th>
                        <th className="py-1 px-2 text-left">Método</th>
                        <th className="py-1 px-2 text-left">Referencia / Banco</th>
                        <th className="py-1 px-2 text-right">Monto Pagado</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {selectedVenta.pagos.map((p) => (
                        <tr key={p.id_pago}>
                          <td className="py-1 px-2 font-mono">{p.fecha_pago}</td>
                          <td className="py-1 px-2">{p.metodo_pago}</td>
                          <td className="py-1 px-2 text-slate-500">
                            {p.referencia_operacion ? `${p.referencia_operacion} (${p.banco || ''})` : '-'}
                          </td>
                          <td className="py-1 px-2 text-right font-mono font-bold text-emerald-600">
                            {selectedVenta.moneda} {Number(p.monto).toFixed(2)}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Modal Emitir Nueva Venta Directa */}
      {createModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
          <div className="relative w-full max-w-4xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-8">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <div className="flex items-center gap-2.5">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                  <Receipt className="h-5 w-5" />
                </div>
                <div>
                  <h3 className="text-base font-bold text-slate-900 dark:text-white">
                    Emitir Nueva Venta Directa
                  </h3>
                  <p className="text-xs text-slate-400">
                    Venta directa en mostrador o facturación inmediata sin requerir cotización previa.
                  </p>
                </div>
              </div>
              <button
                onClick={() => setCreateModalOpen(false)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
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

            <form onSubmit={handleSaveDirectVenta} className="mt-4 space-y-4">
              {/* Header Fields */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="sm:col-span-1">
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Cliente *
                  </label>
                  <select
                    required
                    value={createFormData.id_cliente}
                    onChange={(e) => setCreateFormData({ ...createFormData, id_cliente: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  >
                    <option value="">-- Seleccionar Cliente --</option>
                    {clientes.map((cli) => (
                      <option key={cli.id_cliente} value={cli.id_cliente}>
                        {cli.razon_social} ({cli.num_documento})
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Tipo de Comprobante
                  </label>
                  <select
                    value={createFormData.tipo_documento}
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
                    value={createFormData.serie}
                    onChange={(e) => setCreateFormData({ ...createFormData, serie: e.target.value.toUpperCase() })}
                    placeholder="Ej. F001, B001, NV01"
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Moneda
                  </label>
                  <select
                    value={createFormData.moneda}
                    onChange={(e) => setCreateFormData({ ...createFormData, moneda: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  >
                    <option value="PEN">Soles (PEN - S/)</option>
                    <option value="USD">Dólares (USD - $)</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Condición de Pago
                  </label>
                  <input
                    type="text"
                    value={createFormData.forma_pago}
                    onChange={(e) => setCreateFormData({ ...createFormData, forma_pago: e.target.value })}
                    placeholder="Contado / Crédito"
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Fecha de Emisión
                  </label>
                  <input
                    type="date"
                    required
                    value={createFormData.fecha_emision}
                    onChange={(e) => setCreateFormData({ ...createFormData, fecha_emision: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Descontar Stock Físico
                  </label>
                  <select
                    value={createFormData.descontar_stock_almacen_id}
                    onChange={(e) => setCreateFormData({ ...createFormData, descontar_stock_almacen_id: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                  >
                    <option value="">Sin descuento (Servicios)</option>
                    {almacenes.map((a) => (
                      <option key={a.id_almacen} value={a.id_almacen}>
                        {a.nombre_almacen}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Items Section */}
              <div className="pt-2">
                <div className="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                  <h4 className="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                    Productos y Servicios a Facturar
                  </h4>
                  <button
                    type="button"
                    onClick={handleAddItem}
                    className="flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                  >
                    <Plus className="h-3.5 w-3.5" />
                    <span>Agregar Ítem</span>
                  </button>
                </div>

                <div className="mt-3 space-y-2 max-h-60 overflow-y-auto pr-1">
                  {detalles.map((det, idx) => (
                    <div
                      key={idx}
                      className="grid grid-cols-12 gap-2 items-center rounded-2xl bg-slate-50 p-2.5 dark:bg-slate-800/60"
                    >
                      <div className="col-span-12 sm:col-span-5">
                        <select
                          value={det.id_producto || ''}
                          onChange={(e) => handleItemProductSelect(idx, e.target.value)}
                          className="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white mb-1"
                        >
                          <option value="">-- Ítem de Catálogo (Opcional) --</option>
                          {productos.map((p) => (
                            <option key={p.id_producto} value={p.id_producto}>
                              {p.nombre_producto} ({p.codigo_producto})
                            </option>
                          ))}
                        </select>
                        <input
                          type="text"
                          required
                          placeholder="Descripción del ítem *"
                          value={det.descripcion}
                          onChange={(e) => handleItemChange(idx, 'descripcion', e.target.value)}
                          className="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                        />
                      </div>

                      <div className="col-span-3 sm:col-span-2">
                        <label className="text-[10px] text-slate-400 block mb-0.5">Cantidad</label>
                        <input
                          type="number"
                          step="any"
                          min="0.01"
                          required
                          value={det.cantidad}
                          onChange={(e) => handleItemChange(idx, 'cantidad', e.target.value)}
                          className="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-center font-mono dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                        />
                      </div>

                      <div className="col-span-3 sm:col-span-2">
                        <label className="text-[10px] text-slate-400 block mb-0.5">Precio Unit.</label>
                        <input
                          type="number"
                          step="any"
                          min="0"
                          required
                          value={det.precio_unitario}
                          onChange={(e) => handleItemChange(idx, 'precio_unitario', e.target.value)}
                          className="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-right font-mono dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                        />
                      </div>

                      <div className="col-span-4 sm:col-span-2 text-right">
                        <label className="text-[10px] text-slate-400 block mb-0.5">Subtotal</label>
                        <span className="text-xs font-mono font-bold text-slate-800 dark:text-slate-200">
                          {((parseFloat(det.cantidad) || 0) * (parseFloat(det.precio_unitario) || 0)).toFixed(2)}
                        </span>
                      </div>

                      <div className="col-span-2 sm:col-span-1 flex justify-center">
                        <button
                          type="button"
                          onClick={() => handleRemoveItem(idx)}
                          disabled={detalles.length === 1}
                          className="rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30 dark:hover:bg-rose-950/40"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Totals Summary */}
              <div className="rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                  <div className="text-xs text-slate-500">
                    Empresa Emisora: <strong className="text-slate-700 dark:text-slate-300">{activeEmpresa?.razon_social}</strong>
                  </div>
                  <div className="flex items-center gap-6 self-end text-xs">
                    <div>
                      <span className="text-slate-500">Subtotal: </span>
                      <span className="font-mono font-bold">{createFormData.moneda} {subtotalNuevo.toFixed(2)}</span>
                    </div>
                    <div>
                      <span className="text-slate-500">IGV (18%): </span>
                      <span className="font-mono font-bold">{createFormData.moneda} {igvNuevo.toFixed(2)}</span>
                    </div>
                    <div className="text-sm">
                      <span className="font-bold text-slate-700 dark:text-slate-300">TOTAL: </span>
                      <span className="font-mono font-bold text-indigo-600 dark:text-indigo-400">{createFormData.moneda} {totalNuevo.toFixed(2)}</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Cobro Inicial Opcional */}
              <div className="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-3 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={pagoInmediato}
                    onChange={(e) => {
                      setPagoInmediato(e.target.checked);
                      if (e.target.checked && !pagoInicialData.monto) {
                        setPagoInicialData({ ...pagoInicialData, monto: totalNuevo.toFixed(2) });
                      }
                    }}
                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                  />
                  <span className="text-xs font-bold text-indigo-900 dark:text-indigo-300">
                    Registrar Cobro Inmediato (Venta al Contado / Cuota Inicial)
                  </span>
                </label>

                {pagoInmediato && (
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3 pt-3 border-t border-indigo-100 dark:border-indigo-900/40">
                    <div>
                      <label className="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Monto Cobrado
                      </label>
                      <input
                        type="number"
                        step="any"
                        min="0.01"
                        max={totalNuevo}
                        required={pagoInmediato}
                        value={pagoInicialData.monto}
                        onChange={(e) => setPagoInicialData({ ...pagoInicialData, monto: e.target.value })}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 font-mono dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Método de Cobro
                      </label>
                      <select
                        value={pagoInicialData.metodo_pago}
                        onChange={(e) => setPagoInicialData({ ...pagoInicialData, metodo_pago: e.target.value })}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                      >
                        <option value="EFECTIVO">Efectivo en Caja</option>
                        <option value="TRANSFERENCIA">Transferencia Bancaria</option>
                        <option value="YAPE_PLIN">Yape / Plin</option>
                        <option value="TARJETA">Tarjeta Débito / Crédito</option>
                        <option value="CHEQUE">Cheque</option>
                      </select>
                    </div>

                    <div>
                      <label className="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Ref. Operación / Banco
                      </label>
                      <input
                        type="text"
                        value={pagoInicialData.referencia_operacion}
                        onChange={(e) => setPagoInicialData({ ...pagoInicialData, referencia_operacion: e.target.value })}
                        placeholder="N° Op. / BCP, BBVA, etc."
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                      />
                    </div>
                  </div>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Observaciones / Glosa
                </label>
                <textarea
                  rows="2"
                  value={createFormData.observaciones}
                  onChange={(e) => setCreateFormData({ ...createFormData, observaciones: e.target.value })}
                  placeholder="Detalles adicionales de la venta..."
                  className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
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
                  disabled={savingVenta}
                  className="flex items-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50"
                >
                  <Receipt className="h-4 w-4" />
                  <span>{savingVenta ? 'Emitiendo comprobante...' : 'Emitir Venta Directa'}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
