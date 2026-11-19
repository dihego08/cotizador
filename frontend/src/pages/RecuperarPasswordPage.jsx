import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import api from '../api/client';
import AuthShell from '../components/auth/AuthShell';
import { authButtonClass, authInputClass, authLabelClass, primerError } from '../components/auth/authEstilos';

export default function RecuperarPasswordPage() {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await api.post('/auth/recuperar-password', { email });
      setSuccess(res.data?.message);
    } catch (err) {
      setError(primerError(err, 'No se pudo enviar el correo. Inténtalo nuevamente.'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthShell
      title="Recuperar contraseña"
      subtitle="Te enviaremos un enlace para crear una contraseña nueva."
      error={error}
      success={success}
    >
      <form onSubmit={handleSubmit} className="space-y-5">
        <div>
          <label className={authLabelClass}>Correo Electrónico</label>
          <input
            type="email"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="ejemplo@empresa.com"
            className={authInputClass}
          />
        </div>

        <button type="submit" disabled={loading} className={authButtonClass}>
          {loading ? 'Enviando...' : 'Enviar enlace'}
        </button>

        <Link to="/login" className="flex items-center justify-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white">
          <ArrowLeft className="h-3.5 w-3.5" />
          <span>Volver al inicio de sesión</span>
        </Link>
      </form>
    </AuthShell>
  );
}
