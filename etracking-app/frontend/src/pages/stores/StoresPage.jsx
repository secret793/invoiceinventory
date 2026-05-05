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

export default function StoresPage() {
  const { notify }  = useNotification();
  const [items, setItems] = useState([]);
  const [meta, setMeta]   = useState({});
  const [loading, setLoading] = useState(false);
  const [params, setParams]   = useState({ page: 1, per_page: 25 });
  const [showForm, setShowForm]   = useState(false);
  const [editing, setEditing]     = useState(null);
  const [deleting, setDeleting]   = useState(null);
  const [saving, setSaving]       = useState(false);
  const [form, setForm] = useState({ serial_number: '', device_type: '', batch_number: '', status: 'RECEIVED', date_received: '' });

  const fetchItems = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const { data } = await api.get('/stores', { params: p });
      setItems(data.data || []); setMeta(data.meta || {});
    } catch { } finally { setLoading(false); }
  }, [params]);

  useEffect(() => { fetchItems(); }, []);

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await api.put(`/stores/${editing.id}`, form);
      else         await api.post('/stores', form);
      notify.success(`Stock item ${editing ? 'updated' : 'created'}`);
      setShowForm(false); setEditing(null); fetchItems();
    } catch (e) { notify.error(e.message); }
    finally     { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await api.delete(`/stores/${deleting.id}`);
      notify.success('Item deleted'); setDeleting(null); fetchItems();
    } catch (e) { notify.error(e.message); }
  };

  const openEdit = (row) => {
    setEditing(row); setForm({ ...row }); setShowForm(true);
  };

  const columns = [
    { header: 'Serial Number', key: 'serial_number', render: v => <span className="font-mono font-medium">{v}</span> },
    { header: 'Device Type',   key: 'device_type' },
    { header: 'Batch',         key: 'batch_number', render: v => v || '—' },
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

  return (
    <div>
      <PageHeader title="Inventory / Store" subtitle="Manage stock received from store"
        actions={<button onClick={() => { setEditing(null); setForm({ serial_number: '', device_type: '', status: 'RECEIVED', date_received: '' }); setShowForm(true); }} className="btn-primary">+ Add Stock</button>} />

      <div className="flex gap-3 mb-4">
        <SearchBar onSearch={s => { const p = { ...params, search: s, page: 1 }; setParams(p); fetchItems(p); }} className="w-64" />
        <select className="input w-44" onChange={e => { const p = { ...params, status: e.target.value, page: 1 }; setParams(p); fetchItems(p); }}>
          <option value="">All Statuses</option>
          <option value="RECEIVED">Received</option>
          <option value="CONFIGURED">Configured</option>
          <option value="ALLOCATED">Allocated</option>
          <option value="FAULTY">Faulty</option>
        </select>
      </div>

      <div className="card">
        <DataTable columns={columns} data={items} loading={loading} emptyMessage="No stock items found." />
        <Pagination meta={meta} onPageChange={p => { const np = { ...params, page: p }; setParams(np); fetchItems(np); }} />
      </div>

      <Modal isOpen={showForm} onClose={() => { setShowForm(false); setEditing(null); }}
        title={editing ? 'Edit Stock Item' : 'Add Stock Item'}
        footer={
          <>
            <button onClick={() => { setShowForm(false); setEditing(null); }} className="btn-secondary">Cancel</button>
            <button form="store-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button>
          </>
        }>
        <form id="store-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Serial Number" required value={form.serial_number || ''} onChange={e => setForm(f => ({ ...f, serial_number: e.target.value }))} />
          <Input label="Device Type" value={form.device_type || ''} onChange={e => setForm(f => ({ ...f, device_type: e.target.value }))} />
          <Input label="Batch Number" value={form.batch_number || ''} onChange={e => setForm(f => ({ ...f, batch_number: e.target.value }))} />
          <Input label="Date Received" type="date" value={form.date_received || ''} onChange={e => setForm(f => ({ ...f, date_received: e.target.value }))} />
          <Select label="Status" value={form.status || 'RECEIVED'} onChange={e => setForm(f => ({ ...f, status: e.target.value }))}>
            <option value="RECEIVED">RECEIVED</option>
            <option value="CONFIGURED">CONFIGURED</option>
            <option value="ALLOCATED">ALLOCATED</option>
            <option value="FAULTY">FAULTY</option>
          </Select>
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Stock Item" danger message={`Delete "${deleting?.serial_number}"? This cannot be undone.`} />
    </div>
  );
}
