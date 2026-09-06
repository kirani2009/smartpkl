import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import api from '../api/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    const stored = localStorage.getItem('user');
    return stored ? JSON.parse(stored) : null;
  });
  const [loading, setLoading] = useState(false);

  const isAuthenticated = !!user;

  const login = useCallback(async (email, password) => {
    setLoading(true);
    try {
      const { data } = await api.post('/auth/login', { email, password });
      if (data.success) {
        const token = data.data?.token;
        const userData = data.data?.user;
        if (!token || !userData) {
          return { success: false, message: 'Respons server tidak lengkap. Silakan coba lagi.' };
        }
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(userData));
        setUser(userData);
        return { success: true, user: userData };
      }
      return { success: false, message: data.message || 'Gagal login.' };
    } catch (err) {
      if (err.code === 'ERR_NETWORK') {
        return { success: false, message: 'Tidak dapat terhubung ke server. Pastikan server backend sedang berjalan.' };
      }
      const data = err.response?.data;
      // Server mengembalikan body non-JSON (mis. halaman fatal-error HTML).
      // Beri pesan yang jelas alih-alih 'Gagal login.' yang generik.
      if (typeof data === 'string' || data == null) {
        return { success: false, message: 'Server sedang tidak tersedia. Silakan coba lagi.' };
      }
      return { success: false, message: data.message || 'Gagal login.' };
    } finally {
      setLoading(false);
    }
  }, []);

  const register = useCallback(async (payload) => {
    setLoading(true);
    try {
      const { data } = await api.post('/auth/register', payload);
      if (data.success) {
        const token = data.data?.token;
        const userData = data.data?.user;
        if (!userData) {
          return { success: false, message: 'Respons server tidak lengkap. Silakan coba lagi.' };
        }
        if (token) {
          localStorage.setItem('token', token);
          localStorage.setItem('user', JSON.stringify(userData));
          setUser(userData);
          return { success: true, user: userData, needLogin: false };
        }
        return { success: true, user: userData, needLogin: true };
      }
      return { success: false, message: data.message || 'Gagal mendaftar.', errors: data.errors };
    } catch (err) {
      if (err.code === 'ERR_NETWORK') {
        return { success: false, message: 'Tidak dapat terhubung ke server. Pastikan server backend sedang berjalan.' };
      }
      const data = err.response?.data;
      // Server mengembalikan body non-JSON (mis. halaman fatal-error HTML).
      // Beri pesan yang jelas alih-alih 'Gagal mendaftar.' yang generik.
      if (typeof data === 'string' || data == null) {
        return { success: false, message: 'Server sedang tidak tersedia. Silakan coba lagi.', errors: null };
      }
      const msg = data.message || 'Gagal mendaftar.';
      const errors = data.errors || null;
      return { success: false, message: msg, errors };
    } finally {
      setLoading(false);
    }
  }, []);

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // ignore
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      setUser(null);
    }
  }, []);

  // Refresh user data from /api/me
  const refreshUser = useCallback(async () => {
    try {
      const { data } = await api.get('/me');
      if (data.success) {
        localStorage.setItem('user', JSON.stringify(data.data));
        setUser(data.data);
      }
    } catch {
      // token expired or invalid
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      setUser(null);
    }
  }, []);

  useEffect(() => {
    if (localStorage.getItem('token')) {
      refreshUser();
    }
  }, [refreshUser]);

  return (
    <AuthContext.Provider value={{ user, loading, isAuthenticated, login, register, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
