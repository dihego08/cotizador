import React from 'react';
import { Package, AlertCircle, CheckCircle } from 'lucide-react';

// Contenedor de las pantallas de acceso (mismo estilo que el login)
export default function AuthShell({ title, subtitle, error, success, children }) {
  return (
    <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-900 px-4 py-12">
      <div className="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-indigo-600/30 blur-[128px]" />
      <div className="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-violet-600/30 blur-[128px]" />

      <div className="relative w-full max-w-md">
        <div className="rounded-3xl border border-slate-700/60 bg-slate-800/80 p-8 shadow-2xl backdrop-blur-xl">
          <div className="mb-6 text-center">
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-500 text-white shadow-lg shadow-indigo-500/30">
              <Package className="h-8 w-8" />
            </div>
            <h1 className="text-xl font-bold tracking-tight text-white">{title}</h1>
            {subtitle && <p className="mt-1 text-xs text-slate-400">{subtitle}</p>}
          </div>

          {error && (
            <div className="mb-5 flex items-start gap-3 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-3.5 text-xs text-rose-300">
              <AlertCircle className="h-5 w-5 shrink-0 text-rose-400" />
              <span>{error}</span>
            </div>
          )}

          {success && (
            <div className="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 text-xs text-emerald-300">
              <CheckCircle className="h-5 w-5 shrink-0 text-emerald-400" />
              <span>{success}</span>
            </div>
          )}

          {children}
        </div>
      </div>
    </div>
  );
}
