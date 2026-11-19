import React, { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import api from '../api/client';
import AuthShell from '../components/auth/AuthShell';
import { authButtonClass, authInputClass, authLabelClass, primerError } from '../components/auth/authEstilos';

// Pantalla a la que lleva el enlace del correo de recuperación (?token=...&email=...)
export default function RestablecerPasswordPage() {
  const [params] = useSearchParams();
  const token = params.get('token') || '';
  const email = params.get('email') || '';

  const [password, setPassword] = useState('');
  const [confirmacion, setConfirmacion] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(token && email ? '' : 'El enlace está incompleto. Solicita uno nuevo.');
  const [success, setSuccess] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (password !== confirmacion) {
      setError('Las contraseñas no coinciden.');
      return;
    }

    setLoading(true);
    try {
      const res = await api.post('/auth/restablecer-password', {
        token,
        email,
        password,
        password_confirmation: confirmacion,
      });
      setSuccess(res.data?.message);
    } catch (err) {
      setError(primerError(err, 'No se pudo restablecer la contraseña.'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthShell title="Nueva contraseña" subtitle={email} error={error} success={success}>
      {success ? (
        <Link to="/login" className={authButtonClass}>
          Ir al inicio de sesión
        </Link>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <label className={authLabelClass}>Contraseña nueva</label>
            <input
              type="password"
              required
              minLength={8}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Mínimo 8 caracteres"
              className={authInputClass}
            />
          </div>

          <div>
            <label className={authLabelClass}>Confirmar contraseña</label>
            <input
              type="password"
              required
              minLength={8}
              value={confirmacion}
              onChange={(e) => setConfirmacion(e.target.value)}
              className={authInputClass}
            />
          </div>

          <button type="submit" disabled={loading || !token || !email} className={authButtonClass}>
            {loading ? 'Guardando...' : 'Guardar contraseña'}
          </button>

          <Link to="/recuperar-password" className="flex items-center justify-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white">
            <ArrowLeft className="h-3.5 w-3.5" />
            <span>Solicitar un enlace nuevo</span>
          </Link>
        </form>
      )}
    </AuthShell>
  );
}
