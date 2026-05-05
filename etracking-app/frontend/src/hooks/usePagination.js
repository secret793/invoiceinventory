import { useState, useCallback } from 'react';

export function usePagination(fetchFn, initialParams = {}) {
  const [data, setData]     = useState([]);
  const [meta, setMeta]     = useState({ total: 0, current_page: 1, last_page: 1, per_page: 25 });
  const [loading, setLoading] = useState(false);
  const [error, setError]   = useState(null);
  const [params, setParams] = useState({ page: 1, per_page: 25, ...initialParams });

  const load = useCallback(async (overrides = {}) => {
    const p = { ...params, ...overrides };
    setLoading(true); setError(null);
    try {
      const res = await fetchFn(p);
      setData(res.data || []);
      setMeta(res.meta  || {});
    } catch (e) { setError(e.message); }
    finally     { setLoading(false); }
  }, [params, fetchFn]);

  const changePage    = useCallback((page) => { setParams(p => ({...p, page})); load({ ...params, page }); }, [params, load]);
  const changeFilters = useCallback((f)    => { const p = { ...params, ...f, page: 1 }; setParams(p); load(p); }, [params, load]);
  const refresh       = useCallback(()     => load(params), [params, load]);

  return { data, meta, loading, error, load, refresh, changePage, changeFilters };
}
