import React, { useState, useEffect, useCallback } from 'react';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import SearchBar from '../../components/common/SearchBar';
import { Input, Select } from '../../components/common/FormField';

export default function UsersPage() {
  const { notify } = useNotification();
  const [users, setUsers]       = useState([]);
  const [meta, setMeta]         = useState({});
  const [loading, setLoading]   = useState(false);
  const [params, setParams]     = useState({ page: 1, per_page: 25 });
  const [roles, setRoles]       = useState([]);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [form, setForm] = useState({ name: '', email: '', password: '', role: 'user', roles: [], username: '' });

  const load = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const res = await configService.users.list(p);
      setUsers(res.data || []); setMeta(res.meta || {});
    } catch { } finally { setLoading(false); }
  }, [params]);

  useEffect(() => {
    load();
    configService.roles.list().then(setRoles).catch(() => {});
  }, []);

  const openEdit = (u) => { setEditing(u); setForm({ name: u.name, email: u.email, password: '', role: u.role || '', roles: u.roles || [], username: u.username || '' }); setShowForm(true); };
  const openNew  = ()  => { setEditing(null); setForm({ name: '', email: '', password: '', role: 'user', roles: [], username: '' }); setShowForm(true); };

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await configService.users.update(editing.id, form);
      else         await configService.users.create(form);
      notify.success(`User ${editing ? 'updated' : 'created'}`); setShowForm(false); load();
    } catch (e) { notify.error(e.message); } finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try { await configService.users.delete(deleting.id); notify.success('User deleted'); setDeleting(null); load(); }
    catch (e) { notify.error(e.message); }
  };

  const toggleRole = (roleName) => {
    setForm(f => ({
      ...f,
      roles: f.roles.includes(roleName) ? f.roles.filter(r => r !== roleName) : [...f.roles, roleName]
    }));
  };

  const columns = [
    { header: 'Name',  key: 'name' },
    { header: 'Email', key: 'email' },
    { header: 'Roles', key: 'roles', render: v => (v || []).join(', ') || '—' },
    { header: 'Actions', key: 'id', render: (_, r) => (
      <div className="flex gap-1">
        <button onClick={() => openEdit(r)} className="btn-secondary btn-sm">Edit</button>
        <button onClick={() => setDeleting(r)} className="btn-danger btn-sm">Delete</button>
      </div>
    )},
  ];

  return (
    <div>
      <PageHeader title="Users" subtitle="System user management"
        breadcrumbs={[{ label: 'Configuration' }, { label: 'Users' }]}
        actions={<button onClick={openNew} className="btn-primary">+ Add User</button>} />

      <div className="mb-4">
        <SearchBar onSearch={s => { const p = { ...params, search: s, page: 1 }; setParams(p); load(p); }} className="w-64" />
      </div>

      <div className="card">
        <DataTable columns={columns} data={users} loading={loading} emptyMessage="No users found." />
        <Pagination meta={meta} onPageChange={p => { const np = { ...params, page: p }; setParams(np); load(np); }} />
      </div>

      <Modal isOpen={showForm} onClose={() => setShowForm(false)} title={editing ? 'Edit User' : 'Add User'} size="lg"
        footer={<><button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button><button form="user-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button></>}>
        <form id="user-form" onSubmit={handleSave} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <Input label="Name" required value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
            <Input label="Username" value={form.username} onChange={e => setForm(f => ({ ...f, username: e.target.value }))} />
            <Input label="Email" type="email" required value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} />
            <Input label={editing ? 'Password (leave blank to keep)' : 'Password *'} type="password"
              required={!editing} value={form.password} onChange={e => setForm(f => ({ ...f, password: e.target.value }))} />
          </div>
          <div>
            <label className="label">Assign Roles</label>
            <div className="flex flex-wrap gap-2 mt-1">
              {roles.map(r => (
                <label key={r.id} className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg border cursor-pointer text-sm transition-colors
                  ${form.roles.includes(r.name) ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-600 hover:border-blue-400'}`}>
                  <input type="checkbox" className="sr-only" checked={form.roles.includes(r.name)} onChange={() => toggleRole(r.name)} />
                  {r.name}
                </label>
              ))}
            </div>
          </div>
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete User" danger message={`Delete user "${deleting?.name}"? This cannot be undone.`} />
    </div>
  );
}
