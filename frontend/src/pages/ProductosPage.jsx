import React, { useState, useEffect } from 'react';
import {
  Boxes,
  Plus,
  Search,
  Filter,
  Edit2,
  Trash2,
  Barcode,
  Tag,
  DollarSign,
  X,
  AlertCircle,
  CheckCircle,
} from 'lucide-react';
import api from '../api/client';

export default function ProductosPage() {
  const [productos, setProductos] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [categoriaFilter, setCategoriaFilter] = useState('');
  const [tipoFilter, setTipoFilter] = useState('');

  // Modal State
  const [modalOpen, setModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [formData, setFormData] = useState({
    tipo: 'PRODUCTO',
    id_categoria: '',
    codigo_interno: '',
    codigo_barra: '',
    codigo_proveedor: '',
    nombre_producto: '',
    descripcion: '',
    numero_parte: '',
    marca: '',
    modelo: '',
    unidad_medida: 'UND',
    moneda: 'PEN',
    precio_costo: '',
    porcentaje_utilidad: '15.00',
    precio_venta: '',
  });
  const [formError, setFormError] = useState('');
  const [formSuccess, setFormSuccess] = useState('');
  const [saving, setSaving] = useState(false);

  const fetchProductos = async () => {
    setLoading(true);
    try {
      const res = await api.get('/productos', {
        params: {
          search,
          id_categoria: categoriaFilter || undefined,
          tipo: tipoFilter || undefined,
        },
      });
      if (res.data?.data) {
        setProductos(res.data.data.data || res.data.data);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const fetchCategorias = async () => {
    try {
      const res = await api.get('/categorias');
      if (res.data?.data) {
        setCategorias(res.data.data);
      }
    } catch (e) {
      console.error(e);
    }
  };

  useEffect(() => {
    fetchCategorias();
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchProductos();
    }, 300);
    return () => clearTimeout(timer);
  }, [search, categoriaFilter, tipoFilter]);

  const handleOpenCreate = () => {
    setEditingProduct(null);
    setFormData({
      tipo: 'PRODUCTO',
      id_categoria: '',
      codigo_interno: 'PROD-' + Date.now().toString().slice(-6),
      codigo_barra: '',
      codigo_proveedor: '',
      nombre_producto: '',
      descripcion: '',
      numero_parte: '',
      marca: '',
      modelo: '',
      unidad_medida: 'UND',
      moneda: 'PEN',
      precio_costo: '',
      porcentaje_utilidad: '15.00',
      precio_venta: '',
    });
    setFormError('');
    setFormSuccess('');
    setModalOpen(true);
  };

  const handleOpenEdit = (p) => {
    setEditingProduct(p);
    setFormData({
      tipo: p.tipo || 'PRODUCTO',
      id_categoria: p.id_categoria || '',
      codigo_interno: p.codigo_interno || '',
      codigo_barra: p.codigo_barra || '',
      codigo_proveedor: p.codigo_proveedor || '',
      nombre_producto: p.nombre_producto || '',
      descripcion: p.descripcion || '',
      numero_parte: p.numero_parte || '',
      marca: p.marca || '',
      modelo: p.modelo || '',
      unidad_medida: p.unidad_medida || 'UND',
      moneda: p.moneda || 'PEN',
      precio_costo: p.precio_costo || '',
      porcentaje_utilidad: p.porcentaje_utilidad || '0.00',
      precio_venta: p.precio_venta || '',
    });
    setFormError('');
    setFormSuccess('');
    setModalOpen(true);
  };

  // Profit Margin Calculator
  const handleCostoChange = (val) => {
    const costo = parseFloat(val) || 0;
    const utilidad = parseFloat(formData.porcentaje_utilidad) || 0;
    const venta = costo > 0 ? (costo * (1 + utilidad / 100)).toFixed(2) : '';
    setFormData((prev) => ({
      ...prev,
      precio_costo: val,
      precio_venta: venta,
    }));
  };

  const handleUtilidadChange = (val) => {
    const utilidad = parseFloat(val) || 0;
    const costo = parseFloat(formData.precio_costo) || 0;
    const venta = costo > 0 ? (costo * (1 + utilidad / 100)).toFixed(2) : '';
    setFormData((prev) => ({
      ...prev,
      porcentaje_utilidad: val,
      precio_venta: venta,
    }));
  };

  const handleVentaChange = (val) => {
    const venta = parseFloat(val) || 0;
    const costo = parseFloat(formData.precio_costo) || 0;
    let utilidad = '0.00';
    if (costo > 0 && venta >= costo) {
      utilidad = (((venta - costo) / costo) * 100).toFixed(2);
    }
    setFormData((prev) => ({
      ...prev,
      precio_venta: val,
      porcentaje_utilidad: utilidad,
    }));
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setFormError('');
    setSaving(true);

    try {
      if (editingProduct) {
        await api.put(`/productos/${editingProduct.id_producto}`, formData);
        setFormSuccess('Producto actualizado correctamente');
      } else {
        await api.post('/productos', formData);
        setFormSuccess('Producto registrado correctamente');
      }
      setTimeout(() => {
        setModalOpen(false);
        fetchProductos();
      }, 500);
    } catch (err) {
      setFormError(
        err.response?.data?.message || 'Error al guardar los datos del producto'
      );
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('¿Estás seguro de eliminar este producto?')) return;
    try {
      await api.delete(`/productos/${id}`);
      fetchProductos();
    } catch (err) {
      alert('Error al eliminar producto.');
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Catálogo de Productos y Servicios
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Administración centralizada de ítems, precios, códigos de barra y márgenes de ganancia.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          <span>Nuevo Producto / Servicio</span>
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
            placeholder="Buscar por código, nombre, marca o modelo..."
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-xs text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400"
          />
        </div>

        <div className="flex items-center gap-2">
          <select
            value={categoriaFilter}
            onChange={(e) => setCategoriaFilter(e.target.value)}
            className="rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
          >
            <option value="">Todas las categorías</option>
            {categorias.map((c) => (
              <option key={c.id_categoria} value={c.id_categoria}>
                {c.nombre_categoria}
              </option>
            ))}
          </select>

          <select
            value={tipoFilter}
            onChange={(e) => setTipoFilter(e.target.value)}
            className="rounded-xl border border-slate-200 bg-slate-50 py-2 px-3 text-xs text-slate-700 focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
          >
            <option value="">Todos los tipos</option>
            <option value="PRODUCTO">Productos</option>
            <option value="SERVICIO">Servicios</option>
          </select>
        </div>
      </div>

      {/* Products Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
              <tr>
                <th className="py-3 px-4">Código / SKU</th>
                <th className="py-3 px-4">Descripción del Producto</th>
                <th className="py-3 px-4">Categoría</th>
                <th className="py-3 px-4">Marca / Modelo</th>
                <th className="py-3 px-4 text-right">P. Costo</th>
                <th className="py-3 px-4 text-right">Margen %</th>
                <th className="py-3 px-4 text-right">P. Venta</th>
                <th className="py-3 px-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan="8" className="py-12 text-center text-slate-400">
                    <div className="flex items-center justify-center gap-2">
                      <div className="h-4 w-4 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
                      <span>Cargando catálogo...</span>
                    </div>
                  </td>
                </tr>
              ) : productos.length === 0 ? (
                <tr>
                  <td colSpan="8" className="py-12 text-center text-slate-400">
                    No se encontraron productos registrados.
                  </td>
                </tr>
              ) : (
                productos.map((prod) => (
                  <tr
                    key={prod.id_producto}
                    className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                  >
                    <td className="py-3 px-4">
                      <div className="flex flex-col">
                        <span className="font-mono font-bold text-slate-800 dark:text-slate-200">
                          {prod.codigo_interno}
                        </span>
                        {prod.codigo_barra && (
                          <span className="text-[10px] text-slate-400 flex items-center gap-1">
                            <Barcode className="h-3 w-3" />
                            {prod.codigo_barra}
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="py-3 px-4 font-medium text-slate-900 dark:text-white max-w-xs">
                      <div>{prod.nombre_producto}</div>
                      {prod.descripcion && (
                        <div className="text-[11px] text-slate-400 truncate">
                          {prod.descripcion}
                        </div>
                      )}
                    </td>
                    <td className="py-3 px-4 text-slate-600 dark:text-slate-400">
                      {prod.categoria?.nombre_categoria || 'Sin categoría'}
                    </td>
                    <td className="py-3 px-4 text-slate-600 dark:text-slate-400">
                      {prod.marca ? `${prod.marca} ${prod.modelo || ''}` : '-'}
                    </td>
                    <td className="py-3 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                      {prod.moneda} {Number(prod.precio_costo).toFixed(2)}
                    </td>
                    <td className="py-3 px-4 text-right">
                      <span className="rounded-md bg-emerald-50 px-2 py-0.5 font-mono text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                        {Number(prod.porcentaje_utilidad).toFixed(1)}%
                      </span>
                    </td>
                    <td className="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                      {prod.moneda} {Number(prod.precio_venta).toFixed(2)}
                    </td>
                    <td className="py-3 px-4 text-center">
                      <div className="flex items-center justify-center gap-1.5">
                        <button
                          onClick={() => handleOpenEdit(prod)}
                          title="Editar"
                          className="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-slate-800"
                        >
                          <Edit2 className="h-3.5 w-3.5" />
                        </button>
                        <button
                          onClick={() => handleDelete(prod.id_producto)}
                          title="Eliminar"
                          className="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600 dark:text-slate-400 dark:hover:bg-rose-950/40"
                        >
                          <Trash2 className="h-3.5 w-3.5" />
                        </button>
                      </div>
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
                {editingProduct ? 'Editar Producto / Servicio' : 'Nuevo Producto / Servicio'}
              </h3>
              <button
                onClick={() => setModalOpen(false)}
                className="rounded-xl p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            {formError && (
              <div className="mt-4 flex items-center gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/40 dark:text-rose-400">
                <AlertCircle className="h-4 w-4 shrink-0" />
                <span>{formError}</span>
              </div>
            )}

            {formSuccess && (
              <div className="mt-4 flex items-center gap-2 rounded-xl bg-emerald-50 p-3 text-xs text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                <CheckCircle className="h-4 w-4 shrink-0" />
                <span>{formSuccess}</span>
              </div>
            )}

            <form onSubmit={handleSave} className="mt-4 space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Tipo de Ítem
                  </label>
                  <select
                    value={formData.tipo}
                    onChange={(e) => setFormData({ ...formData, tipo: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="PRODUCTO">PRODUCTO (Físico)</option>
                    <option value="SERVICIO">SERVICIO (Intangible)</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Código Interno / SKU *
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.codigo_interno}
                    onChange={(e) => setFormData({ ...formData, codigo_interno: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Código de Barras
                  </label>
                  <input
                    type="text"
                    value={formData.codigo_barra}
                    onChange={(e) => setFormData({ ...formData, codigo_barra: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Nombre del Producto *
                </label>
                <input
                  type="text"
                  required
                  value={formData.nombre_producto}
                  onChange={(e) => setFormData({ ...formData, nombre_producto: e.target.value })}
                  placeholder="Ej. Memoria RAM DDR4 16GB Kingston"
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Categoría
                  </label>
                  <select
                    value={formData.id_categoria}
                    onChange={(e) => setFormData({ ...formData, id_categoria: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="">Seleccione Categoría</option>
                    {categorias.map((c) => (
                      <option key={c.id_categoria} value={c.id_categoria}>
                        {c.nombre_categoria}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Marca
                  </label>
                  <input
                    type="text"
                    value={formData.marca}
                    onChange={(e) => setFormData({ ...formData, marca: e.target.value })}
                    placeholder="Ej. Brother, HP, Cisco"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Unidad de Medida
                  </label>
                  <select
                    value={formData.unidad_medida}
                    onChange={(e) => setFormData({ ...formData, unidad_medida: e.target.value })}
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  >
                    <option value="UND">Unidad (UND)</option>
                    <option value="KG">Kilogramos (KG)</option>
                    <option value="MTR">Metros (MTR)</option>
                    <option value="PAQ">Paquete (PAQ)</option>
                    <option value="SRV">Servicio (SRV)</option>
                  </select>
                </div>
              </div>

              {/* Pricing & Margin Calculator Card */}
              <div className="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-4 dark:border-indigo-950 dark:bg-indigo-950/20">
                <p className="text-xs font-bold text-indigo-900 dark:text-indigo-300 mb-3 flex items-center gap-1.5">
                  <DollarSign className="h-4 w-4 text-indigo-600" />
                  Calculadora de Precios y Margen de Utilidad
                </p>
                <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Moneda
                    </label>
                    <select
                      value={formData.moneda}
                      onChange={(e) => setFormData({ ...formData, moneda: e.target.value })}
                      className="w-full rounded-xl border border-slate-200 bg-white p-2 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    >
                      <option value="PEN">Soles (PEN)</option>
                      <option value="USD">Dólares (USD)</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Precio Costo
                    </label>
                    <input
                      type="number"
                      step="0.01"
                      value={formData.precio_costo}
                      onChange={(e) => handleCostoChange(e.target.value)}
                      placeholder="0.00"
                      className="w-full rounded-xl border border-slate-200 bg-white p-2 text-xs font-mono font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Margen Utilidad %
                    </label>
                    <input
                      type="number"
                      step="0.1"
                      value={formData.porcentaje_utilidad}
                      onChange={(e) => handleUtilidadChange(e.target.value)}
                      placeholder="15.0"
                      className="w-full rounded-xl border border-slate-200 bg-white p-2 text-xs font-mono font-bold text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-indigo-400"
                    />
                  </div>

                  <div>
                    <label className="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                      Precio Venta (Sugerido)
                    </label>
                    <input
                      type="number"
                      step="0.01"
                      value={formData.precio_venta}
                      onChange={(e) => handleVentaChange(e.target.value)}
                      placeholder="0.00"
                      className="w-full rounded-xl border border-indigo-300 bg-white p-2 text-xs font-mono font-bold text-emerald-700 dark:border-indigo-600 dark:bg-slate-800 dark:text-emerald-400"
                    />
                  </div>
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Descripción Adicional
                </label>
                <textarea
                  rows="2"
                  value={formData.descripcion}
                  onChange={(e) => setFormData({ ...formData, descripcion: e.target.value })}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
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
                  {saving ? 'Guardando...' : 'Guardar Producto'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
