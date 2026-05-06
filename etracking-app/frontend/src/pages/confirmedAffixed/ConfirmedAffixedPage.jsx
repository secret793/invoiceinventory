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

  const [picking, setPicking]         = useState(null);
  const [pickDate, setPickDate]       = useState('');
  const [pickLoading, setPickLoading] = useState(false);

  const [returning, setReturning]         = useState(null);
  const [returnNote, setReturnNote]       = useState('');
  const [returnLoading, setReturnLoading] = useState(false);

  const [selected, setSelected]       = useState([]);
  const [bulkPickModal, setBulkPickModal] = useState(false);
  const [bulkPickDate, setBulkPickDate]   = useState('');
  const [bulkLoading, setBulkLoading]     = useState(false);

  const load = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const { data } = await api.get('/confirmed-affixed', { params: p });
      setRecords(data.data || []); setMeta(data.meta || {});
    } catch { } finally { setLoading(false); }
  }, [params]);

  useEffect(() => {
    load();
    const defaultDate = new Date().toISOString().slice(0, 16);
    setPickDate(defaultDate); setBulkPickDate(defaultDate);
  }, []);

  const handlePick = async () => {
    setPickLoading(true);
    try {
      await api.post(`/confirmed-affixed/${picking.id}/pick`, { affixing_date: pickDate });
      notify.success('Device picked for affixing. Retrieval and monitoring records created.');
      setPicking(null); load();
    } catch (e) { notify.error(e.message); }
    finally { setPickLoading(false); }
  };

  const handleReturn = async () => {
    if (!returnNote.trim()) { notify.error('Return note is required'); return; }
    setReturnLoading(true);
    try {
      await api.post(`/confirmed-affixed/${returning.id}/return`, { return_note: returnNote });
      notify.success('Data returned'); setReturning(null); setReturnNote(''); load();
    } catch (e) { notify.error(e.message); }
    finally { setReturnLoading(false); }
  };

  const handleBulkPick = async () => {
    if (!bulkPickDate) { notify.error('Affixing date is required'); return; }
    setBulkLoading(true);
    let succeeded = 0, failed = 0;
    for (const id of selected) {
      try {
        await api.post(`/confirmed-affixed/${id}/pick`, { affixing_date: bulkPickDate });
        succeeded++;
      } catch { failed++; }
    }
    notify.success(`Picked ${succeeded} device(s) for affixing${failed ? ` (${failed} failed)` : ''}`);
    setBulkPickModal(false); setSelected([]); load();
    setBulkLoading(false);
  };

  const columns = [
    { header: 'Device ID',     key: 'device_id',         render: v => <span className="font-mono font-semibold">{v || '—'}</span> },
    { header: 'BOE / SAD',     key: 'boe',               render: v => <span className="font-mono">{v || '—'}</span> },
    { header: 'Vehicle',       key: 'vehicle_number',    render: v => v || '—' },
    { header: 'Regime',        key: 'regime',            render: v => v || '—' },
    { header: 'Route',         key: 'route',             render: v => v || '—' },
    { header: 'Long Route',    key: 'long_route',        render: v => v || '—' },
    { header: 'Destination',   key: 'destination',       render: v => v || '—' },
    { header: 'Manifest Date', key: 'manifest_date',
      render: v => v ? new Date(v).toLocaleDateString() : <span className="badge-yellow">Pending</span>
    },
    { header: 'Agency',        key: 'agency',            render: v => v || '—' },
    { header: 'Agent Contact', key: 'agent_contact',     render: v => v || '—' },
    { header: 'Truck No.',     key: 'truck_number',      render: v => v || '—' },
    { header: 'Driver',        key: 'driver_name',       render: v => v || '—' },
    { header: 'Date',          key: 'date',              render: v => v ? new Date(v).toLocaleDateString() : '—' },
    { header: 'Status',        key: 'status',            render: v => <StatusBadge status={v} /> },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <div className="flex gap-1">
          {row.status !== 'AFFIXED' && (
            <button onClick={() => { setPicking(row); setPickDate(new Date().toISOString().slice(0, 16)); }}
              className="btn-success btn-sm">Pick for Affixing</button>
          )}
          <button onClick={() => { setReturning(row); setReturnNote(''); }}
            className="btn-warning btn-sm">Return Data</button>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader title="Confirmed Dispatch" subtitle="Devices dispatched and pending affixing to vehicles"
        actions={
          <div className="flex gap-2">
            {selected.length > 0 && (
              <button onClick={() => setBulkPickModal(true)} className="btn-success">
                Pick Selected ({selected.length})
              </button>
            )}
            <a href="/api/confirmed-affixed/export" target="_blank" className="btn-secondary">
              View Report / Export
            </a>
          </div>
        } />

      <div className="flex gap-3 mb-4">
        <SearchBar onSearch={s => { const p = { ...params, search: s, page: 1 }; setParams(p); load(p); }} className="w-64" />
      </div>

      {selected.length > 0 && (
        <div className="flex items-center gap-3 mb-4 rounded-xl px-4 py-3 border"
          style={{ background: '#eef1fb', borderColor: '#c7cef0' }}>
          <span className="text-sm font-semibold" style={{ color: '#1E2D7A' }}>{selected.length} selected</span>
          <button onClick={() => setBulkPickModal(true)} className="btn-success btn-sm">
            Pick Selected for Affixing
          </button>
          <button onClick={() => setSelected([])} className="btn-secondary btn-sm ml-auto">Clear</button>
        </div>
      )}

      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={records} loading={loading}
          selectable selected={selected}
          onSelect={(id, checked) => setSelected(prev => checked ? [...prev, id] : prev.filter(x => x !== id))}
          onSelectAll={checked => setSelected(checked ? records.map(r => r.id) : [])}
          emptyMessage="No confirmed dispatch records found." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination meta={meta} onPageChange={p => { const np = { ...params, page: p }; setParams(np); load(np); }} />
        </div>
      </div>

      {/* Pick for Affixing Modal */}
      <Modal isOpen={!!picking} onClose={() => setPicking(null)} title="Pick for Affixing"
        footer={
          <>
            <button onClick={() => setPicking(null)} className="btn-secondary">Cancel</button>
            <button onClick={handlePick} disabled={pickLoading} className="btn-success">
              {pickLoading ? 'Picking…' : 'Confirm Pick & Affix'}
            </button>
          </>
        }>
        {picking && (
          <div className="space-y-4">
            <div className="bg-gray-50 rounded-lg p-3 text-sm space-y-1">
              <p className="text-gray-500">BOE: <strong className="font-mono">{picking.boe || '—'}</strong></p>
              <p className="text-gray-500">Vehicle: <strong>{picking.vehicle_number || '—'}</strong></p>
            </div>
            <div>
              <label className="label">Affixing Date & Time <span className="text-red-500">*</span></label>
              <input type="datetime-local" className="input" value={pickDate}
                onChange={e => setPickDate(e.target.value)} />
            </div>
            <p className="text-xs text-gray-500">
              This will create a DeviceRetrieval record and a ConfirmedAffixLog, then remove the dispatch record.
            </p>
          </div>
        )}
      </Modal>

      {/* Bulk Pick Modal */}
      <Modal isOpen={bulkPickModal} onClose={() => setBulkPickModal(false)} title={`Pick ${selected.length} Device(s) for Affixing`}
        footer={
          <>
            <button onClick={() => setBulkPickModal(false)} className="btn-secondary">Cancel</button>
            <button onClick={handleBulkPick} disabled={bulkLoading} className="btn-success">
              {bulkLoading ? 'Picking…' : `Pick ${selected.length} Device(s)`}
            </button>
          </>
        }>
        <div className="space-y-4">
          <div>
            <label className="label">Affixing Date & Time <span className="text-red-500">*</span></label>
            <input type="datetime-local" className="input" value={bulkPickDate}
              onChange={e => setBulkPickDate(e.target.value)} />
          </div>
          <p className="text-xs text-gray-500">
            Records already affixed (status = AFFIXED) will be skipped automatically.
          </p>
        </div>
      </Modal>

      {/* Return Data Modal */}
      <Modal isOpen={!!returning} onClose={() => setReturning(null)} title="Return Data"
        footer={
          <>
            <button onClick={() => setReturning(null)} className="btn-secondary">Cancel</button>
            <button onClick={handleReturn} disabled={returnLoading} className="btn-warning">
              {returnLoading ? 'Returning…' : 'Confirm Return'}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">Return reason for <strong>{returning?.boe}</strong>:</p>
          <textarea className="input" rows={3} value={returnNote}
            onChange={e => setReturnNote(e.target.value)} placeholder="Return reason (required)…" />
        </div>
      </Modal>
    </div>
  );
}
