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
  FIXED: '#9333ea', LOST: '#6b7280', UNCONFIGURED: '#9ca3af', CONFIGURED: '#0ea5e9',
};

export default function StoresPage() {
  const { notify }        = useNotification();
  const [items, setItems] = useState([]);
  const [stats, setStats] = useState({});
  const [meta, setMeta]   = useState({});
  const [loading, setLoading]   = useState(false);
  const [params, setParams]     = useState({ page: 1, per_page: 25 });
  const [statusFilter, setStatusFilter] = useState('');
  const [showForm, setShowForm]   = useState(false);
  const [editing, setEditing]     = useState(null);
  const [deleting, setDeleting]   = useState(null);
  const [saving, setSaving]       = useState(false);
  const [selected, setSelected]   = useState([]);
  const [showBulkStatus, setShowBulkStatus] = useState(false);
  const [bulkStatusVal, setBulkStatusVal]   = useState('');
  const [bulkLoading, setBulkLoading]       = useState(false);

  const [form, setForm] = useState({
    serial_number: '', device_type: '', batch_number: '',
    status: 'UNCONFIGURED', date_received: '', sim_number: '', sim_operator: '',
  });

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

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await api.put(`/stores/${editing.id}`, form);
      else         await api.post('/stores', form);
      notify.success(`Stock item ${editing ? 'updated' : 'created'}`);
      setShowForm(false); setEditing(null); fetchItems(); fetchStats();
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

  const openEdit = (row) => {
    setEditing(row);
    setForm({
      serial_number: row.serial_number || '', device_type: row.device_type || '',
      batch_number: row.batch_number || '', status: row.status || 'UNCONFIGURED',
      date_received: row.date_received || '', sim_number: row.sim_number || '',
      sim_operator: row.sim_operator || '',
    });
    setShowForm(true);
  };

  const columns = [
    { header: 'Serial Number', key: 'serial_number', render: v => <span className="font-mono font-semibold">{v}</span> },
    { header: 'Device Type',   key: 'device_type',   render: v => v || '—' },
    { header: 'Batch',         key: 'batch_number',  render: v => v || '—' },
    { header: 'SIM Number',    key: 'sim_number',    render: v => v || '—' },
    { header: 'Status',        key: 'status',        render: v => <StatusBadge status={v} /> },
    { header: 'Date Received', key: 'date_received', render: v => v ? new Date(v).toLocaleDateString() : '—' },
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

  const total = Object.values(stats).reduce((a, b) => a + (b || 0), 0);

  return (
    <div>
      <PageHeader title="Inventory / Store" subtitle="Device stock management"
        actions={
          <button onClick={() => { setEditing(null); setForm({ serial_number: '', device_type: '', batch_number: '', status: 'UNCONFIGURED', date_received: '', sim_number: '', sim_operator: '' }); setShowForm(true); }}
            className="btn-primary">+ Add Stock</button>
        } />

      {/* Stats Row */}
      <div className="flex flex-wrap gap-3 mb-5">
        <div className="card-sm flex items-center gap-2 min-w-[80px]">
          <p className="text-xl font-bold" style={{ color: '#1E2D7A' }}>{total}</p>
          <p className="text-xs text-gray-500">Total</p>
        </div>
        {STORE_STATUSES.map(s => (
          <button key={s} onClick={() => handleStatusTab(s)}
            className={`card-sm flex items-center gap-2 min-w-[90px] transition-all hover:shadow-md ${statusFilter === s ? 'ring-2' : ''}`}
            style={{ ringColor: '#1E2D7A', borderColor: statusFilter === s ? '#1E2D7A' : undefined }}>
            <span className="w-2 h-2 rounded-full flex-shrink-0" style={{ background: STATUS_DOT[s] }} />
            <div className="text-left">
              <p className="text-xs text-gray-500">{s}</p>
              <p className="text-lg font-bold text-gray-900">{stats[s] || 0}</p>
            </div>
          </button>
        ))}
      </div>

      {/* Search & bulk */}
      <div className="flex flex-wrap gap-3 mb-4">
        <SearchBar onSearch={s => { const p = { ...params, search: s, page: 1 }; setParams(p); fetchItems(p); }} className="w-64" />
        {selected.length > 0 && (
          <button onClick={() => setShowBulkStatus(true)} className="btn-secondary btn-sm">
            Change Status ({selected.length})
          </button>
        )}
      </div>

      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={items} loading={loading}
          selectable selected={selected}
          onSelect={(id, checked) => setSelected(prev => checked ? [...prev, id] : prev.filter(x => x !== id))}
          onSelectAll={checked => setSelected(checked ? items.map(i => i.id) : [])}
          emptyMessage="No stock items found." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination meta={meta} onPageChange={p => { const np = { ...params, page: p }; setParams(np); fetchItems(np); }} />
        </div>
      </div>

      {/* Form Modal */}
      <Modal isOpen={showForm} onClose={() => { setShowForm(false); setEditing(null); }}
        title={editing ? 'Edit Stock Item' : 'Add Stock Item'}
        footer={
          <>
            <button onClick={() => { setShowForm(false); setEditing(null); }} className="btn-secondary">Cancel</button>
            <button form="store-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button>
          </>
        }>
        <form id="store-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Serial Number" required value={form.serial_number} onChange={e => setForm(f => ({ ...f, serial_number: e.target.value }))} />
          <Input label="Device Type" value={form.device_type} onChange={e => setForm(f => ({ ...f, device_type: e.target.value }))} />
          <Input label="Batch Number" value={form.batch_number} onChange={e => setForm(f => ({ ...f, batch_number: e.target.value }))} />
          <Input label="Date Received" type="date" value={form.date_received} onChange={e => setForm(f => ({ ...f, date_received: e.target.value }))} />
          <Input label="SIM Number" value={form.sim_number} onChange={e => setForm(f => ({ ...f, sim_number: e.target.value }))} />
          <Input label="SIM Operator" value={form.sim_operator} onChange={e => setForm(f => ({ ...f, sim_operator: e.target.value }))} />
          <Select label="Status" value={form.status} onChange={e => setForm(f => ({ ...f, status: e.target.value }))}>
            {STORE_STATUSES.map(s => <option key={s} value={s}>{s}</option>)}
          </Select>
        </form>
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
            <option value="">Select…</option>
            {STORE_STATUSES.map(s => <option key={s} value={s}>{s}</option>)}
          </Select>
        </div>
      </Modal>

      {/* Delete Confirm */}
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Stock Item" danger message={`Delete "${deleting?.serial_number}"? This cannot be undone.`} />
    </div>
  );
}
