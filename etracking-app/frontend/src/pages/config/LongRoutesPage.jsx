import React, { useState, useEffect, useCallback } from 'react';
import { configService } from '../../services/configService';
import { allocationService } from '../../services/allocationService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { Input, Select } from '../../components/common/FormField';

export default function LongRoutesPage() {
  const { notify } = useNotification();
  const [rows, setRows]         = useState([]);
  const [meta, setMeta]         = useState({});
  const [loading, setLoading]   = useState(false);
  const [params, setParams]     = useState({ page: 1, per_page: 25, include_inactive: 1 });
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [allocationPoints, setAllocationPoints] = useState([]);
  const [destinations, setDestinations] = useState([]);
  const [form, setForm] = useState({ name: '', allocation_point_id: '', destination_id: '', is_active: 1, allowed_days: 3, base_usd_amount: 0 });

  const load = useCallback(async (p = params) => {
    setLoading(true);
    try { const res = await configService.longRoutes.list(p); setRows(res.data || []); setMeta(res.meta || {}); } catch { }
    finally { setLoading(false); }
  }, [params]);

  useEffect(() => {
    load();
    allocationService.list().then(setAllocationPoints).catch(() => {});
    configService.destinations.list({ page: 1, per_page: 1000 }).then(res => setDestinations(res.data || [])).catch(() => {});
  }, []);

  const openEdit = (r) => {
    setEditing(r);
    setForm({
      name: r.name,
      allocation_point_id: r.allocation_point_id || '',
      destination_id: r.destination_id || '',
      is_active: Number(r.is_active ?? 1),
      allowed_days: r.allowed_days || 3,
      base_usd_amount: r.base_usd_amount || 0,
    });
    setShowForm(true);
  };
  const openNew  = ()  => { setEditing(null); setForm({ name: '', allocation_point_id: '', destination_id: '', is_active: 1, allowed_days: 3, base_usd_amount: 0 }); setShowForm(true); };

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
    { header: 'Allocation Point', key: 'allocation_point_name', render: v => v || '—' },
    { header: 'Destination', key: 'destination_name', render: v => v || '—' },
    {
      header: 'Status',
      key: 'is_active',
      render: v => Number(v ?? 1) === 1
        ? <span className="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Active</span>
        : <span className="inline-flex items-center rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-700">Inactive</span>
    },
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
        <Pagination
          meta={meta}
          onPageChange={p => { const np = { ...params, page: p }; setParams(np); load(np); }}
          onPerPageChange={(perPage) => { const np = { ...params, per_page: perPage, page: 1 }; setParams(np); load(np); }}
          allowAll
        />
      </div>
      <Modal isOpen={showForm} onClose={() => setShowForm(false)} title={editing ? 'Edit Long Route' : 'Add Long Route'}
        footer={<><button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button><button form="lr-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button></>}>
        <form id="lr-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Name" required value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
          <Select label="Allocation Point" required value={form.allocation_point_id} onChange={e => setForm(f => ({ ...f, allocation_point_id: e.target.value }))}>
            <option value="">Select allocation point…</option>
            {allocationPoints.map(ap => <option key={ap.id} value={ap.id}>{ap.name}</option>)}
          </Select>
          <Select label="Destination" required value={form.destination_id} onChange={e => setForm(f => ({ ...f, destination_id: e.target.value }))}>
            <option value="">Select destination…</option>
            {destinations.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
          </Select>
          <div className="flex items-center gap-2">
            <input
              id="long-route-is-active"
              type="checkbox"
              checked={Number(form.is_active) === 1}
              onChange={e => setForm(f => ({ ...f, is_active: e.target.checked ? 1 : 0 }))}
            />
            <label htmlFor="long-route-is-active" className="text-sm font-medium text-gray-700">Active</label>
          </div>
          <Input label="Allowed Days" type="number" min="1" value={form.allowed_days} onChange={e => setForm(f => ({ ...f, allowed_days: e.target.value }))} />
          <Input label="Base USD Amount" type="number" min="0" step="0.01" value={form.base_usd_amount} onChange={e => setForm(f => ({ ...f, base_usd_amount: e.target.value }))} />
        </form>
      </Modal>
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Long Route" danger message={`Delete long route "${deleting?.name}"?`} />
    </div>
  );
}
