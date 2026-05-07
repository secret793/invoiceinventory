import React, { useState, useEffect, useRef, useCallback } from 'react';
import api from '../../services/api';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';

export default function MonitoringPage() {
  const { notify } = useNotification();
  const [records, setRecords] = useState([]);
  const [meta, setMeta]       = useState({});
  const [loading, setLoading] = useState(false);
  const [serverTime, setServerTime] = useState(new Date());
  const [params, setParams]   = useState({ page: 1, per_page: 25 });
  const [overdueOnly, setOverdueOnly] = useState(false);

  const [noteModal, setNoteModal]       = useState(null);
  const [noteText, setNoteText]         = useState('');
  const [manifestDate, setManifestDate] = useState('');
  const [noteSaving, setNoteSaving]     = useState(false);

  const pollRef   = useRef(null);
  const paramsRef = useRef(params);
  paramsRef.current = params;

  const load = useCallback(async (p) => {
    const query = p ?? paramsRef.current;
    setLoading(true);
    try {
      const { data } = await api.get('/monitoring', { params: query });
      setRecords(data.data || []); setMeta(data.meta || {});
    } catch { } finally { setLoading(false); }
  }, []);

  const silentLoad = useCallback(async () => {
    try {
      const { data } = await api.get('/monitoring', { params: paramsRef.current });
      setRecords(data.data || []); setMeta(data.meta || {});
    } catch { }
  }, []);

  useEffect(() => {
    load();
    pollRef.current = setInterval(() => {
      silentLoad();
      setServerTime(new Date());
    }, 10000);
    const clock = setInterval(() => setServerTime(new Date()), 1000);
    return () => { clearInterval(pollRef.current); clearInterval(clock); };
  }, []);

  const applyFilter = (extra) => {
    const p = { ...paramsRef.current, ...extra, page: 1 };
    setParams(p);
    load(p);
  };

  const handleOverdueOnly = () => {
    const next = !overdueOnly;
    setOverdueOnly(next);
    applyFilter({ overdue: next ? '1' : '' });
  };

  const handleAddNote = async () => {
    if (!noteText.trim()) { notify.error('Note is required'); return; }
    setNoteSaving(true);
    try {
      await api.post(`/monitoring/${noteModal.id}/add-note`, { note: noteText, manifest_date: manifestDate || null });
      notify.success('Note Added — Your note has been saved successfully!');
      setNoteModal(null); setNoteText(''); setManifestDate(''); load();
    } catch (e) { notify.error(e.message); }
    finally { setNoteSaving(false); }
  };

  const fmtDuration = (hours) => {
    if (!hours || hours <= 0) return <span className="text-green-600 text-xs font-medium">On time</span>;
    const d = Math.floor(hours / 24);
    const h = hours % 24;
    const txt = d > 0 ? `${d}d ${h}h` : `${h}h`;
    return <span className="text-red-600 font-bold text-xs">{txt}</span>;
  };

  const columns = [
    { header: 'Dispatch Date',  key: 'dispatch_date',   render: v => v ? new Date(v).toLocaleDateString() : '—' },
    { header: 'Device ID',      key: 'device_identifier', render: v => <span className="font-mono font-semibold" style={{ color: '#1E2D7A' }}>{v || '—'}</span> },
    { header: 'BOE',            key: 'boe',             render: v => <span className="font-mono text-xs">{v || '—'}</span> },
    { header: 'Vehicle',        key: 'vehicle_number',  render: v => <span className="text-xs">{v || '—'}</span> },
    { header: 'Regime',         key: 'regime',          render: v => <span className="text-xs">{v || '—'}</span> },
    { header: 'Route',          key: 'route',           render: v => <span className="text-xs">{v || '—'}</span> },
    { header: 'Long Route',     key: 'long_route',      render: v => <span className="text-xs">{v || '—'}</span> },
    { header: 'Manifest Date',  key: 'manifest_date',
      render: v => v ? <span className="text-xs">{new Date(v).toLocaleDateString()}</span> : <span className="badge-yellow text-xs">Pending</span>
    },
    { header: 'Destination',    key: 'destination_name', render: v => <span className="text-xs">{v || '—'}</span> },
    { header: 'Agency',         key: 'agency',          render: v => <span className="text-xs">{v || '—'}</span> },
    { header: 'Agent Contact',  key: 'agent_contact',   render: v => <span className="text-xs">{v || '—'}</span> },
    { header: 'Truck No.',      key: 'truck_number',    render: v => <span className="text-xs">{v || '—'}</span> },
    { header: 'Driver',         key: 'driver_name',     render: v => <span className="text-xs">{v || '—'}</span> },
    { header: 'Station',        key: 'allocation_point_name', render: v => <span className="text-xs">{v || '—'}</span> },
    { header: 'Affix Date',     key: 'affixing_date',   render: v => <span className="text-xs">{v ? new Date(v).toLocaleString() : '—'}</span> },
    { header: 'Overdue Hours',  key: 'overdue_hours',   render: v => fmtDuration(v) },
    {
      header: 'Overstay Days',  key: 'overstay_days',
      render: (v, row) => {
        const days = parseInt(v) || 0;
        if (days > 0) return (
          <div>
            <span className="text-red-600 font-bold text-xs">{days} day(s)</span>
            {row.overstay_amount > 0 && (
              <p className="text-red-400 text-xs">GMD {Number(row.overstay_amount).toLocaleString()}</p>
            )}
          </div>
        );
        return <span className="text-green-600 text-xs font-medium">On time</span>;
      }
    },
    { header: 'Retrieval',      key: 'retrieval_status', render: v => <StatusBadge status={v || 'NOT_RETRIEVED'} /> },
    {
      header: 'Note', key: 'id',
      render: (_, row) => (
        <button
          onClick={() => { setNoteModal(row); setNoteText(row.note || ''); setManifestDate(row.manifest_date ? row.manifest_date.slice(0, 16) : ''); }}
          className="btn-secondary btn-sm whitespace-nowrap">
          ✎ Note
        </button>
      ),
    },
  ];

  return (
    <div>
      <PageHeader title="Monitoring" subtitle="Live device tracking — refreshes every 10 seconds"
        actions={
          <div className="flex items-center gap-3">
            <div className="text-right hidden sm:block">
              <p className="text-xs text-gray-400">Server Time</p>
              <p className="text-sm font-mono font-semibold text-gray-700">{serverTime.toLocaleTimeString()}</p>
            </div>
            <button onClick={handleOverdueOnly}
              className={overdueOnly ? 'btn-danger' : 'btn-secondary'}>
              {overdueOnly ? '🔴 Overdue Only' : 'Overdue Devices'}
            </button>
          </div>
        } />

      {/* Filters */}
      <div className="flex flex-wrap gap-3 mb-4 card p-4">
        <input type="number" placeholder="Min overstay days" className="input w-36"
          onChange={e => applyFilter({ overstay_min: e.target.value })} />
        <input type="number" placeholder="Max overstay days" className="input w-36"
          onChange={e => applyFilter({ overstay_max: e.target.value })} />
        <select className="input w-48" onChange={e => applyFilter({ retrieval_status: e.target.value })}>
          <option value="">All Retrieval Statuses</option>
          <option value="NOT_RETRIEVED">Not Retrieved</option>
          <option value="RETRIEVED">Retrieved</option>
          <option value="RETURNED">Returned</option>
        </select>
        <input type="text" placeholder="Search BOE / Vehicle / Device…" className="input w-56"
          onChange={e => applyFilter({ search: e.target.value })} />
        <button onClick={() => {
          const p = { page: 1, per_page: 25 };
          setParams(p); setOverdueOnly(false); load(p);
        }} className="btn-secondary">Reset</button>
      </div>

      <div className="card p-0 overflow-hidden">
        <div className="px-4 py-2 border-b border-gray-100 flex items-center justify-between">
          <span className="text-xs text-gray-400">
            {meta.total ?? 0} record(s) · Auto-refreshes every 10s
          </span>
          <span className="inline-flex items-center gap-1.5">
            <span className="w-2 h-2 rounded-full bg-green-400 animate-pulse" />
            <span className="text-xs text-green-600 font-medium">Live</span>
          </span>
        </div>
        <DataTable columns={columns} data={records} loading={loading}
          emptyMessage="No monitoring records found." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination meta={meta} onPageChange={p => {
            const np = { ...paramsRef.current, page: p };
            setParams(np); load(np);
          }} />
        </div>
      </div>

      {/* Add Note Modal */}
      <Modal isOpen={!!noteModal} onClose={() => setNoteModal(null)} title="Add Note"
        footer={
          <>
            <button onClick={() => setNoteModal(null)} className="btn-secondary">Cancel</button>
            <button onClick={handleAddNote} disabled={noteSaving} className="btn-primary">
              {noteSaving ? 'Saving…' : 'Save Note'}
            </button>
          </>
        }>
        {noteModal && (
          <div className="space-y-4">
            <div className="bg-gray-50 rounded-lg p-3 text-sm">
              <p className="text-gray-500">Device: <strong className="font-mono">{noteModal.device_identifier}</strong></p>
              <p className="text-gray-500">BOE: <strong className="font-mono">{noteModal.boe || '—'}</strong></p>
              <p className="text-gray-500">Vehicle: <strong>{noteModal.vehicle_number || '—'}</strong></p>
            </div>
            <div>
              <label className="label">Note <span className="text-red-500">*</span></label>
              <textarea className="input" rows={4} value={noteText}
                onChange={e => setNoteText(e.target.value)}
                placeholder="Enter note (max 1000 characters)…"
                maxLength={1000} />
              <p className="text-xs text-gray-400 mt-1">{noteText.length}/1000</p>
            </div>
            <div>
              <label className="label">Manifest Date <span className="text-gray-400 text-xs">(optional — updates the dispatch manifest date)</span></label>
              <input type="datetime-local" className="input" value={manifestDate}
                onChange={e => setManifestDate(e.target.value)} />
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
