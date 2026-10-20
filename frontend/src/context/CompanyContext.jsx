import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../api/client';
import { useAuth } from './AuthContext';

const CompanyContext = createContext();

export const CompanyProvider = ({ children }) => {
  const { user } = useAuth();
  const [empresas, setEmpresas] = useState([]);
  const [activeEmpresa, setActiveEmpresa] = useState(null);
  const [loadingEmpresas, setLoadingEmpresas] = useState(true);

  useEffect(() => {
    const fetchEmpresas = async () => {
      if (!user) return;
      try {
        const res = await api.get('/empresas');
        if (res.data?.data) {
          const list = res.data.data;
          setEmpresas(list);

          const savedId = localStorage.getItem('active_empresa_id');
          const found = list.find((e) => e.id_empresa == savedId);

          if (found) {
            setActiveEmpresa(found);
          } else if (user.empresa) {
            setActiveEmpresa(user.empresa);
            localStorage.setItem('active_empresa_id', user.empresa.id_empresa);
          } else if (list.length > 0) {
            setActiveEmpresa(list[0]);
            localStorage.setItem('active_empresa_id', list[0].id_empresa);
          }
        }
      } catch (err) {
        console.error('Error fetching companies:', err);
      } finally {
        setLoadingEmpresas(false);
      }
    };

    fetchEmpresas();
  }, [user]);

  const changeEmpresa = (empresaOrId) => {
    const id = typeof empresaOrId === 'object' ? empresaOrId.id_empresa : empresaOrId;
    const found = empresas.find((e) => e.id_empresa == id);
    if (found) {
      setActiveEmpresa(found);
      localStorage.setItem('active_empresa_id', found.id_empresa);
    }
  };

  return (
    <CompanyContext.Provider
      value={{
        empresas,
        activeEmpresa,
        changeEmpresa,
        loadingEmpresas,
      }}
    >
      {children}
    </CompanyContext.Provider>
  );
};

export const useCompany = () => useContext(CompanyContext);
