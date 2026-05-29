import React, { useState, useEffect, useCallback } from 'react';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { Input, Select } from '../../components/common/FormField';

const EMPTY = { name: '', status: 'Active' };

export default function CompaniesPage() {
  const { notify } = useNotification();
  const [rows, setRows]         = useState([]);
  const [meta, setMeta]         = useState({});
  const [loading, setLoading]   = useState(false);
  const [params, setParams]     = useState({ page: 1, per_page: 25 });
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [form, setForm]         = useState(EMPTY);

  const load = useCallback(async (p = params) => {
    setLoading(true);
    try { const res = await configService.companies.list(p); setRows(res.data || []); setMeta(res.meta || {}); }
    finally { setLoading(false); }
  }, [params]);

  useEffect(() => { load(); }, [load]);

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const openNew  = () => { setEditing(null); setForm(EMPTY); setShowForm(true); };
  const openEdit = (r) => { setEditing(r); setForm({ name: r.name, status: r.status || 'Active' }); setShowForm(true); };

  const handleSave = async (e) => {
    e.preventDefault();
    if (!form.name.trim()) { notify.error('Name is required'); return; }
    setSaving(true);
    try {
      if (editing) await configService.companies.update(editing.id, form);
      else         await configService.companies.create(form);
      notify.success(`Company ${editing ? 'updated' : 'created'}`);
      setShowForm(false); load();
    } catch (err) { notify.error(err.message); } finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await configService.companies.delete(deleting.id);
      notify.success('Company deleted'); setDeleting(null); load();
    } catch (err) { notify.error(err.message); }
  };

  const columns = [
    { header: 'Company Name', key: 'name' },
    { header: 'Status', key: 'status', render: v => (
      <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${v === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>{v || 'Active'}</span>
    )},
    { header: 'Actions', key: 'id', render: (_, r) => (
      <div className="flex gap-1">
        <button onClick={() => openEdit(r)} className="btn-secondary btn-sm">Edit</button>
        <button onClick={() => setDeleting(r)} className="btn-danger btn-sm">Delete</button>
      </div>
    )},
  ];

  return (
    <div>
      <PageHeader title="Companies" subtitle="Manage companies for device assignment"
        breadcrumbs={[{ label: 'Configuration' }, { label: 'Companies' }]}
        actions={<button onClick={openNew} className="btn-primary">+ Add Company</button>} />
      <div className="card">
        <DataTable columns={columns} data={rows} loading={loading} emptyMessage="No companies configured." />
        <Pagination
          meta={meta}
          onPageChange={p => { const np = { ...params, page: p }; setParams(np); load(np); }}
          onPerPageChange={(perPage) => { const np = { ...params, per_page: perPage, page: 1 }; setParams(np); load(np); }}
          allowAll
        />
      </div>

      <Modal isOpen={showForm} onClose={() => setShowForm(false)}
        title={editing ? 'Edit Company' : 'Add Company'}
        footer={
          <>
            <button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button>
            <button form="company-form" type="submit" disabled={saving} className="btn-primary">
              {saving ? 'Saving…' : 'Save Company'}
            </button>
          </>
        }>
        <form id="company-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Company Name" required value={form.name}
            onChange={e => set('name', e.target.value)} placeholder="e.g. Banjul Shipping Co." />
          <Select label="Status" value={form.status} onChange={e => set('status', e.target.value)}>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </Select>
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Company" danger
        message={`Delete company "${deleting?.name}"? Devices assigned to this company will lose their company link.`} />
    </div>
  );
}
