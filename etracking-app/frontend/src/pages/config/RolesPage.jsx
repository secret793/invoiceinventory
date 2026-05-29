import React, { useState, useEffect, useCallback } from 'react';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { Input } from '../../components/common/FormField';

export default function RolesPage() {
  const { notify } = useNotification();
  const [roles, setRoles]       = useState([]);
  const [meta, setMeta]         = useState({});
  const [perms, setPerms]       = useState([]);
  const [loading, setLoading]   = useState(false);
  const [params, setParams]     = useState({ page: 1, per_page: 25 });
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [form, setForm] = useState({ name: '', permissions: [] });

  const load = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const [r, permissions] = await Promise.all([configService.roles.list(p), configService.permissions.list()]);
      setRoles(r.data || []); setMeta(r.meta || {}); setPerms(permissions || []);
    } catch { } finally { setLoading(false); }
  }, [params]);

  useEffect(() => { load(); }, []);

  const openEdit = (r) => { setEditing(r); setForm({ name: r.name, permissions: r.permissions || [] }); setShowForm(true); };
  const openNew  = ()  => { setEditing(null); setForm({ name: '', permissions: [] }); setShowForm(true); };

  const togglePerm = (perm) => setForm(f => ({
    ...f,
    permissions: f.permissions.includes(perm) ? f.permissions.filter(p => p !== perm) : [...f.permissions, perm]
  }));

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await configService.roles.update(editing.id, form);
      else         await configService.roles.create(form);
      notify.success(`Role ${editing ? 'updated' : 'created'}`); setShowForm(false); load();
    } catch (e) { notify.error(e.message); } finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try { await configService.roles.delete(deleting.id); notify.success('Role deleted'); setDeleting(null); load(); }
    catch (e) { notify.error(e.message); }
  };

  // Group permissions by prefix
  const groupedPerms = perms.reduce((acc, p) => {
    const prefix = p.name.includes('allocationpoint') ? 'Allocation Points' :
                   p.name.includes('destination')     ? 'Destinations'      : 'General';
    if (!acc[prefix]) acc[prefix] = [];
    acc[prefix].push(p);
    return acc;
  }, {});

  const columns = [
    { header: 'Role Name',   key: 'name' },
    { header: 'Permissions', key: 'permissions', render: v => `${(v || []).length} permission(s)` },
    { header: 'Actions', key: 'id', render: (_, r) => (
      <div className="flex gap-1">
        <button onClick={() => openEdit(r)} className="btn-secondary btn-sm">Edit Permissions</button>
        <button onClick={() => setDeleting(r)} className="btn-danger btn-sm">Delete</button>
      </div>
    )},
  ];

  return (
    <div>
      <PageHeader title="Roles & Permissions" subtitle="Manage user roles and their access permissions"
        breadcrumbs={[{ label: 'Configuration' }, { label: 'Roles' }]}
        actions={<button onClick={openNew} className="btn-primary">+ Add Role</button>} />
      <div className="card">
        <DataTable columns={columns} data={roles} loading={loading} emptyMessage="No roles defined." />
        <Pagination
          meta={meta}
          onPageChange={p => { const np = { ...params, page: p }; setParams(np); load(np); }}
          onPerPageChange={(perPage) => { const np = { ...params, per_page: perPage, page: 1 }; setParams(np); load(np); }}
          allowAll
        />
      </div>

      <Modal isOpen={showForm} onClose={() => setShowForm(false)} title={editing ? `Edit Role: ${editing.name}` : 'Add Role'} size="xl"
        footer={<><button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button><button form="role-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button></>}>
        <form id="role-form" onSubmit={handleSave} className="space-y-5">
          <Input label="Role Name" required value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
          <div>
            <label className="label">Permissions</label>
            {Object.entries(groupedPerms).map(([group, permsInGroup]) => (
              <div key={group} className="mb-4">
                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{group}</p>
                <div className="flex flex-wrap gap-2">
                  {permsInGroup.map(p => (
                    <label key={p.id} className={`flex items-center gap-1.5 px-2.5 py-1 rounded-lg border cursor-pointer text-xs transition-colors
                      ${form.permissions.includes(p.name) ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-600 hover:border-blue-400'}`}>
                      <input type="checkbox" className="sr-only" checked={form.permissions.includes(p.name)} onChange={() => togglePerm(p.name)} />
                      {p.name}
                    </label>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Role" danger message={`Delete role "${deleting?.name}"?`} />
    </div>
  );
}
