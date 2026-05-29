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
import { Input, Select } from '../../components/common/FormField';

const STORE_STATUSES = ['UNCONFIGURED', 'CONFIGURED', 'ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST'];

const STATUS_DOT = {
  ONLINE: '#16a34a', OFFLINE: '#dc2626', DAMAGED: '#ea580c',
  FIXED: '#9333ea', LOST: '#6b7280', UNCONFIGURED: '#ca8a04', CONFIGURED: '#0ea5e9',
};

const STATUS_BG = {
  ONLINE: '#dcfce7', OFFLINE: '#fee2e2', DAMAGED: '#ffedd5',
  FIXED: '#f3e8ff', LOST: '#f3f4f6', UNCONFIGURED: '#fef9c3', CONFIGURED: '#e0f2fe',
};

export default function StoresPage() {
  const { notify }        = useNotification();
  const [items, setItems] = useState([]);
  const [stats, setStats] = useState({});
  const [meta, setMeta]   = useState({});
  const [loading, setLoading] = useState(false);
  const [params, setParams]   = useState({ page: 1, per_page: 25 });
  const [statusFilter, setStatusFilter] = useState('');

  const [showEdit, setShowEdit]   = useState(false);
  const [editing, setEditing]     = useState(null);
  const [deleting, setDeleting]   = useState(null);
  const [saving, setSaving]       = useState(false);
  const [selected, setSelected]   = useState([]);

  const [showBulkStatus, setShowBulkStatus] = useState(false);
  const [bulkStatusVal, setBulkStatusVal]   = useState('');
  const [bulkLoading, setBulkLoading]       = useState(false);

  const [showBulkDelete, setShowBulkDelete] = useState(false);
  const [bulkDeleting, setBulkDeleting]     = useState(false);

  const [editForm, setEditForm] = useState({ status: 'UNCONFIGURED', sim_number: '', sim_operator: '', batch_number: '' });

  const fetchItems = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const { data } = await api.get('/stores', { params: p });
      setItems(data.data || []); setMeta(data.meta || {});
    } catch { } finally { setLoading(false); }
  }, [params]);

  const fetchStats = useCallback(async () => {
    try {
      const { data } = await api.get('/stores/stats');
      setStats(data.data || {});
    } catch { }
  }, []);

  useEffect(() => { fetchItems(); fetchStats(); }, []);

  const handleStatusTab = (s) => {
    const next = statusFilter === s ? '' : s;
    setStatusFilter(next);
    const p = { ...params, status: next, page: 1 };
    setParams(p); fetchItems(p);
  };

  const handleSearch = (search) => {
    const p = { ...params, search, page: 1 };
    setParams(p); fetchItems(p);
  };

  const openEdit = (row) => {
    setEditing(row);
    setEditForm({
      status: row.status || 'UNCONFIGURED',
      sim_number: row.sim_number || '',
      sim_operator: row.sim_operator || '',
      batch_number: row.batch_number || '',
    });
    setShowEdit(true);
  };

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      await api.put(`/stores/${editing.id}`, editForm);
      notify.success('Stock item updated');
      setShowEdit(false); setEditing(null); fetchItems(); fetchStats();
    } catch (e) { notify.error(e.message); }
    finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await api.delete(`/stores/${deleting.id}`);
      notify.success('Item deleted'); setDeleting(null); fetchItems(); fetchStats();
    } catch (e) { notify.error(e.message); }
  };

  const handleBulkStatus = async () => {
    if (!bulkStatusVal || !selected.length) return;
    setBulkLoading(true);
    try {
      await api.post('/stores/bulk-status', { ids: selected, status: bulkStatusVal });
      notify.success(`Updated ${selected.length} item(s) to ${bulkStatusVal}`);
      setShowBulkStatus(false); setBulkStatusVal(''); setSelected([]); fetchItems(); fetchStats();
    } catch (e) { notify.error(e.message); }
    finally { setBulkLoading(false); }
  };

  const handleBulkDelete = async () => {
    setBulkDeleting(true);
    try {
      const res = await api.post('/stores/bulk-delete', { ids: selected });
      notify.success(`${res.data.data?.deleted ?? selected.length} item(s) deleted`);
      setShowBulkDelete(false); setSelected([]); fetchItems(); fetchStats();
    } catch (e) { notify.error(e.message); }
    finally { setBulkDeleting(false); }
  };

  const total = Object.values(stats).reduce((a, b) => a + (b || 0), 0);

  const columns = [
    { header: 'Device ID',    key: 'serial_number', render: v => <span className="font-mono font-semibold text-gray-900">{v || '—'}</span> },
    { header: 'Device Type',  key: 'device_type',   render: v => v || '—' },
    { header: 'Batch Number', key: 'batch_number',  render: v => v || '—' },
    { header: 'Status',       key: 'status',        render: v => <StatusBadge status={v} /> },
    { header: 'Date Received', key: 'date_received', render: v => v ? new Date(v).toLocaleDateString() : '—' },
    { header: 'SIM Number',   key: 'sim_number',    render: v => v || '—' },
    { header: 'SIM Operator', key: 'sim_operator',  render: v => v || '—' },
    { header: 'Added By',     key: 'added_by_name', render: v => v || '—' },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <div className="flex gap-1">
          <button onClick={() => openEdit(row)} className="btn-secondary btn-sm">Edit</button>
          <button onClick={() => setDeleting(row)} className="btn-danger btn-sm">Delete</button>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader title="Stores / Device Stock" subtitle="Device stock — auto-synced from Devices / Trackers section" />

      {/* Stats Row */}
      <div className="flex flex-wrap gap-3 mb-5">
        <div className="card-sm flex flex-col items-center justify-center min-w-[80px] text-center">
          <p className="text-2xl font-bold" style={{ color: '#1E2D7A' }}>{total}</p>
          <p className="text-xs text-gray-500">Total</p>
        </div>
        {STORE_STATUSES.map(s => (
          <button key={s} onClick={() => handleStatusTab(s)}
            className="card-sm flex items-center gap-2 min-w-[100px] transition-all hover:shadow-md focus:outline-none"
            style={{
              border: `2px solid ${statusFilter === s ? '#1E2D7A' : '#e5e7eb'}`,
              background: statusFilter === s ? STATUS_BG[s] || '#eef1fb' : 'white',
            }}>
            <span className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ background: STATUS_DOT[s] }} />
            <div className="text-left">
              <p className="text-xs text-gray-500">{s}</p>
              <p className="text-lg font-bold text-gray-900">{stats[s] || 0}</p>
            </div>
          </button>
        ))}
      </div>

      {/* Info notice */}
      <div className="rounded-lg px-4 py-2 mb-4 text-sm flex items-center gap-2" style={{ background: '#eff6ff', border: '1px solid #bfdbfe', color: '#1e40af' }}>
        <span>ℹ️</span>
        <span>Devices created in <strong>Devices / Trackers</strong> appear here automatically. You can edit status/SIM details or transfer devices from there.</span>
      </div>

      {/* Search & bulk actions */}
      <div className="flex flex-wrap gap-3 mb-4 items-center">
        <SearchBar onSearch={handleSearch} className="w-64" placeholder="Search by ID, type, batch…" />
        {selected.length > 0 && (
          <div className="flex items-center gap-2 flex-wrap">
            <span className="text-sm font-semibold" style={{ color: '#1E2D7A' }}>{selected.length} selected</span>
            <button onClick={() => setShowBulkStatus(true)} className="btn-secondary btn-sm">
              Change Status
            </button>
            <button onClick={() => setShowBulkDelete(true)} className="btn-danger btn-sm">
              Delete Selected
            </button>
            <button onClick={() => setSelected([])} className="btn-secondary btn-sm">Clear</button>
          </div>
        )}
      </div>

      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={items} loading={loading}
          selectable selected={selected}
          onSelect={(id, checked) => setSelected(prev => checked ? [...prev, id] : prev.filter(x => x !== id))}
          onSelectAll={checked => setSelected(checked ? items.map(i => i.id) : [])}
          emptyMessage="No stock items found. Add devices in the Devices / Trackers section." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination
            meta={meta}
            onPageChange={p => { const np = { ...params, page: p }; setParams(np); fetchItems(np); }}
            onPerPageChange={(perPage) => { const np = { ...params, per_page: perPage, page: 1 }; setParams(np); fetchItems(np); }}
            allowAll
          />
        </div>
      </div>

      {/* Edit Modal — status/SIM/batch only */}
      <Modal isOpen={showEdit} onClose={() => { setShowEdit(false); setEditing(null); }}
        title="Edit Stock Item"
        footer={
          <>
            <button onClick={() => { setShowEdit(false); setEditing(null); }} className="btn-secondary">Cancel</button>
            <button form="store-edit-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save Changes'}</button>
          </>
        }>
        {editing && (
          <form id="store-edit-form" onSubmit={handleSave} className="space-y-4">
            <div className="rounded-lg p-3 text-sm" style={{ background: '#f8f9ff', border: '1px solid #e0e4f8' }}>
              <p className="font-semibold text-gray-700 mb-1">Device: <span className="font-mono">{editing.serial_number}</span></p>
              <p className="text-gray-500">Type: {editing.device_type} · Batch: {editing.batch_number}</p>
            </div>
            <Select label="Status" value={editForm.status} onChange={e => setEditForm(f => ({ ...f, status: e.target.value }))}>
              {STORE_STATUSES.map(s => <option key={s} value={s}>{s}</option>)}
            </Select>
            <Input label="Batch Number" value={editForm.batch_number} onChange={e => setEditForm(f => ({ ...f, batch_number: e.target.value }))} />
            <Input label="SIM Number" value={editForm.sim_number} onChange={e => setEditForm(f => ({ ...f, sim_number: e.target.value }))} placeholder="SIM card number" />
            <Input label="SIM Operator" value={editForm.sim_operator} onChange={e => setEditForm(f => ({ ...f, sim_operator: e.target.value }))} placeholder="e.g. Africell" />
          </form>
        )}
      </Modal>

      {/* Bulk Status Modal */}
      <Modal isOpen={showBulkStatus} onClose={() => setShowBulkStatus(false)} title="Change Status"
        footer={
          <>
            <button onClick={() => setShowBulkStatus(false)} className="btn-secondary">Cancel</button>
            <button onClick={handleBulkStatus} disabled={!bulkStatusVal || bulkLoading} className="btn-primary">
              {bulkLoading ? 'Updating…' : 'Apply'}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">Change status for <strong>{selected.length}</strong> selected item(s):</p>
          <Select label="New Status" value={bulkStatusVal} onChange={e => setBulkStatusVal(e.target.value)}>
            <option value="">Select status…</option>
            {STORE_STATUSES.map(s => <option key={s} value={s}>{s}</option>)}
          </Select>
        </div>
      </Modal>

      {/* Bulk Delete Confirm */}
      <ConfirmDialog isOpen={showBulkDelete} onClose={() => setShowBulkDelete(false)}
        onConfirm={handleBulkDelete} title="Delete Selected Items" danger
        loading={bulkDeleting}
        message={`Delete ${selected.length} selected stock item(s) and their linked devices? This cannot be undone.`} />

      {/* Single Delete Confirm */}
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Stock Item" danger message={`Delete "${deleting?.serial_number}"? This cannot be undone.`} />
    </div>
  );
}
