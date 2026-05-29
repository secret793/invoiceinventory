import React, { useState, useEffect, useCallback } from 'react';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import api from '../../services/api';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { Input, Select, FormField } from '../../components/common/FormField';

const EMPTY_FORM = {
  name: '', regime_id: '', address: '',
  latitude: '', longitude: '', status: 'Active', is_default_location: false,
};

export default function DestinationsPage() {
  const { notify } = useNotification();
  const [rows, setRows]         = useState([]);
  const [meta, setMeta]         = useState({});
  const [regimes, setRegimes]   = useState([]);
  const [loading, setLoading]   = useState(false);
  const [params, setParams]     = useState({ page: 1, per_page: 25 });
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [form, setForm]         = useState(EMPTY_FORM);

  const load = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const [dests, regs] = await Promise.all([
        configService.destinations.list(p).catch(() => ({ data: [], meta: {} })),
        api.get('/regimes').then(r => r.data.data || []).catch(() => []),
      ]);
      setRows(dests.data || []);
      setMeta(dests.meta || {});
      setRegimes(regs);
    } finally { setLoading(false); }
  }, [params]);

  useEffect(() => { load(); }, []);

  const openEdit = (r) => {
    setEditing(r);
    setForm({
      name: r.name || '', regime_id: r.regime_id || '',
      address: r.address || '', latitude: r.latitude || '', longitude: r.longitude || '',
      status: r.status || 'Active', is_default_location: !!r.is_default_location,
    });
    setShowForm(true);
  };

  const openNew = () => { setEditing(null); setForm(EMPTY_FORM); setShowForm(true); };

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const handleSave = async (e) => {
    e.preventDefault();
    if (!form.name.trim()) { notify.error('Name is required'); return; }
    if (!form.regime_id)   { notify.error('Regime is required'); return; }
    setSaving(true);
    try {
      const payload = { ...form, is_default_location: form.is_default_location ? 1 : 0 };
      if (editing) await configService.destinations.update(editing.id, payload);
      else         await configService.destinations.create(payload);
      notify.success(`Destination ${editing ? 'updated' : 'created'}`);
      setShowForm(false); load();
    } catch (e) { notify.error(e.message); } finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await configService.destinations.delete(deleting.id);
      notify.success('Destination deleted'); setDeleting(null); load();
    } catch (e) { notify.error(e.message); }
  };

  const columns = [
    { header: 'Name',    key: 'name' },
    { header: 'Regime',  key: 'regime_name', render: v => v || '—' },
    { header: 'Address', key: 'address', render: v => v || '—' },
    { header: 'Status',  key: 'status', render: v => (
      <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${v === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>{v || 'Active'}</span>
    )},
    { header: 'Default Loc.', key: 'is_default_location', render: v => v ? '✓' : '—' },
    { header: 'Lat / Lng', key: 'latitude', render: (v, r) => (v || r.longitude) ? `${v || '—'}, ${r.longitude || '—'}` : '—' },
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
        <Pagination
          meta={meta}
          onPageChange={p => { const np = { ...params, page: p }; setParams(np); load(np); }}
          onPerPageChange={(perPage) => { const np = { ...params, per_page: perPage, page: 1 }; setParams(np); load(np); }}
          allowAll
        />
      </div>

      <Modal isOpen={showForm} onClose={() => setShowForm(false)}
        title={editing ? 'Edit Destination' : 'Add Destination'}
        footer={
          <>
            <button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button>
            <button form="dest-form" type="submit" disabled={saving} className="btn-primary">
              {saving ? 'Saving…' : 'Save Destination'}
            </button>
          </>
        }>
        <form id="dest-form" onSubmit={handleSave} className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Input
              label="Name" required
              value={form.name}
              onChange={e => set('name', e.target.value)}
              placeholder="e.g. Banjul Port"
            />

            <Select
              label="Regime" required
              value={form.regime_id}
              onChange={e => set('regime_id', e.target.value)}
            >
              <option value="">Select regime…</option>
              {regimes.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
            </Select>

            <Input
              label="Address"
              value={form.address}
              onChange={e => set('address', e.target.value)}
              placeholder="Street address (optional)"
            />

            <Select
              label="Status"
              value={form.status}
              onChange={e => set('status', e.target.value)}
            >
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </Select>

            <Input
              label="Latitude"
              value={form.latitude}
              onChange={e => set('latitude', e.target.value)}
              placeholder="e.g. 13.4531"
            />

            <Input
              label="Longitude"
              value={form.longitude}
              onChange={e => set('longitude', e.target.value)}
              placeholder="e.g. -16.5775"
            />
          </div>

          <FormField label="Default Location">
            <div className="flex items-center gap-3 p-3 rounded-lg" style={{ background: '#f8f9ff', border: '1px solid #e0e4f8' }}>
              <label className="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" className="sr-only peer"
                  checked={!!form.is_default_location}
                  onChange={e => set('is_default_location', e.target.checked)} />
                <div className="w-10 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-700" />
              </label>
              <span className="text-sm text-gray-700">
                {form.is_default_location
                  ? <span className="font-medium text-blue-700">Set as default location</span>
                  : <span className="text-gray-500">Not a default location</span>}
              </span>
            </div>
          </FormField>

          {!editing && (
            <p className="text-xs text-gray-500 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
              Saving will automatically generate <strong>11 permissions</strong> for this destination (view, manage devices, routes, allocations, etc.) that can be assigned to roles.
            </p>
          )}
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Destination" danger message={`Delete destination "${deleting?.name}"? This will not remove any associated permissions.`} />
    </div>
  );
}
