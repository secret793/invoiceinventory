import React, { useState, useEffect, useCallback } from 'react';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { Input } from '../../components/common/FormField';

export default function DestinationsPage() {
  const { notify } = useNotification();
  const [rows, setRows]         = useState([]);
  const [loading, setLoading]   = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [form, setForm] = useState({ name: '', country: '', description: '' });

  const load = useCallback(async () => {
    setLoading(true);
    try { setRows(await configService.destinations.list() || []); } catch { }
    finally { setLoading(false); }
  }, []);

  useEffect(() => { load(); }, []);

  const openEdit = (r) => { setEditing(r); setForm({ name: r.name, country: r.country || '', description: r.description || '' }); setShowForm(true); };
  const openNew  = ()  => { setEditing(null); setForm({ name: '', country: '', description: '' }); setShowForm(true); };

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await configService.destinations.update(editing.id, form);
      else         await configService.destinations.create(form);
      notify.success(`Destination ${editing ? 'updated' : 'created'}`); setShowForm(false); load();
    } catch (e) { notify.error(e.message); } finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try { await configService.destinations.delete(deleting.id); notify.success('Destination deleted'); setDeleting(null); load(); }
    catch (e) { notify.error(e.message); }
  };

  const columns = [
    { header: 'Name',    key: 'name' },
    { header: 'Country', key: 'country', render: v => v || '—' },
    { header: 'Regime',  key: 'regime_name', render: v => v || '—' },
    { header: 'Actions', key: 'id', render: (_, r) => (
      <div className="flex gap-1">
        <button onClick={() => openEdit(r)} className="btn-secondary btn-sm">Edit</button>
        <button onClick={() => setDeleting(r)} className="btn-danger btn-sm">Delete</button>
      </div>
    )},
  ];

  return (
    <div>
      <PageHeader title="Destinations" subtitle="Transit destination management"
        breadcrumbs={[{ label: 'Configuration' }, { label: 'Destinations' }]}
        actions={<button onClick={openNew} className="btn-primary">+ Add Destination</button>} />
      <div className="card">
        <DataTable columns={columns} data={rows} loading={loading} emptyMessage="No destinations configured." />
      </div>
      <Modal isOpen={showForm} onClose={() => setShowForm(false)} title={editing ? 'Edit Destination' : 'Add Destination'}
        footer={<><button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button><button form="dest-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button></>}>
        <form id="dest-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Name" required value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
          <Input label="Country" value={form.country} onChange={e => setForm(f => ({ ...f, country: e.target.value }))} />
          <Input label="Description" value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} />
        </form>
      </Modal>
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Destination" danger message={`Delete destination "${deleting?.name}"?`} />
    </div>
  );
}
