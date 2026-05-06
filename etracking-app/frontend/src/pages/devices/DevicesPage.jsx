import React, { useState, useEffect } from 'react';
import { useDevices } from '../../hooks/useDevices';
import { deviceService } from '../../services/deviceService';
import { allocationService } from '../../services/allocationService';
import { distributionService } from '../../services/distributionService';
import { useNotification } from '../../contexts/NotificationContext';
import { useAuth } from '../../contexts/AuthContext';
import api from '../../services/api';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DeviceForm from '../../components/devices/DeviceForm';
import DeviceFilters from '../../components/devices/DeviceFilters';

const DEVICE_STATUSES = ['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'RECEIVED', 'UNCONFIGURED', 'CONFIGURED'];

const STATUS_STYLES = {
  ONLINE:        { bg: '#dcfce7', text: '#166534', dot: '#16a34a' },
  OFFLINE:       { bg: '#fee2e2', text: '#991b1b', dot: '#dc2626' },
  DAMAGED:       { bg: '#ffedd5', text: '#9a3412', dot: '#ea580c' },
  FIXED:         { bg: '#f3e8ff', text: '#6b21a8', dot: '#9333ea' },
  LOST:          { bg: '#f3f4f6', text: '#374151', dot: '#6b7280' },
  RECEIVED:      { bg: '#dbeafe', text: '#1e40af', dot: '#3b82f6' },
  UNCONFIGURED:  { bg: '#f3f4f6', text: '#374151', dot: '#9ca3af' },
  CONFIGURED:    { bg: '#e0f2fe', text: '#075985', dot: '#0ea5e9' },
};

export default function DevicesPage() {
  const { canManageInventory } = useAuth();
  const { devices, meta, stats, loading, fetch, fetchStats, changePage, changeFilters } = useDevices();
  const { notify } = useNotification();
  const [aps, setAps] = useState([]);
  const [dps, setDps] = useState([]);

  const [showForm, setShowForm]     = useState(false);
  const [editing, setEditing]       = useState(null);
  const [deleting, setDeleting]     = useState(null);
  const [saving, setSaving]         = useState(false);
  const [selected, setSelected]     = useState([]);
  const [statusFilter, setStatusFilter] = useState('');

  const [bulkStatusVal, setBulkStatusVal]   = useState('');
  const [bulkLoading, setBulkLoading]       = useState(false);

  const [showTransferDP, setShowTransferDP] = useState(false);
  const [transferDpId, setTransferDpId]     = useState('');
  const [transferLoading, setTransferLoading] = useState(false);

  useEffect(() => {
    allocationService.list().then(setAps).catch(() => {});
    distributionService.list().then(setDps).catch(() => {});
  }, []);

  const handleStatusTabClick = (s) => {
    const next = statusFilter === s ? '' : s;
    setStatusFilter(next);
    changeFilters({ status: next });
  };

  const columns = [
    { header: 'Device ID',     key: 'device_id',    render: v => <span className="font-mono font-semibold text-gray-900">{v}</span> },
    { header: 'Type',          key: 'device_type',  render: v => v || '—' },
    { header: 'Serial',        key: 'serial_number', render: v => v || '—' },
    { header: 'SIM',           key: 'sim_number',    render: v => v || '—' },
    { header: 'Status',        key: 'status',        render: v => <StatusBadge status={v} /> },
    { header: 'Distribution Point', key: 'distribution_point_name', render: v => v || '—' },
    { header: 'Allocation Point',   key: 'allocation_point_name',   render: v => v || '—' },
    { header: 'Date Received', key: 'date_received', render: v => v ? new Date(v).toLocaleDateString() : '—' },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <div className="flex items-center gap-1">
          <button onClick={() => { setEditing(row); setShowForm(true); }}
            className="btn-secondary btn-sm">Edit</button>
          {canManageInventory() && (
            <button onClick={() => setDeleting(row)} className="btn-danger btn-sm">Delete</button>
          )}
        </div>
      ),
    },
  ];

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
      notify.success(`Created ${res.data.data?.created ?? selected.length} transfer record(s)`);
      setShowTransferDP(false); setTransferDpId(''); setSelected([]); fetch(); fetchStats();
    } catch (e) { notify.error(e.message); }
    finally { setTransferLoading(false); }
  };

  const totalDevices = Object.values(stats).reduce((a, b) => a + (b || 0), 0);

  return (
    <div>
      <PageHeader title="Devices / Trackers" subtitle="GPS tracker registry and management"
        actions={
          <div className="flex gap-2 flex-wrap">
            <button onClick={() => { setEditing(null); setShowForm(true); }} className="btn-primary">
              + New Device
            </button>
          </div>
        } />

      {/* Status Stat Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3 mb-5">
        <div className="card-sm text-center col-span-2 sm:col-span-4 xl:col-span-1 xl:col-start-auto">
          <p className="text-2xl font-bold" style={{ color: '#1E2D7A' }}>{totalDevices}</p>
          <p className="text-xs text-gray-500 mt-0.5">Total</p>
        </div>
        {DEVICE_STATUSES.map(s => {
          const st = STATUS_STYLES[s] || {};
          const cnt = stats[s] || 0;
          return (
            <div key={s}
              onClick={() => handleStatusTabClick(s)}
              className={`card-sm text-center cursor-pointer transition-all hover:shadow-md ${statusFilter === s ? 'ring-2' : ''}`}
              style={{ ringColor: '#1E2D7A', borderColor: statusFilter === s ? '#1E2D7A' : undefined }}>
              <div className="flex items-center justify-center gap-1 mb-0.5">
                <span className="w-2 h-2 rounded-full flex-shrink-0" style={{ background: st.dot }} />
                <p className="text-xs font-medium truncate" style={{ color: st.text }}>{s}</p>
              </div>
              <p className="text-xl font-bold text-gray-900">{cnt}</p>
            </div>
          );
        })}
      </div>

      {/* Filters */}
      <div className="card mb-4 p-4">
        <DeviceFilters onFilter={changeFilters} allocationPoints={aps} distributionPoints={dps} />
      </div>

      {/* Bulk Actions */}
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
          </div>
          <button onClick={() => setSelected([])} className="btn-secondary btn-sm ml-auto">Clear</button>
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
          <Pagination meta={meta} onPageChange={changePage} />
        </div>
      </div>

      {/* Form Modal */}
      <Modal isOpen={showForm} onClose={() => { setShowForm(false); setEditing(null); }}
        title={editing ? 'Edit Device' : 'New Device'} size="lg">
        <DeviceForm initial={editing || {}} onSubmit={handleSave} loading={saving}
          onCancel={() => { setShowForm(false); setEditing(null); }}
          allocationPoints={aps} distributionPoints={dps} />
      </Modal>

      {/* Transfer to DP Modal */}
      <Modal isOpen={showTransferDP} onClose={() => setShowTransferDP(false)} title="Transfer to Distribution Point"
        footer={
          <>
            <button onClick={() => setShowTransferDP(false)} className="btn-secondary">Cancel</button>
            <button onClick={handleTransferToDP} disabled={!transferDpId || transferLoading} className="btn-primary">
              {transferLoading ? 'Transferring…' : 'Create Transfer'}
            </button>
          </>
        }>
        <div className="space-y-4">
          <p className="text-sm text-gray-600">
            Create PENDING transfer records for <strong>{selected.length}</strong> device(s) to a distribution point.
            Devices with status OFFLINE, LOST, or DAMAGED will be skipped.
          </p>
          <div>
            <label className="label">Distribution Point <span className="text-red-500">*</span></label>
            <select className="input" value={transferDpId} onChange={e => setTransferDpId(e.target.value)}>
              <option value="">Select distribution point…</option>
              {dps.map(dp => <option key={dp.id} value={dp.id}>{dp.name} — {dp.location}</option>)}
            </select>
          </div>
        </div>
      </Modal>

      {/* Delete Confirm */}
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Device" danger
        message={`Delete device "${deleting?.device_id}"? This cannot be undone.`} />
    </div>
  );
}
