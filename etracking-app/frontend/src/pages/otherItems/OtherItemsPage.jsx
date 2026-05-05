import React, { useState, useEffect, useCallback } from 'react';
import api from '../../services/api';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { Input, Select } from '../../components/common/FormField';

export default function OtherItemsPage() {
  const { notify } = useNotification();
  const [items, setItems]     = useState([]);
  const [meta, setMeta]       = useState({});
  const [loading, setLoading] = useState(false);
  const [params, setParams]   = useState({ page: 1, per_page: 25 });
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [form, setForm] = useState({ item_name: '', item_type: '', quantity: 1, status: 'RECEIVED', notes: '' });

  const fetch = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const { data } = await api.get('/other-items', { params: p });
      setItems(data.data || []); setMeta(data.meta || {});
    } catch { } finally { setLoading(false); }
  }, [params]);

  useEffect(() => { fetch(); }, []);

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await api.put(`/other-items/${editing.id}`, form);
      else         await api.post('/other-items', form);
      notify.success(`Item ${editing ? 'updated' : 'created'}`);
      setShowForm(false); setEditing(null); fetch();
    } catch (e) { notify.error(e.message); }
    finally     { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await api.delete(`/other-items/${deleting.id}`);
      notify.success('Item deleted'); setDeleting(null); fetch();
    } catch (e) { notify.error(e.message); }
  };

  const openEdit = (row) => { setEditing(row); setForm({ ...row }); setShowForm(true); };

  const columns = [
    { header: 'Item Name', key: 'item_name' },
    { header: 'Type',      key: 'item_type', render: v => v || '—' },
    { header: 'Quantity',  key: 'quantity' },
    { header: 'Status',    key: 'status', render: v => <StatusBadge status={v} /> },
    { header: 'Notes',     key: 'notes', render: v => v ? <span className="text-xs text-gray-400 truncate max-w-xs block">{v}</span> : '—' },
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
      <PageHeader title="Other Items" subtitle="Manage non-device inventory items"
        actions={<button onClick={() => { setEditing(null); setForm({ item_name: '', item_type: '', quantity: 1, status: 'RECEIVED', notes: '' }); setShowForm(true); }} className="btn-primary">+ Add Item</button>} />

      <div className="card">
        <DataTable columns={columns} data={items} loading={loading} emptyMessage="No items found." />
        <Pagination meta={meta} onPageChange={p => { const np = { ...params, page: p }; setParams(np); fetch(np); }} />
      </div>

      <Modal isOpen={showForm} onClose={() => { setShowForm(false); setEditing(null); }}
        title={editing ? 'Edit Item' : 'Add Item'}
        footer={
          <>
            <button onClick={() => { setShowForm(false); setEditing(null); }} className="btn-secondary">Cancel</button>
            <button form="item-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button>
          </>
        }>
        <form id="item-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Item Name" required value={form.item_name || ''} onChange={e => setForm(f => ({ ...f, item_name: e.target.value }))} />
          <Input label="Item Type" value={form.item_type || ''} onChange={e => setForm(f => ({ ...f, item_type: e.target.value }))} />
          <Input label="Quantity" type="number" min="1" value={form.quantity || 1} onChange={e => setForm(f => ({ ...f, quantity: e.target.value }))} />
          <Select label="Status" value={form.status || 'RECEIVED'} onChange={e => setForm(f => ({ ...f, status: e.target.value }))}>
            <option value="RECEIVED">RECEIVED</option>
            <option value="ALLOCATED">ALLOCATED</option>
            <option value="CONSUMED">CONSUMED</option>
            <option value="RETURNED">RETURNED</option>
          </Select>
          <div>
            <label className="label">Notes</label>
            <textarea className="input" rows={2} value={form.notes || ''} onChange={e => setForm(f => ({ ...f, notes: e.target.value }))} />
          </div>
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Item" danger message={`Delete "${deleting?.item_name}"?`} />
    </div>
  );
}
