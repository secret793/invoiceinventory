/* @refresh reset */
import React, { createContext, useContext, useState, useCallback, useEffect } from 'react';
import { authService } from '../services/authService';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser]       = useState(null);
  const [token, setToken]     = useState(() => localStorage.getItem('token'));
  const [loading, setLoading] = useState(true);

  const loadUser = useCallback(async () => {
    const t = localStorage.getItem('token');
    if (!t) { setLoading(false); return; }
    try {
      const data = await authService.me();
      setUser(data);
    } catch {
      localStorage.removeItem('token');
      setToken(null);
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { loadUser(); }, [loadUser]);

  const login = useCallback(async (username, password) => {
    const { token: t, user: u } = await authService.login(username, password);
    localStorage.setItem('token', t);
    setToken(t);
    setUser(u);
    return u;
  }, []);

  const logout = useCallback(async () => {
    try { await authService.logout(); } catch { /* ignore */ }
    localStorage.removeItem('token');
    setToken(null);
    setUser(null);
  }, []);

  const hasRole = useCallback((role) => {
    if (!user) return false;
    const roles = Array.isArray(role) ? role : [role];
    return (user.roles || []).some(r => roles.includes(r));
  }, [user]);

  const hasPermission = useCallback((perm) => {
    if (!user) return false;
    if (hasRole('Super Admin')) return true;
    return (user.permissions || []).includes(perm);
  }, [user, hasRole]);

  const isSuperAdmin = useCallback(() => hasRole('Super Admin'), [hasRole]);

  const canManageInventory = useCallback(() =>
    hasRole(['Super Admin', 'Warehouse Manager']), [hasRole]);

  const canViewReports = useCallback(() =>
    hasRole(['Super Admin', 'Warehouse Manager', 'Finance Officer', 'Report Viewer']), [hasRole]);

  return (
    <AuthContext.Provider value={{
      user, token, loading,
      login, logout,
      hasRole, hasPermission, isSuperAdmin,
      canManageInventory, canViewReports,
    }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
