// Estilos y utilidades compartidas por las pantallas de acceso

export const authInputClass =
  'w-full rounded-xl border border-slate-700 bg-slate-900/60 py-3 px-4 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500';

export const authButtonClass =
  'flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-indigo-600 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 transition-all hover:from-indigo-600 hover:to-indigo-700 focus:outline-hidden disabled:opacity-50';

export const authLabelClass = 'mb-2 block text-xs font-semibold text-slate-300';

// Primer mensaje de error de una respuesta de validación de Laravel
export const primerError = (err, porDefecto) => {
  if (err.response?.status === 429) {
    return 'Demasiados intentos. Espera un minuto antes de volver a intentarlo.';
  }
  const errores = err.response?.data?.errors;
  if (errores) {
    const primero = Object.values(errores)[0];
    if (primero?.[0]) return primero[0];
  }
  return err.response?.data?.message || porDefecto;
};
