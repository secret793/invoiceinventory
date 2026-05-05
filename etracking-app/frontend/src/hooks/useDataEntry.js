import { useState, useCallback, useEffect } from 'react';
import { dataEntryService } from '../services/dataEntryService';

export function useDataEntry(params = {}) {
  const [assignments, setAssignments] = useState([]);
  const [meta, setMeta]               = useState({});
  const [loading, setLoading]         = useState(false);
  const [error, setError]             = useState(null);

  const fetch = useCallback(async (overrides = {}) => {
    setLoading(true); setError(null);
    try {
      const res = await dataEntryService.list({ ...params, ...overrides });
      setAssignments(res.data || []);
      setMeta(res.meta        || {});
    } catch (e) { setError(e.message); }
    finally     { setLoading(false); }
  }, []);

  useEffect(() => { fetch(); }, []);

  return { assignments, meta, loading, error, fetch };
}
