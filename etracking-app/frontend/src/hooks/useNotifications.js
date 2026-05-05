import { useState, useCallback, useEffect } from 'react';
import { notificationService } from '../services/notificationService';

export function useNotifications() {
  const [notifications, setNotifications] = useState([]);
  const [meta, setMeta]                   = useState({});
  const [loading, setLoading]             = useState(false);
  const [selected, setSelected]           = useState([]);

  const fetch = useCallback(async (params = {}) => {
    setLoading(true);
    try {
      const res = await notificationService.list(params);
      setNotifications(res.data || []);
      setMeta(res.meta           || {});
    } catch { /* ignore */ } finally { setLoading(false); }
  }, []);

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

  const toggleSelect = (id) =>
    setSelected(prev => prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]);

  return { notifications, meta, loading, selected, setSelected, fetch, markRead, markUnread, bulkRead, toggleSelect };
}
