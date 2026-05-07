import { useState, useCallback, useEffect, useRef } from 'react';
import { retrievalService } from '../services/retrievalService';

export function useRetrievals(initialParams = {}) {
  const [retrievals, setRetrievals] = useState([]);
  const [meta, setMeta]             = useState({ total: 0, current_page: 1, last_page: 1, per_page: 25 });
  const [loading, setLoading]       = useState(false);
  const [error, setError]           = useState(null);
  const [params, setParams]         = useState({ page: 1, per_page: 25, ...initialParams });

  const paramsRef    = useRef(params);
  const intervalRef  = useRef(null);
  paramsRef.current  = params;

  const fetch = useCallback(async (overrides = {}) => {
    const p = { ...paramsRef.current, ...overrides };
    setLoading(true); setError(null);
    try {
      const res = await retrievalService.list(p);
      setRetrievals(res.data || []);
      setMeta(res.meta       || {});
    } catch (e) { setError(e.message); }
    finally     { setLoading(false); }
  }, []);

  const silentFetch = useCallback(async () => {
    try {
      const res = await retrievalService.list(paramsRef.current);
      setRetrievals(res.data || []);
      setMeta(res.meta       || {});
    } catch (_) {}
  }, []);

  useEffect(() => {
    fetch();
    intervalRef.current = setInterval(silentFetch, 10000);
    return () => clearInterval(intervalRef.current);
  }, []);

  const changePage    = (page) => { const p = { ...params, page }; setParams(p); fetch(p); };
  const changeFilters = (f)    => { const p = { ...params, ...f, page: 1 }; setParams(p); fetch(p); };

  return { retrievals, meta, loading, error, fetch, changePage, changeFilters };
}
