import React, { createContext, useContext, useState, useCallback, useEffect } from 'react';
import api from '../services/api';
import { useAuth } from './AuthContext';

const SidebarContext = createContext(null);

export function SidebarProvider({ children }) {
  const { user } = useAuth();
  const [collapsed, setCollapsed]               = useState(false);
  const [allocationPoints, setAllocationPoints] = useState([]);
  const [distributionPoints, setDistributionPoints] = useState([]);
  const [sidebarBadges, setSidebarBadges]       = useState({});
  const [loading, setLoading]                   = useState(false);

  const fetchSidebarData = useCallback(async () => {
    if (!user) return;
    try {
      setLoading(true);
      const { data } = await api.get('/sidebar');
      setAllocationPoints(data.data?.allocation_points   || []);
      setDistributionPoints(data.data?.distribution_points || []);
      setSidebarBadges(prev => ({
        ...prev,
        notifications: data.data?.unread_notifications || 0,
      }));
    } catch { /* ignore */ } finally {
      setLoading(false);
    }
  }, [user]);

  useEffect(() => {
    if (user) fetchSidebarData();
  }, [user, fetchSidebarData]);

  const toggleCollapse = () => setCollapsed(c => !c);

  return (
    <SidebarContext.Provider value={{
      collapsed, toggleCollapse,
      allocationPoints, distributionPoints, sidebarBadges,
      loading, fetchSidebarData,
    }}>
      {children}
    </SidebarContext.Provider>
  );
}

export function useSidebar() {
  const ctx = useContext(SidebarContext);
  if (!ctx) throw new Error('useSidebar must be used within SidebarProvider');
  return ctx;
}
