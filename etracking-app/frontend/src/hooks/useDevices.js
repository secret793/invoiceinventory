import { useState, useCallback, useEffect } from 'react';
import { deviceService } from '../services/deviceService';

export function useDevices(initialParams = {}) {
  const [devices, setDevices]   = useState([]);
  const [meta, setMeta]         = useState({ total: 0, current_page: 1, last_page: 1, per_page: 25 });
  const [stats, setStats]       = useState({});
  const [loading, setLoading]   = useState(false);
  const [error, setError]       = useState(null);
  const [params, setParams]     = useState({ page: 1, per_page: 25, ...initialParams });

  const fetch = useCallback(async (overrides = {}) => {
    const p = { ...params, ...overrides };
    setLoading(true); setError(null);
    try {
      const res = await deviceService.list(p);
      setDevices(res.data || []);
      setMeta(res.meta  || {});
    } catch (e) { setError(e.message); }
    finally     { setLoading(false); }
  }, [params]);

  const fetchStats = useCallback(async () => {
    try { setStats(await deviceService.stats()); } catch { /* ignore */ }
  }, []);

  useEffect(() => { fetch(); fetchStats(); }, []);

  const changePage = (page) => { setParams(p => ({ ...p, page })); fetch({ page }); };
  const changeFilters = (f)  => { const p = { ...params, ...f, page: 1 }; setParams(p); fetch(p); };

  return { devices, meta, stats, loading, error, fetch, fetchStats, changePage, changeFilters };
}
