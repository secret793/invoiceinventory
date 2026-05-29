import React, { useState, useCallback, useEffect } from 'react';
import api from '../../services/api';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { Input, Select } from '../../components/common/FormField';
import { allocationService } from '../../services/allocationService';

export default function ReceiptsPage() {
  const { notify } = useNotification();
  const [receipts, setReceipts] = useState([]);
  const [meta, setMeta]         = useState({});
  const [loading, setLoading]   = useState(false);
  const [params, setParams]     = useState({ page: 1, per_page: 25 });
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [aps, setAps]           = useState([]);
  const [form, setForm] = useState({ receipt_number: '', allocation_point_id: '', date: '', agent_name: '', sad_number: '' });

  const fetch = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const { data } = await api.get('/receipts', { params: p });
      setReceipts(data.data || []); setMeta(data.meta || {});
    } catch { } finally { setLoading(false); }
  }, [params]);

  useEffect(() => { fetch(); allocationService.list().then(setAps).catch(() => {}); }, []);

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await api.put(`/receipts/${editing.id}`, form);
      else         await api.post('/receipts', form);
      notify.success('Receipt saved'); setShowForm(false); setEditing(null); fetch();
    } catch (e) { notify.error(e.message); }
    finally     { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await api.delete(`/receipts/${deleting.id}`);
      notify.success('Receipt deleted'); setDeleting(null); fetch();
    } catch (e) { notify.error(e.message); }
  };

  const columns = [
    { header: 'Receipt #',    key: 'receipt_number', render: v => <span className="font-mono font-medium">{v || '—'}</span> },
    { header: 'SAD/BOE',      key: 'sad_number' },
    { header: 'Station',      key: 'station_name', render: v => v || '—' },
    { header: 'Agent',        key: 'agent_name', render: v => v || '—' },
    { header: 'Date',         key: 'date', render: v => v ? new Date(v).toLocaleDateString() : '—' },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <div className="flex gap-1">
          <button onClick={() => { setEditing(row); setForm({ ...row }); setShowForm(true); }} className="btn-secondary btn-sm">Edit</button>
          <button onClick={() => setDeleting(row)} className="btn-danger btn-sm">Delete</button>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader title="Receipts" subtitle="Manage customs / transaction receipts"
        actions={<button onClick={() => { setEditing(null); setForm({ receipt_number: '', allocation_point_id: '', date: new Date().toISOString().slice(0,10), agent_name: '', sad_number: '' }); setShowForm(true); }} className="btn-primary">+ New Receipt</button>} />

      <div className="card">
        <DataTable columns={columns} data={receipts} loading={loading} emptyMessage="No receipts found." />
        <Pagination
          meta={meta}
          onPageChange={p => { const np = { ...params, page: p }; setParams(np); fetch(np); }}
          onPerPageChange={(perPage) => { const np = { ...params, per_page: perPage, page: 1 }; setParams(np); fetch(np); }}
          allowAll
        />
      </div>

      <Modal isOpen={showForm} onClose={() => { setShowForm(false); setEditing(null); }}
        title={editing ? 'Edit Receipt' : 'New Receipt'}
        footer={
          <>
            <button onClick={() => { setShowForm(false); setEditing(null); }} className="btn-secondary">Cancel</button>
            <button form="receipt-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button>
          </>
        }>
        <form id="receipt-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Receipt Number" value={form.receipt_number || ''} onChange={e => setForm(f => ({ ...f, receipt_number: e.target.value }))} />
          <Input label="SAD/BOE Number" value={form.sad_number || ''} onChange={e => setForm(f => ({ ...f, sad_number: e.target.value }))} />
          <Select label="Station" value={form.allocation_point_id || ''} onChange={e => setForm(f => ({ ...f, allocation_point_id: e.target.value }))}>
            <option value="">Select station…</option>
            {aps.map(ap => <option key={ap.id} value={ap.id}>{ap.name}</option>)}
          </Select>
          <Input label="Agent Name" value={form.agent_name || ''} onChange={e => setForm(f => ({ ...f, agent_name: e.target.value }))} />
          <Input label="Date" type="date" value={form.date || ''} onChange={e => setForm(f => ({ ...f, date: e.target.value }))} />
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Receipt" danger message={`Delete receipt "${deleting?.receipt_number}"?`} />
    </div>
  );
}
