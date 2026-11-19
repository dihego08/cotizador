import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  const empresaId = localStorage.getItem('active_empresa_id');
  if (empresaId) {
    config.headers['X-Empresa-Id'] = empresaId;
  }
  return config;
});

const cerrarSesionLocal = (aviso) => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  if (aviso) {
    sessionStorage.setItem('aviso_login', aviso);
  }
  if (window.location.pathname !== '/login') {
    window.location.href = '/login';
  }
};

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    const code = error.response?.data?.code;

    if (status === 401) {
      cerrarSesionLocal();
    } else if (status === 403 && ['cuenta_suspendida', 'usuario_inactivo', 'sin_cuenta'].includes(code)) {
      // Cuenta suspendida o usuario desactivado: se cierra la sesión mostrando el motivo
      cerrarSesionLocal(error.response.data.message);
    } else if (status === 403 && code === 'debe_cambiar_password' && window.location.pathname !== '/cambiar-password') {
      window.location.href = '/cambiar-password';
    } else if (status === 403 && code === 'solo_plataforma' && window.location.pathname !== '/plataforma') {
      window.location.href = '/plataforma';
    }
    return Promise.reject(error);
  }
);

export default api;
