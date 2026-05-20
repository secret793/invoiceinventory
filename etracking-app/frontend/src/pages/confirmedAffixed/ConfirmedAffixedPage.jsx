import React, { useState, useEffect, useCallback } from 'react';
import api from '../../services/api';
import { downloadFile } from '../../utils/downloadFile';
import { useNotification } from '../../contexts/NotificationContext';
import { useAuth } from '../../contexts/AuthContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import SearchBar from '../../components/common/SearchBar';

export default function ConfirmedAffixedPage() {
  const { notify } = useNotification();
  const { hasRole } = useAuth();
  // Only SA, WM, and Affixing Officers may pick/return — RO Tracker and DEO are view-only
  const canAffixWrite = hasRole(['Super Admin', 'Warehouse Manager', 'Affixing Officer']);
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

  const [reportOpen,    setReportOpen]    = useState(false);
  const [reportList,    setReportList]    = useState([]);
  const [reportLoading, setReportLoading] = useState(false);
  const [reportFilters, setReportFilters] = useState({ from: '', to: '', allocation_point_id: '', status: '' });
  const [apList,        setApList]        = useState([]);

  const load = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const { data } = await api.get('/confirmed-affixed', { params: p });
      setRecords(data.data || []); setMeta(data.meta || {});
    } catch { } finally { setLoading(false); }
  }, [params]);

  const runReport = async (f = reportFilters) => {
    setReportLoading(true);
    try {
      const { data } = await api.get('/confirmed-affixed/report', { params: f });
      setReportList(Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []));
    } catch (e) { notify.error(e.message); }
    finally { setReportLoading(false); }
  };

  const openReport = () => { setReportOpen(true); runReport(); };

  const exportUrl = () => {
    const p = new URLSearchParams();
    if (reportFilters.from)                p.set('from',                reportFilters.from);
    if (reportFilters.to)                  p.set('to',                  reportFilters.to);
    if (reportFilters.allocation_point_id) p.set('allocation_point_id', reportFilters.allocation_point_id);
    if (reportFilters.status)              p.set('status',              reportFilters.status);
    return `/api/confirmed-affixed/export?${p.toString()}`;
  };

  useEffect(() => {
    load();
    const defaultDate = new Date().toISOString().slice(0, 16);
    setPickDate(defaultDate); setBulkPickDate(defaultDate);
    api.get('/allocation-points', { params: { per_page: 100 } })
      .then(r => setApList(r.data?.data || [])).catch(() => {});
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
    ...(canAffixWrite ? [{
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
    }] : []),
  ];

  return (
    <div>
      <PageHeader title="Confirmed Dispatch" subtitle="Devices dispatched and pending affixing to vehicles"
        actions={
          <div className="flex gap-2">
            {canAffixWrite && selected.length > 0 && (
              <button onClick={() => setBulkPickModal(true)} className="btn-success">
                Pick Selected ({selected.length})
              </button>
            )}
            <button onClick={openReport} className="btn-success">
              View Report
            </button>
          </div>
        } />

      <div className="flex gap-3 mb-4">
        <SearchBar onSearch={s => { const p = { ...params, search: s, page: 1 }; setParams(p); load(p); }} className="w-64" />
      </div>

      {canAffixWrite && selected.length > 0 && (
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
          selectable={canAffixWrite} selected={selected}
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

      {/* View Report Modal */}
      <Modal isOpen={reportOpen} onClose={() => setReportOpen(false)} title="Confirmed Dispatch Report" size="full"
        footer={
          <div className="flex items-center justify-between w-full">
            <button onClick={() => downloadFile(exportUrl(), 'confirmed-dispatch-report.csv').catch(() => notify.error('Export failed'))} className="btn-success btn-sm">
              Export to CSV
            </button>
            <button onClick={() => setReportOpen(false)} className="btn-secondary">Close</button>
          </div>
        }>
        <div className="space-y-4">
          {/* Filters */}
          <div className="flex flex-wrap gap-3 items-end p-3 bg-gray-50 rounded-lg">
            <div>
              <label className="label text-xs">From Date</label>
              <input type="date" className="input input-sm"
                value={reportFilters.from}
                onChange={e => setReportFilters(f => ({ ...f, from: e.target.value }))} />
            </div>
            <div>
              <label className="label text-xs">To Date</label>
              <input type="date" className="input input-sm"
                value={reportFilters.to}
                onChange={e => setReportFilters(f => ({ ...f, to: e.target.value }))} />
            </div>
            <div>
              <label className="label text-xs">Allocation Point</label>
              <select className="input input-sm w-44"
                value={reportFilters.allocation_point_id}
                onChange={e => setReportFilters(f => ({ ...f, allocation_point_id: e.target.value }))}>
                <option value="">All Stations</option>
                {apList.map(ap => <option key={ap.id} value={ap.id}>{ap.name}</option>)}
              </select>
            </div>
            <div>
              <label className="label text-xs">Status</label>
              <select className="input input-sm w-36"
                value={reportFilters.status}
                onChange={e => setReportFilters(f => ({ ...f, status: e.target.value }))}>
                <option value="">All Statuses</option>
                <option value="PENDING">Pending</option>
                <option value="AFFIXED">Affixed</option>
                <option value="RETURNED">Returned</option>
              </select>
            </div>
            <button onClick={() => runReport(reportFilters)} className="btn-primary btn-sm">Apply</button>
            <button onClick={() => {
              const reset = { from: '', to: '', allocation_point_id: '', status: '' };
              setReportFilters(reset); runReport(reset);
            }} className="btn-secondary btn-sm">Reset</button>
          </div>

          {/* Results */}
          {reportLoading ? (
            <div className="py-8 text-center text-gray-400">Loading report…</div>
          ) : reportList.length === 0 ? (
            <div className="py-8 text-center text-gray-400">No records for selected filters.</div>
          ) : (
            <div className="overflow-x-auto max-h-[60vh]">
              <p className="text-xs text-gray-500 mb-2">{reportList.length} record(s)</p>
              <table className="min-w-full text-xs">
                <thead className="bg-gray-50 sticky top-0">
                  <tr>
                    {['Date', 'Device ID', 'BOE', 'Vehicle', 'Regime', 'Route', 'Long Route',
                      'Destination', 'Manifest Date', 'Agency', 'Agent Contact', 'Truck No.', 'Driver', 'Status'].map(h => (
                      <th key={h} className="px-2 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {reportList.map((r, i) => (
                    <tr key={r.id ?? i} className="hover:bg-gray-50">
                      <td className="px-2 py-1.5 whitespace-nowrap">{r.date ? new Date(r.date).toLocaleDateString() : '—'}</td>
                      <td className="px-2 py-1.5 font-mono font-semibold" style={{ color: '#1E2D7A' }}>{r.device_identifier || r.device_id || '—'}</td>
                      <td className="px-2 py-1.5 font-mono">{r.boe || '—'}</td>
                      <td className="px-2 py-1.5">{r.vehicle_number || '—'}</td>
                      <td className="px-2 py-1.5">{r.regime || '—'}</td>
                      <td className="px-2 py-1.5">{r.route || '—'}</td>
                      <td className="px-2 py-1.5">{r.long_route || '—'}</td>
                      <td className="px-2 py-1.5">{r.destination || r.destination_name || '—'}</td>
                      <td className="px-2 py-1.5 whitespace-nowrap">{r.manifest_date ? new Date(r.manifest_date).toLocaleDateString() : <span className="text-amber-500">Pending</span>}</td>
                      <td className="px-2 py-1.5">{r.agency || '—'}</td>
                      <td className="px-2 py-1.5">{r.agent_contact || '—'}</td>
                      <td className="px-2 py-1.5">{r.truck_number || '—'}</td>
                      <td className="px-2 py-1.5">{r.driver_name || '—'}</td>
                      <td className="px-2 py-1.5"><StatusBadge status={r.status || 'PENDING'} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
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
