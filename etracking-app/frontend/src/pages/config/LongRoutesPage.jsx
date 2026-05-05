import React, { useState, useEffect, useCallback } from 'react';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { Input } from '../../components/common/FormField';

export default function LongRoutesPage() {
  const { notify } = useNotification();
  const [rows, setRows]         = useState([]);
  const [loading, setLoading]   = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [form, setForm] = useState({ name: '', allowed_days: 3, base_usd_amount: 0 });

  const load = useCallback(async () => {
    setLoading(true);
    try { setRows(await configService.longRoutes.list() || []); } catch { }
    finally { setLoading(false); }
  }, []);

  useEffect(() => { load(); }, []);

  const openEdit = (r) => { setEditing(r); setForm({ name: r.name, allowed_days: r.allowed_days || 3, base_usd_amount: r.base_usd_amount || 0 }); setShowForm(true); };
  const openNew  = ()  => { setEditing(null); setForm({ name: '', allowed_days: 3, base_usd_amount: 0 }); setShowForm(true); };

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await configService.longRoutes.update(editing.id, form);
      else         await configService.longRoutes.create(form);
      notify.success(`Long route ${editing ? 'updated' : 'created'}`); setShowForm(false); load();
    } catch (e) { notify.error(e.message); } finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try { await configService.longRoutes.delete(deleting.id); notify.success('Long route deleted'); setDeleting(null); load(); }
    catch (e) { notify.error(e.message); }
  };

  const columns = [
    { header: 'Name',         key: 'name' },
    { header: 'Allowed Days', key: 'allowed_days' },
    { header: 'Base USD',     key: 'base_usd_amount', render: v => `$${v || 0}` },
    { header: 'Actions', key: 'id', render: (_, r) => (
      <div className="flex gap-1">
        <button onClick={() => openEdit(r)} className="btn-secondary btn-sm">Edit</button>
        <button onClick={() => setDeleting(r)} className="btn-danger btn-sm">Delete</button>
      </div>
    )},
  ];

  return (
    <div>
      <PageHeader title="Long Routes" subtitle="Long distance route configuration"
        breadcrumbs={[{ label: 'Configuration' }, { label: 'Long Routes' }]}
        actions={<button onClick={openNew} className="btn-primary">+ Add Long Route</button>} />
      <div className="card">
        <DataTable columns={columns} data={rows} loading={loading} emptyMessage="No long routes configured." />
      </div>
      <Modal isOpen={showForm} onClose={() => setShowForm(false)} title={editing ? 'Edit Long Route' : 'Add Long Route'}
        footer={<><button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button><button form="lr-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button></>}>
        <form id="lr-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Name" required value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
          <Input label="Allowed Days" type="number" min="1" value={form.allowed_days} onChange={e => setForm(f => ({ ...f, allowed_days: e.target.value }))} />
          <Input label="Base USD Amount" type="number" min="0" step="0.01" value={form.base_usd_amount} onChange={e => setForm(f => ({ ...f, base_usd_amount: e.target.value }))} />
        </form>
      </Modal>
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Long Route" danger message={`Delete long route "${deleting?.name}"?`} />
    </div>
  );
}
