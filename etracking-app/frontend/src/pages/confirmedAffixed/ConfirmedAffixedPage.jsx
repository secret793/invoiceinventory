import React, { useState, useEffect, useCallback } from 'react';
import api from '../../services/api';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import SearchBar from '../../components/common/SearchBar';

export default function ConfirmedAffixedPage() {
  const { notify } = useNotification();
  const [records, setRecords]   = useState([]);
  const [meta, setMeta]         = useState({});
  const [loading, setLoading]   = useState(false);
  const [params, setParams]     = useState({ page: 1, per_page: 25 });
  const [picking, setPicking]   = useState(null);
  const [pickLoading, setPickLoading] = useState(false);
  const [returning, setReturning] = useState(null);
  const [returnNote, setReturnNote] = useState('');
  const [returnLoading, setReturnLoading] = useState(false);

  const fetch = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const { data } = await api.get('/confirmed-affixed', { params: p });
      setRecords(data.data || []); setMeta(data.meta || {});
    } catch { } finally { setLoading(false); }
  }, [params]);

  useEffect(() => { fetch(); }, []);

  const handlePick = async () => {
    setPickLoading(true);
    try {
      await api.post(`/confirmed-affixed/${picking.id}/pick`, { affixing_date: new Date().toISOString().slice(0, 19) });
      notify.success('Device picked for affixing. Retrieval and monitoring records created.');
      setPicking(null); fetch();
    } catch (e) { notify.error(e.message); }
    finally     { setPickLoading(false); }
  };

  const handleReturn = async () => {
    if (!returnNote.trim()) { notify.error('Return note is required'); return; }
    setReturnLoading(true);
    try {
      await api.post(`/confirmed-affixed/${returning.id}/return`, { return_note: returnNote });
      notify.success('Data returned'); setReturning(null); setReturnNote(''); fetch();
    } catch (e) { notify.error(e.message); }
    finally     { setReturnLoading(false); }
  };

  const columns = [
    { header: 'BOE',           key: 'boe',            render: v => <span className="font-mono font-medium">{v}</span> },
    { header: 'Vehicle',       key: 'vehicle_number' },
    { header: 'Station',       key: 'allocation_point_name', render: v => v || '—' },
    { header: 'Regime',        key: 'regime' },
    { header: 'Destination',   key: 'destination' },
    { header: 'Date',          key: 'date', render: v => v ? new Date(v).toLocaleDateString() : '—' },
    { header: 'Status',        key: 'status', render: v => <StatusBadge status={v} /> },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <div className="flex gap-1">
          <button onClick={() => setPicking(row)} className="btn-success btn-sm">Pick for Affixing</button>
          <button onClick={() => setReturning(row)} className="btn-warning btn-sm">Return</button>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader title="Confirmed Dispatch" subtitle="Manage confirmed affixed / dispatch records" />

      <div className="flex gap-3 mb-4">
        <SearchBar onSearch={s => { const p = { ...params, search: s, page: 1 }; setParams(p); fetch(p); }} className="w-64" />
      </div>

      <div className="card">
        <DataTable columns={columns} data={records} loading={loading} emptyMessage="No confirmed dispatch records found." />
        <Pagination meta={meta} onPageChange={p => { const np = { ...params, page: p }; setParams(np); fetch(np); }} />
      </div>

      <ConfirmDialog isOpen={!!picking} onClose={() => setPicking(null)} onConfirm={handlePick}
        loading={pickLoading} title="Pick for Affixing"
        message={`Pick "${picking?.boe}" for affixing? This will create retrieval and monitoring records.`}
        confirmLabel="Pick & Affix" />

      <Modal isOpen={!!returning} onClose={() => setReturning(null)} title="Return Data"
        footer={
          <>
            <button onClick={() => setReturning(null)} className="btn-secondary">Cancel</button>
            <button onClick={handleReturn} disabled={returnLoading} className="btn-warning">
              {returnLoading ? 'Returning…' : 'Return'}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">Return reason for <strong>{returning?.boe}</strong>:</p>
          <textarea className="input" rows={3} value={returnNote} onChange={e => setReturnNote(e.target.value)} placeholder="Return reason…" />
        </div>
      </Modal>
    </div>
  );
}
