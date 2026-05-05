import React, { useState, useEffect } from 'react';
import { useDevices } from '../../hooks/useDevices';
import { deviceService } from '../../services/deviceService';
import { allocationService } from '../../services/allocationService';
import { distributionService } from '../../services/distributionService';
import { useNotification } from '../../contexts/NotificationContext';
import { useAuth } from '../../contexts/AuthContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DeviceForm from '../../components/devices/DeviceForm';
import DeviceFilters from '../../components/devices/DeviceFilters';
import StatCard from '../../components/common/StatCard';

export default function DevicesPage() {
  const { canManageInventory } = useAuth();
  const { devices, meta, stats, loading, fetch, fetchStats, changePage, changeFilters } = useDevices();
  const { notify } = useNotification();
  const [aps, setAps] = useState([]);
  const [dps, setDps] = useState([]);

  const [showForm, setShowForm]       = useState(false);
  const [editing, setEditing]         = useState(null);
  const [deleting, setDeleting]       = useState(null);
  const [saving, setSaving]           = useState(false);
  const [selected, setSelected]       = useState([]);
  const [bulkStatus, setBulkStatus]   = useState('');
  const [bulkLoading, setBulkLoading] = useState(false);

  useEffect(() => {
    allocationService.list().then(setAps).catch(() => {});
    distributionService.list().then(setDps).catch(() => {});
  }, []);

  const columns = [
    { header: 'Device ID',     key: 'device_id',    render: v => <span className="font-mono font-medium">{v}</span> },
    { header: 'Type',          key: 'device_type' },
    { header: 'Serial',        key: 'serial_number', render: v => v || '—' },
    { header: 'SIM',           key: 'sim_number',    render: v => v || '—' },
    { header: 'Status',        key: 'status',        render: v => <StatusBadge status={v} /> },
    { header: 'Allocation Point', key: 'allocation_point_name', render: v => v || '—' },
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
    finally     { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await deviceService.delete(deleting.id);
      notify.success('Device deleted');
      setDeleting(null); fetch(); fetchStats();
    } catch (e) { notify.error(e.message); }
  };

  const handleBulkStatus = async () => {
    if (!selected.length || !bulkStatus) return;
    setBulkLoading(true);
    try {
      await deviceService.bulkStatus(selected, bulkStatus);
      notify.success(`Updated ${selected.length} device(s) to ${bulkStatus}`);
      setSelected([]); fetch(); fetchStats();
    } catch (e) { notify.error(e.message); }
    finally     { setBulkLoading(false); }
  };

  return (
    <div>
      <PageHeader title="Devices" subtitle="Manage all GPS tracking devices"
        actions={canManageInventory() && (
          <button onClick={() => { setEditing(null); setShowForm(true); }} className="btn-primary">
            + Add Device
          </button>
        )} />

      {/* Stats */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        {Object.entries(stats).map(([status, count]) => (
          <StatCard key={status} label={status} value={count}
            icon="📱"
            color={status === 'ACTIVE' ? 'green' : status === 'FAULTY' ? 'red' : 'blue'}
            onClick={() => changeFilters({ status })} />
        ))}
      </div>

      {/* Filters */}
      <div className="card mb-4">
        <DeviceFilters onFilter={changeFilters} allocationPoints={aps} distributionPoints={dps} />
      </div>

      {/* Bulk actions */}
      {selected.length > 0 && (
        <div className="flex items-center gap-3 mb-4 bg-blue-50 rounded-xl px-4 py-3 border border-blue-200">
          <span className="text-sm text-blue-800 font-medium">{selected.length} selected</span>
          <select value={bulkStatus} onChange={e => setBulkStatus(e.target.value)} className="input w-40">
            <option value="">Set status…</option>
            <option value="CONFIGURED">CONFIGURED</option>
            <option value="ALLOCATED">ALLOCATED</option>
            <option value="ACTIVE">ACTIVE</option>
            <option value="FAULTY">FAULTY</option>
            <option value="RETURNED">RETURNED</option>
          </select>
          <button onClick={handleBulkStatus} disabled={!bulkStatus || bulkLoading} className="btn-primary btn-sm">
            {bulkLoading ? 'Updating…' : 'Apply'}
          </button>
          <button onClick={() => setSelected([])} className="btn-secondary btn-sm ml-auto">Clear</button>
        </div>
      )}

      {/* Table */}
      <div className="card">
        <DataTable columns={columns} data={devices} loading={loading}
          selectable selected={selected}
          onSelect={(id, checked) => setSelected(prev => checked ? [...prev, id] : prev.filter(x => x !== id))}
          onSelectAll={checked => setSelected(checked ? devices.map(d => d.id) : [])}
          emptyMessage="No devices found. Add your first device." />
        <Pagination meta={meta} onPageChange={changePage} />
      </div>

      {/* Form Modal */}
      <Modal isOpen={showForm} onClose={() => { setShowForm(false); setEditing(null); }}
        title={editing ? 'Edit Device' : 'Add New Device'} size="lg">
        <DeviceForm initial={editing || {}} onSubmit={handleSave} loading={saving}
          onCancel={() => { setShowForm(false); setEditing(null); }}
          allocationPoints={aps} distributionPoints={dps} />
      </Modal>

      {/* Delete Confirm */}
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Device" danger
        message={`Delete device "${deleting?.device_id}"? This cannot be undone.`} />
    </div>
  );
}
