import React, { useState, useEffect, useCallback } from 'react';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { Input, Select } from '../../components/common/FormField';

export default function RegimesPage() {
  const { notify } = useNotification();
  const [rows, setRows]         = useState([]);
  const [meta, setMeta]         = useState({});
  const [loading, setLoading]   = useState(false);
  const [params, setParams]     = useState({ page: 1, per_page: 25 });
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [form, setForm] = useState({ name: '', description: '', is_active: 1 });

  const load = useCallback(async (p = params) => {
    setLoading(true);
    try { const res = await configService.regimes.list(p); setRows(res.data || []); setMeta(res.meta || {}); } catch { }
    finally { setLoading(false); }
  }, [params]);

  useEffect(() => { load(); }, []);

  const openEdit = (r) => { setEditing(r); setForm({ name: r.name, description: r.description || '', is_active: r.is_active ?? 1 }); setShowForm(true); };
  const openNew  = ()  => { setEditing(null); setForm({ name: '', description: '', is_active: 1 }); setShowForm(true); };

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await configService.regimes.update(editing.id, form);
      else         await configService.regimes.create(form);
      notify.success(`Regime ${editing ? 'updated' : 'created'}`); setShowForm(false); load();
    } catch (e) { notify.error(e.message); } finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try { await configService.regimes.delete(deleting.id); notify.success('Regime deleted'); setDeleting(null); load(); }
    catch (e) { notify.error(e.message); }
  };

  const columns = [
    { header: 'Name',        key: 'name' },
    { header: 'Description', key: 'description', render: v => v || '—' },
    { header: 'Active',      key: 'is_active', render: v => v ? <span className="badge-green">Yes</span> : <span className="badge-gray">No</span> },
    { header: 'Actions', key: 'id', render: (_, r) => (
      <div className="flex gap-1">
        <button onClick={() => openEdit(r)} className="btn-secondary btn-sm">Edit</button>
        <button onClick={() => setDeleting(r)} className="btn-danger btn-sm">Delete</button>
      </div>
    )},
  ];

  return (
    <div>
      <PageHeader title="Regimes" subtitle="Customs regime configuration"
        breadcrumbs={[{ label: 'Configuration' }, { label: 'Regimes' }]}
        actions={<button onClick={openNew} className="btn-primary">+ Add Regime</button>} />
      <div className="card">
        <DataTable columns={columns} data={rows} loading={loading} emptyMessage="No regimes configured." />
        <Pagination
          meta={meta}
          onPageChange={p => { const np = { ...params, page: p }; setParams(np); load(np); }}
          onPerPageChange={(perPage) => { const np = { ...params, per_page: perPage, page: 1 }; setParams(np); load(np); }}
          allowAll
        />
      </div>
      <Modal isOpen={showForm} onClose={() => setShowForm(false)} title={editing ? 'Edit Regime' : 'Add Regime'}
        footer={<><button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button><button form="regime-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button></>}>
        <form id="regime-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Name" required value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
          <Input label="Description" value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} />
          <Select label="Active" value={form.is_active} onChange={e => setForm(f => ({ ...f, is_active: Number(e.target.value) }))}>
            <option value={1}>Yes</option>
            <option value={0}>No</option>
          </Select>
        </form>
      </Modal>
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Regime" danger message={`Delete regime "${deleting?.name}"?`} />
    </div>
  );
}
