import React, { useState, useEffect } from 'react';
import {
  Tags,
  Award,
  Plus,
  Search,
  Edit2,
  Trash2,
  X,
  AlertCircle,
} from 'lucide-react';
import api from '../api/client';

// Configuración de cada pestaña: endpoint, campos y textos
const TABS = {
  categorias: {
    label: 'Categorías',
    singular: 'Categoría',
    icon: Tags,
    endpoint: '/categorias',
    idKey: 'id_categoria',
    nameKey: 'nombre_categoria',
    hasDescripcion: true,
    placeholder: 'Ej. Computadoras, Periféricos',
  },
  marcas: {
    label: 'Marcas',
    singular: 'Marca',
    icon: Award,
    endpoint: '/marcas',
    idKey: 'id_marca',
    nameKey: 'nombre_marca',
    hasDescripcion: false,
    placeholder: 'Ej. HP, Lenovo, Cisco',
  },
};

export default function CategoriasMarcasPage() {
  const [activeTab, setActiveTab] = useState('categorias');
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  // Modal State
  const [modalOpen, setModalOpen] = useState(false);
  const [editingItem, setEditingItem] = useState(null);
  const [formData, setFormData] = useState({ nombre: '', descripcion: '' });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const tab = TABS[activeTab];

  const fetchItems = async () => {
    setLoading(true);
    try {
      const res = await api.get(tab.endpoint);
      if (res.data?.data) {
        setItems(res.data.data);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    setItems([]);
    setSearch('');
    fetchItems();
  }, [activeTab]);

  const filteredItems = items.filter((item) =>
    item[tab.nameKey]?.toLowerCase().includes(search.toLowerCase())
  );

  const handleOpenCreate = () => {
    setEditingItem(null);
    setFormData({ nombre: '', descripcion: '' });
    setError('');
    setModalOpen(true);
  };

  const handleOpenEdit = (item) => {
    setEditingItem(item);
    setFormData({
      nombre: item[tab.nameKey] || '',
      descripcion: item.descripcion || '',
    });
    setError('');
    setModalOpen(true);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    const payload = { [tab.nameKey]: formData.nombre };
    if (tab.hasDescripcion) payload.descripcion = formData.descripcion;

    try {
      if (editingItem) {
        await api.put(`${tab.endpoint}/${editingItem[tab.idKey]}`, payload);
      } else {
        await api.post(tab.endpoint, payload);
      }
      setModalOpen(false);
      fetchItems();
    } catch (err) {
      setError(err.response?.data?.message || `Error al guardar la ${tab.singular.toLowerCase()}.`);
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (item) => {
    if (!window.confirm(`¿Seguro de eliminar "${item[tab.nameKey]}"?`)) return;
    try {
      await api.delete(`${tab.endpoint}/${item[tab.idKey]}`);
      fetchItems();
    } catch (e) {
      alert(e.response?.data?.message || `Error al eliminar la ${tab.singular.toLowerCase()}.`);
    }
  };

  const TabIcon = tab.icon;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            Categorías y Marcas
          </h1>
          <p className="text-xs text-slate-500 dark:text-slate-400">
            Catálogos utilizados para clasificar los productos y servicios.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <Plus className="h-4 w-4" />
          <span>Nueva {tab.singular}</span>
        </button>
      </div>

      {/* Tabs + Search */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="inline-flex rounded-xl border border-slate-200 bg-white p-1 dark:border-slate-800 dark:bg-slate-900">
          {Object.entries(TABS).map(([key, t]) => {
            const Icon = t.icon;
            return (
              <button
                key={key}
                onClick={() => setActiveTab(key)}
                className={`flex items-center gap-2 rounded-lg px-4 py-1.5 text-xs font-semibold transition-colors ${
                  activeTab === key
                    ? 'bg-indigo-600 text-white shadow-xs'
                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
                }`}
              >
                <Icon className="h-3.5 w-3.5" />
                <span>{t.label}</span>
              </button>
            );
          })}
        </div>

        <div className="relative w-full sm:w-72">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={`Buscar ${tab.label.toLowerCase()}...`}
            className="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"
          />
        </div>
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-100 bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
              <tr>
                <th className="py-3 px-4">{tab.singular}</th>
                {tab.hasDescripcion && <th className="py-3 px-4">Descripción</th>}
                <th className="py-3 px-4 text-center">Productos</th>
                <th className="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan={4} className="py-12 text-center text-slate-400">
                    Cargando {tab.label.toLowerCase()}...
                  </td>
                </tr>
              ) : filteredItems.length === 0 ? (
                <tr>
                  <td colSpan={4} className="py-12 text-center text-slate-400">
                    No hay {tab.label.toLowerCase()} registradas.
                  </td>
                </tr>
              ) : (
                filteredItems.map((item) => (
                  <tr
                    key={item[tab.idKey]}
                    className="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                  >
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                          <TabIcon className="h-4 w-4" />
                        </div>
                        <span className="font-semibold text-slate-900 dark:text-white">
                          {item[tab.nameKey]}
                        </span>
                      </div>
                    </td>
                    {tab.hasDescripcion && (
                      <td className="py-3 px-4 text-slate-500 dark:text-slate-400">
                        {item.descripcion || '-'}
                      </td>
                    )}
                    <td className="py-3 px-4 text-center">
                      <span className="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {item.productos_count ?? 0}
                      </span>
                    </td>
                    <td className="py-3 px-4">
                      <div className="flex items-center justify-end gap-1">
                        <button
                          onClick={() => handleOpenEdit(item)}
                          className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800"
                        >
                          <Edit2 className="h-3.5 w-3.5" />
                        </button>
                        <button
                          onClick={() => handleDelete(item)}
                          className="rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
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
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
          <div className="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
              <h3 className="text-base font-bold text-slate-900 dark:text-white">
                {editingItem ? `Editar ${tab.singular}` : `Nueva ${tab.singular}`}
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
                  Nombre *
                </label>
                <input
                  type="text"
                  required
                  maxLength={100}
                  value={formData.nombre}
                  onChange={(e) => setFormData({ ...formData, nombre: e.target.value })}
                  placeholder={tab.placeholder}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                />
                {editingItem && activeTab === 'marcas' && editingItem.productos_count > 0 && (
                  <p className="mt-1 text-[11px] text-slate-400">
                    Al renombrarla se actualizarán los {editingItem.productos_count} producto(s) asociados.
                  </p>
                )}
              </div>

              {tab.hasDescripcion && (
                <div>
                  <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                    Descripción
                  </label>
                  <textarea
                    rows={3}
                    maxLength={255}
                    value={formData.descripcion}
                    onChange={(e) => setFormData({ ...formData, descripcion: e.target.value })}
                    placeholder="Ej. Monitores, Teclados, Mouse, Impresoras"
                    className="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                  />
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
                  {saving ? 'Guardando...' : `Guardar ${tab.singular}`}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
