import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/client';
import { useAuth } from '../context/AuthContext';
import AuthShell from '../components/auth/AuthShell';
import { authButtonClass, authInputClass, authLabelClass, primerError } from '../components/auth/authEstilos';

// Obligatoria cuando el usuario tiene una contraseña temporal; también se usa desde el menú de perfil
export default function CambiarPasswordPage() {
  const { user, updateUser, logout } = useAuth();
  const navigate = useNavigate();
  const obligatorio = !!user?.debe_cambiar_password;

  const [form, setForm] = useState({ password_actual: '', password: '', password_confirmation: '' });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (form.password !== form.password_confirmation) {
      setError('Las contraseñas nuevas no coinciden.');
      return;
    }

    setLoading(true);
    try {
      const res = await api.post('/auth/cambiar-password', form);
      updateUser(res.data.user);
      navigate(res.data.user?.cuenta?.es_plataforma ? '/plataforma' : '/');
    } catch (err) {
      setError(primerError(err, 'No se pudo cambiar la contraseña.'));
    } finally {
      setLoading(false);
    }
  };

  const campo = (name, label, extra = {}) => (
    <div>
      <label className={authLabelClass}>{label}</label>
      <input
        type="password"
        required
        value={form[name]}
        onChange={(e) => setForm({ ...form, [name]: e.target.value })}
        className={authInputClass}
        {...extra}
      />
    </div>
  );

  return (
    <AuthShell
      title="Cambiar contraseña"
      subtitle={
        obligatorio
          ? 'Tu contraseña es temporal. Crea una nueva para continuar.'
          : user?.email
      }
      error={error}
    >
      <form onSubmit={handleSubmit} className="space-y-5">
        {campo('password_actual', obligatorio ? 'Contraseña temporal' : 'Contraseña actual')}
        {campo('password', 'Contraseña nueva', { minLength: 8, placeholder: 'Mínimo 8 caracteres' })}
        {campo('password_confirmation', 'Confirmar contraseña nueva', { minLength: 8 })}

        <button type="submit" disabled={loading} className={authButtonClass}>
          {loading ? 'Guardando...' : 'Guardar contraseña'}
        </button>

        <button
          type="button"
          onClick={() => (obligatorio ? logout() : navigate(-1))}
          className="w-full text-center text-xs font-semibold text-slate-400 hover:text-white"
        >
          {obligatorio ? 'Cerrar sesión' : 'Cancelar'}
        </button>
      </form>
    </AuthShell>
  );
}
