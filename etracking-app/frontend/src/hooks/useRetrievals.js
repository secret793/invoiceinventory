import { useState, useCallback, useEffect } from 'react';
import { retrievalService } from '../services/retrievalService';

export function useRetrievals(initialParams = {}) {
  const [retrievals, setRetrievals] = useState([]);
  const [meta, setMeta]             = useState({ total: 0, current_page: 1, last_page: 1, per_page: 25 });
  const [loading, setLoading]       = useState(false);
  const [error, setError]           = useState(null);
  const [params, setParams]         = useState({ page: 1, per_page: 25, ...initialParams });

  const fetch = useCallback(async (overrides = {}) => {
    const p = { ...params, ...overrides };
    setLoading(true); setError(null);
    try {
      const res = await retrievalService.list(p);
      setRetrievals(res.data || []);
      setMeta(res.meta       || {});
    } catch (e) { setError(e.message); }
    finally     { setLoading(false); }
  }, [params]);

  useEffect(() => { fetch(); }, []);

  const changePage    = (page) => { setParams(p => ({ ...p, page })); fetch({ page }); };
  const changeFilters = (f)    => { const p = { ...params, ...f, page: 1 }; setParams(p); fetch(p); };

  return { retrievals, meta, loading, error, fetch, changePage, changeFilters };
}
