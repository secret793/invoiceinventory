import { useState, useCallback, useEffect } from 'react';
import { allocationService } from '../services/allocationService';

export function useAllocationPoints() {
  const [allocationPoints, setAllocationPoints] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError]     = useState(null);

  const fetch = useCallback(async () => {
    setLoading(true); setError(null);
    try {
      const data = await allocationService.list();
      setAllocationPoints(data || []);
    } catch (e) { setError(e.message); }
    finally     { setLoading(false); }
  }, []);

  useEffect(() => { fetch(); }, []);

  return { allocationPoints, loading, error, fetch };
}
