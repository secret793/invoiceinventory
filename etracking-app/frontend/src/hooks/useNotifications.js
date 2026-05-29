import { useState, useCallback, useEffect } from 'react';
import { notificationService } from '../services/notificationService';

export function useNotifications() {
  const [notifications, setNotifications] = useState([]);
  const [meta, setMeta]                   = useState({});
  const [loading, setLoading]             = useState(false);
  const [selected, setSelected]           = useState([]);
  const [params, setParams]               = useState({ page: 1, per_page: 25 });

  const fetch = useCallback(async (overrides = {}) => {
    const query = { ...params, ...overrides };
    setLoading(true);
    try {
      const res = await notificationService.list(query);
      setNotifications(res.data || []);
      setMeta(res.meta           || {});
    } catch { /* ignore */ } finally { setLoading(false); }
  }, [params]);

  useEffect(() => { fetch(); }, []);

  const markRead   = useCallback(async (id) => {
    await notificationService.markRead(id);
    setNotifications(prev => prev.map(n => n.id === id ? { ...n, read_at: new Date().toISOString() } : n));
  }, []);

  const markUnread = useCallback(async (id) => {
    await notificationService.markUnread(id);
    setNotifications(prev => prev.map(n => n.id === id ? { ...n, read_at: null } : n));
  }, []);

  const bulkRead   = useCallback(async (ids) => {
    await notificationService.bulkRead(ids);
    fetch();
  }, [fetch]);

  const changePage = (page) => {
    const next = { ...params, page };
    setParams(next);
    fetch(next);
  };

  const changeFilters = (f) => {
    const next = { ...params, ...f, page: 1 };
    setParams(next);
    fetch(next);
  };

  const toggleSelect = (id) =>
    setSelected(prev => prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]);

  return { notifications, meta, loading, selected, setSelected, fetch, markRead, markUnread, bulkRead, toggleSelect, changePage, changeFilters };
}
