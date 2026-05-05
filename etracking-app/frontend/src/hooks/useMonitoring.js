import { useState, useCallback, useEffect } from 'react';
import api from '../services/api';

export function useMonitoring(initialParams = {}) {
  const [records, setRecords] = useState([]);
  const [meta, setMeta]       = useState({});
  const [loading, setLoading] = useState(false);

  const fetch = useCallback(async (params = {}) => {
    setLoading(true);
    try {
      const { data } = await api.get('/monitoring', { params: { ...initialParams, ...params } });
      setRecords(data.data || []);
      setMeta(data.meta    || {});
    } catch { /* ignore */ } finally { setLoading(false); }
  }, []);

  useEffect(() => { fetch(); }, []);

  return { records, meta, loading, fetch };
}
