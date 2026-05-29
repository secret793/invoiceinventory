import React, { useState, useEffect, useRef } from 'react';
import { useDevices } from '../../hooks/useDevices';
import { deviceService } from '../../services/deviceService';
import { configService } from '../../services/configService';
import { distributionService } from '../../services/distributionService';
import { useNotification } from '../../contexts/NotificationContext';
import api from '../../services/api';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DeviceForm from '../../components/devices/DeviceForm';

const DEVICE_STATUSES = ['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'RECEIVED', 'UNCONFIGURED', 'CONFIGURED'];

const STATUS_STYLES = {
  ONLINE:        { bg: '#dcfce7', text: '#166534', dot: '#16a34a' },
  OFFLINE:       { bg: '#fee2e2', text: '#991b1b', dot: '#dc2626' },
  DAMAGED:       { bg: '#ffedd5', text: '#9a3412', dot: '#ea580c' },
  FIXED:         { bg: '#f3e8ff', text: '#6b21a8', dot: '#9333ea' },
  LOST:          { bg: '#f3f4f6', text: '#374151', dot: '#6b7280' },
  RECEIVED:      { bg: '#dbeafe', text: '#1e40af', dot: '#3b82f6' },
  UNCONFIGURED:  { bg: '#fef9c3', text: '#854d0e', dot: '#ca8a04' },
  CONFIGURED:    { bg: '#e0f2fe', text: '#075985', dot: '#0ea5e9' },
};

export default function DevicesPage() {
  const { devices, meta, stats, loading, fetch, fetchStats, changePage, changeFilters } = useDevices();
  const { notify } = useNotification();
  const [companies, setCompanies] = useState([]);
  const [dps, setDps] = useState([]);

  const [showForm, setShowForm]   = useState(false);
  const [editing, setEditing]     = useState(null);
  const [deleting, setDeleting]   = useState(null);
  const [saving, setSaving]       = useState(false);
  const [selected, setSelected]   = useState([]);
  const [statusFilter, setStatusFilter] = useState('');

  const [bulkStatusVal, setBulkStatusVal] = useState('');
  const [bulkLoading, setBulkLoading]     = useState(false);

  const [showTransferDP, setShowTransferDP]   = useState(false);
  const [transferDpId, setTransferDpId]       = useState('');
  const [transferLoading, setTransferLoading] = useState(false);

  const [showBulkDelete, setShowBulkDelete] = useState(false);
  const [bulkDeleting, setBulkDeleting]     = useState(false);

  const [showImport, setShowImport]   = useState(false);
  const [importFile, setImportFile]   = useState(null);
  const [importing, setImporting]     = useState(false);
  const [importResult, setImportResult] = useState(null);
  const fileInputRef = useRef(null);

  useEffect(() => {
    configService.companies.list().then(setCompanies).catch(() => {});
    distributionService.list().then(setDps).catch(() => {});
  }, []);

  const handleStatusTabClick = (s) => {
    const next = statusFilter === s ? '' : s;
    setStatusFilter(next);
    changeFilters({ status: next });
  };

  const handleDownloadTemplate = async () => {
    try {
      const res = await api.get(`/devices/import-template?t=${Date.now()}`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([res.data], { type: 'text/csv' }));
      const a = document.createElement('a');
      a.href = url; a.download = 'Device_Import_Template.csv';
      document.body.appendChild(a); a.click();
      window.URL.revokeObjectURL(url); document.body.removeChild(a);
    } catch { notify.error('Failed to download template'); }
  };

  const handleImport = async () => {
    if (!importFile) { notify.error('Please select a file to import'); return; }
    setImporting(true);
    setImportResult(null);
    try {
      const formData = new FormData();
      formData.append('file', importFile);
      const res = await api.post('/devices/import', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      const d = res.data.data || {};
      setImportResult(d);
      notify.success(`${d.created || 0} device(s) imported successfully`);
      if (!d.errors?.length) { setShowImport(false); setImportFile(null); }
      fetch(); fetchStats();
    } catch (e) { notify.error(e.message); }
    finally { setImporting(false); }
  };

  const handleSave = async (data) => {
    setSaving(true);
    try {
      if (editing) await deviceService.update(editing.id, data);
      else         await deviceService.create(data);
      notify.success(`Device ${editing ? 'updated' : 'created'} successfully`);
      setShowForm(false); setEditing(null);
      fetch(); fetchStats();
    } catch (e) { notify.error(e.message); }
    finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await deviceService.delete(deleting.id);
      notify.success('Device deleted');
      setDeleting(null); fetch(); fetchStats();
    } catch (e) { notify.error(e.message); }
  };

  const handleBulkStatus = async () => {
    if (!selected.length || !bulkStatusVal) return;
    setBulkLoading(true);
    try {
      await deviceService.bulkStatus(selected, bulkStatusVal);
      notify.success(`Updated ${selected.length} device(s) to ${bulkStatusVal}`);
      setSelected([]); setBulkStatusVal(''); fetch(); fetchStats();
    } catch (e) { notify.error(e.message); }
    finally { setBulkLoading(false); }
  };

  const handleTransferToDP = async () => {
    if (!transferDpId) { notify.error('Please select a distribution point'); return; }
    setTransferLoading(true);
    try {
      const res = await api.post('/transfers/bulk-to-dp', { ids: selected, distribution_point_id: transferDpId });
      const created = res.data.data?.created ?? selected.length;
      notify.success(`${created} transfer record(s) created. Go to Transfers to approve.`);
      setShowTransferDP(false); setTransferDpId(''); setSelected([]); fetch(); fetchStats();
    } catch (e) { notify.error(e.message); }
    finally { setTransferLoading(false); }
  };

  const handleBulkDelete = async () => {
    setBulkDeleting(true);
    try {
      const res = await api.post('/devices/bulk-delete', { ids: selected });
      notify.success(`${res.data.data?.deleted ?? selected.length} device(s) deleted`);
      setShowBulkDelete(false); setSelected([]); fetch(); fetchStats();
    } catch (e) { notify.error(e.message); }
    finally { setBulkDeleting(false); }
  };

  const totalDevices = Object.values(stats).reduce((a, b) => a + (b || 0), 0);

  const columns = [
    { header: 'Device ID',    key: 'device_id',    render: v => <span className="font-mono font-semibold text-gray-900">{v}</span> },
    { header: 'Type',         key: 'device_type',  render: v => v || '—' },
    { header: 'Serial',       key: 'serial_number', render: v => v || '—' },
    { header: 'Batch',        key: 'batch_number', render: v => v || '—' },
    { header: 'Status',       key: 'status',        render: v => <StatusBadge status={v} /> },
    { header: 'Distribution Point', key: 'distribution_point_name', render: v => v || '—' },
    { header: 'Company',   key: 'company_name',   render: v => v || '—' },
    { header: 'SIM',          key: 'sim_number',   render: v => v || '—' },
    { header: 'Operator',     key: 'sim_operator', render: v => v || '—' },
    { header: 'Added By',     key: 'added_by_name', render: v => v || '—' },
    { header: 'Date Received', key: 'date_received', render: v => v ? new Date(v).toLocaleDateString() : '—' },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <div className="flex items-center gap-1">
          <button onClick={() => { setEditing(row); setShowForm(true); }} className="btn-secondary btn-sm">Edit</button>
          <button onClick={() => setDeleting(row)} className="btn-danger btn-sm">Delete</button>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader title="Devices / Trackers" subtitle="GPS tracker registry and management"
        actions={
          <div className="flex gap-2 flex-wrap">
            <button onClick={handleDownloadTemplate} className="btn-secondary btn-sm">
              ⬇ Download Excel
            </button>
            <button onClick={() => { setShowImport(true); setImportResult(null); setImportFile(null); }}
              className="btn-danger btn-sm">
              ⬆ Import Products
            </button>
            <button onClick={() => { setEditing(null); setShowForm(true); }} className="btn-primary">
              + New Device
            </button>
          </div>
        } />

      {/* Status Stat Cards — clickable filters */}
      <div className="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-9 gap-3 mb-5">
        {/* Total card */}
        <div className="card-sm text-center cursor-default">
          <p className="text-2xl font-bold" style={{ color: '#1E2D7A' }}>{totalDevices}</p>
          <p className="text-xs text-gray-500 mt-0.5">Total</p>
        </div>
        {DEVICE_STATUSES.map(s => {
          const st = STATUS_STYLES[s] || {};
          const cnt = stats[s] || 0;
          const active = statusFilter === s;
          return (
            <button key={s} onClick={() => handleStatusTabClick(s)}
              className="card-sm text-center transition-all hover:shadow-md focus:outline-none"
              style={{
                border: `2px solid ${active ? '#1E2D7A' : '#e5e7eb'}`,
                background: active ? '#eef1fb' : 'white',
              }}>
              <div className="flex items-center justify-center gap-1 mb-0.5">
                <span className="w-2 h-2 rounded-full flex-shrink-0" style={{ background: st.dot }} />
                <p className="text-xs font-medium truncate" style={{ color: st.text }}>{s}</p>
              </div>
              <p className="text-xl font-bold text-gray-900">{cnt}</p>
            </button>
          );
        })}
      </div>

      {/* Bulk Actions Bar */}
      {selected.length > 0 && (
        <div className="flex flex-wrap items-center gap-3 mb-4 rounded-xl px-4 py-3 border"
          style={{ background: '#eef1fb', borderColor: '#c7cef0' }}>
          <span className="text-sm font-semibold" style={{ color: '#1E2D7A' }}>{selected.length} selected</span>
          <div className="flex items-center gap-2 flex-wrap">
            <select value={bulkStatusVal} onChange={e => setBulkStatusVal(e.target.value)} className="input w-44">
              <option value="">Change Status…</option>
              {DEVICE_STATUSES.map(s => <option key={s} value={s}>{s}</option>)}
            </select>
            <button onClick={handleBulkStatus} disabled={!bulkStatusVal || bulkLoading} className="btn-primary btn-sm">
              {bulkLoading ? 'Updating…' : 'Apply Status'}
            </button>
            <button onClick={() => setShowTransferDP(true)} className="btn-secondary btn-sm">
              Transfer to Distribution Point
            </button>
            <button onClick={() => api.post('/devices/sync-icloud', { ids: selected })
              .then(() => notify.success('iCloud sync initiated'))
              .catch(e => notify.error(e.message))
            } className="btn-secondary btn-sm">
              Sync iCloud GUIDs
            </button>
            <button onClick={() => setShowBulkDelete(true)} className="btn-danger btn-sm">
              Delete Selected
            </button>
          </div>
          <button onClick={() => setSelected([])} className="btn-secondary btn-sm ml-auto">Clear</button>
        </div>
      )}

      {/* Active filter indicator */}
      {statusFilter && (
        <div className="flex items-center gap-2 mb-3">
          <span className="text-sm text-gray-500">Filtered by:</span>
          <span className="px-2 py-0.5 rounded-full text-xs font-semibold" style={{ background: STATUS_STYLES[statusFilter]?.bg, color: STATUS_STYLES[statusFilter]?.text }}>
            {statusFilter}
          </span>
          <button onClick={() => { setStatusFilter(''); changeFilters({ status: '' }); }} className="text-xs text-gray-400 hover:text-gray-700">✕ Clear filter</button>
        </div>
      )}

      {/* Table */}
      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={devices} loading={loading}
          selectable selected={selected}
          onSelect={(id, checked) => setSelected(prev => checked ? [...prev, id] : prev.filter(x => x !== id))}
          onSelectAll={checked => setSelected(checked ? devices.map(d => d.id) : [])}
          emptyMessage="No devices found. Add your first device." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination
            meta={meta}
            onPageChange={changePage}
            onPerPageChange={(perPage) => changeFilters({ per_page: perPage })}
            allowAll
          />
        </div>
      </div>

      {/* New / Edit Device Modal */}
      <Modal isOpen={showForm} onClose={() => { setShowForm(false); setEditing(null); }}
        title={editing ? 'Edit Device' : 'New Device'} size="lg">
        <DeviceForm initial={editing || {}} onSubmit={handleSave} loading={saving}
          onCancel={() => { setShowForm(false); setEditing(null); }}
          companies={companies} distributionPoints={dps} />
      </Modal>

      {/* Import Products Modal */}
      <Modal isOpen={showImport} onClose={() => setShowImport(false)} title="Import Products (Bulk)"
        footer={
          <>
            <button onClick={() => setShowImport(false)} className="btn-secondary">Close</button>
            <button onClick={handleImport} disabled={!importFile || importing} className="btn-primary">
              {importing ? 'Importing…' : 'Import'}
            </button>
          </>
        }>
        <div className="space-y-4">
          <p className="text-sm text-gray-600">
            Upload a <strong>.csv</strong> or <strong>.xlsx</strong> file to bulk-import devices.
            Download the template first to ensure the correct column format.
          </p>
          <button onClick={handleDownloadTemplate} className="btn-secondary btn-sm w-full">
            ⬇ Download Import Template (CSV)
          </button>
          <div>
            <label className="label">Select File <span className="text-red-500">*</span></label>
            <input ref={fileInputRef} type="file" accept=".csv,.xlsx"
              onChange={e => setImportFile(e.target.files[0] || null)}
              className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:cursor-pointer"
              style={{ '--file-bg': '#1E2D7A', '--file-color': 'white' }} />
            {importFile && <p className="text-xs text-gray-500 mt-1">Selected: {importFile.name}</p>}
          </div>
          {importResult && (
            <div className="rounded-lg p-3 space-y-2" style={{ background: '#f0fdf4', border: '1px solid #86efac' }}>
              <p className="text-sm font-semibold text-green-800">
                ✓ {importResult.created} device(s) imported
                {importResult.skipped ? `, ${importResult.skipped} skipped` : ''}
              </p>
              {importResult.errors?.length > 0 && (() => {
                const warns = importResult.errors.filter(e => e.includes('not found'));
                const errs  = importResult.errors.filter(e => !e.includes('not found'));
                return (
                  <>
                    {errs.length > 0 && (
                      <div className="mt-1">
                        <p className="text-xs font-semibold text-red-700 mb-1">Errors ({errs.length} row{errs.length !== 1 ? 's' : ''} skipped):</p>
                        <ul className="text-xs text-red-600 space-y-0.5 max-h-28 overflow-y-auto">
                          {errs.map((e, i) => <li key={i}>• {e}</li>)}
                        </ul>
                      </div>
                    )}
                    {warns.length > 0 && (
                      <div className="mt-1">
                        <p className="text-xs font-semibold text-yellow-700 mb-1">Warnings ({warns.length}):</p>
                        <ul className="text-xs text-yellow-700 space-y-0.5 max-h-28 overflow-y-auto">
                          {warns.map((w, i) => <li key={i}>⚠ {w}</li>)}
                        </ul>
                      </div>
                    )}
                  </>
                );
              })()}
            </div>
          )}
        </div>
      </Modal>

      {/* Transfer to DP Modal */}
      <Modal isOpen={showTransferDP} onClose={() => setShowTransferDP(false)} title="Transfer to Distribution Point"
        footer={
          <>
            <button onClick={() => setShowTransferDP(false)} className="btn-secondary">Cancel</button>
            <button onClick={handleTransferToDP} disabled={!transferDpId || transferLoading} className="btn-primary">
              {transferLoading ? 'Creating…' : 'Create Transfer Records'}
            </button>
          </>
        }>
        <div className="space-y-4">
          <div className="rounded-lg p-3 text-sm" style={{ background: '#eff6ff', border: '1px solid #bfdbfe' }}>
            <p className="font-semibold text-blue-800 mb-1">How this works:</p>
            <ol className="text-blue-700 space-y-1 list-decimal list-inside">
              <li>PENDING transfer records are created for {selected.length} device(s)</li>
              <li>Go to <strong>Transfers</strong> page to review and approve them</li>
              <li>Once approved, devices appear at the chosen distribution point</li>
            </ol>
            <p className="text-blue-600 text-xs mt-2">Note: Devices with status OFFLINE, LOST, or DAMAGED will be skipped. Devices already assigned to a distribution point will also be skipped.</p>
          </div>
          <div>
            <label className="label">Destination Distribution Point <span className="text-red-500">*</span></label>
            <select className="input" value={transferDpId} onChange={e => setTransferDpId(e.target.value)}>
              <option value="">Select distribution point…</option>
              {dps.map(dp => <option key={dp.id} value={dp.id}>{dp.name}{dp.location ? ` — ${dp.location}` : ''}</option>)}
            </select>
          </div>
        </div>
      </Modal>

      {/* Bulk Delete Confirm */}
      <ConfirmDialog isOpen={showBulkDelete} onClose={() => setShowBulkDelete(false)}
        onConfirm={handleBulkDelete} title="Delete Selected Devices" danger
        loading={bulkDeleting}
        message={`Delete ${selected.length} selected device(s)? This will also remove them from the store. This cannot be undone.`} />

      {/* Single Delete Confirm */}
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Device" danger
        message={`Delete device "${deleting?.device_id}"? This cannot be undone.`} />
    </div>
  );
}
