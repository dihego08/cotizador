import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Package,
  Mail,
  Lock,
  ArrowRight,
  AlertCircle,
  ShieldCheck,
  CheckCircle2,
} from 'lucide-react';
import { useAuth } from '../context/AuthContext';

export default function LoginPage() {
  const [email, setEmail] = useState('admin@ruvindustrias.com');
  const [password, setPassword] = useState('admin123');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await login(email, password);
      navigate('/');
    } catch (err) {
      setError(
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.message ||
        'Error al iniciar sesión. Verifica tus credenciales.'
      );
    } finally {
      setLoading(false);
    }
  };

  const handleQuickFill = (presetEmail, presetPass) => {
    setEmail(presetEmail);
    setPassword(presetPass);
  };

  return (
    <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-900 px-4 py-12">
      {/* Dynamic ambient backdrop */}
      <div className="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-indigo-600/30 blur-[128px]" />
      <div className="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-violet-600/30 blur-[128px]" />

      <div className="relative w-full max-w-md">
        {/* Main Card */}
        <div className="rounded-3xl border border-slate-700/60 bg-slate-800/80 p-8 shadow-2xl backdrop-blur-xl">
          {/* Header */}
          <div className="mb-8 text-center">
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-500 text-white shadow-lg shadow-indigo-500/30">
              <Package className="h-8 w-8" />
            </div>
            <h1 className="text-2xl font-bold tracking-tight text-white">
              LogiStore <span className="text-indigo-400">ERP</span>
            </h1>
            <p className="mt-1 text-xs text-slate-400">
              Gestión Integral de Almacenes, Cotizaciones y Helpdesk
            </p>
          </div>

          {/* Error Banner */}
          {error && (
            <div className="mb-6 flex items-start gap-3 rounded-2xl bg-rose-500/10 border border-rose-500/30 p-3.5 text-xs text-rose-300">
              <AlertCircle className="h-5 w-5 shrink-0 text-rose-400" />
              <span>{error}</span>
            </div>
          )}

          {/* Login Form */}
          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="mb-2 block text-xs font-semibold text-slate-300">
                Correo Electrónico
              </label>
              <div className="relative">
                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                  <Mail className="h-4 w-4" />
                </div>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="ejemplo@empresa.com"
                  className="w-full rounded-xl border border-slate-700 bg-slate-900/60 py-3 pl-10 pr-4 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                />
              </div>
            </div>

            <div>
              <label className="mb-2 block text-xs font-semibold text-slate-300">
                Contraseña
              </label>
              <div className="relative">
                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                  <Lock className="h-4 w-4" />
                </div>
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full rounded-xl border border-slate-700 bg-slate-900/60 py-3 pl-10 pr-4 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-indigo-600 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 transition-all hover:from-indigo-600 hover:to-indigo-700 focus:outline-hidden disabled:opacity-50"
            >
              {loading ? (
                <div className="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent" />
              ) : (
                <>
                  <span>Ingresar al Sistema</span>
                  <ArrowRight className="h-4 w-4" />
                </>
              )}
            </button>
          </form>

          {/* Quick preset selector */}
          <div className="mt-8 border-t border-slate-700/60 pt-6">
            <p className="mb-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 text-center">
              Cuentas demo de la base de datos
            </p>
            <div className="space-y-2">
              <button
                type="button"
                onClick={() => handleQuickFill('admin@ruvindustrias.com', 'admin123')}
                className="flex w-full items-center justify-between rounded-xl bg-slate-900/50 p-2.5 text-left text-xs text-slate-300 transition-colors hover:bg-slate-900 hover:text-white border border-slate-700/40"
              >
                <div>
                  <p className="font-semibold text-indigo-400">RUVINDUSTRIAS S.A.C.</p>
                  <p className="text-[11px] text-slate-400">admin@ruvindustrias.com</p>
                </div>
                <span className="rounded-lg bg-indigo-500/20 px-2 py-0.5 text-[10px] font-medium text-indigo-300">
                  Autocompletar
                </span>
              </button>

              <button
                type="button"
                onClick={() => handleQuickFill('admin@almacen.com', 'admin123')}
                className="flex w-full items-center justify-between rounded-xl bg-slate-900/50 p-2.5 text-left text-xs text-slate-300 transition-colors hover:bg-slate-900 hover:text-white border border-slate-700/40"
              >
                <div>
                  <p className="font-semibold text-slate-200">Mi Empresa S.A.C.</p>
                  <p className="text-[11px] text-slate-400">admin@almacen.com</p>
                </div>
                <span className="rounded-lg bg-slate-700/60 px-2 py-0.5 text-[10px] font-medium text-slate-300">
                  Autocompletar
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
