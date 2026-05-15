import React, { useState, useEffect, useCallback } from 'react';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import SearchBar from '../../components/common/SearchBar';
import { Input } from '../../components/common/FormField';

const PERM_CATEGORIES = [
  { key: 'allocation_points', label: 'Allocation Points', color: '#1E2D7A', bg: '#eef1fb',
    match: n => n.includes('allocationpoint') || (n.includes('data_entry') && !n.includes('destination')) },
  { key: 'destinations',      label: 'Destinations',      color: '#085E37', bg: '#dcfce7',
    match: n => n.includes('destination') },
  { key: 'other',             label: 'General',           color: '#6b21a8', bg: '#f3e8ff',
    match: () => true },
];

function catFor(name) {
  for (const c of PERM_CATEGORIES) { if (c.match(name)) return c.key; }
  return 'other';
}

export default function UsersPage() {
  const { notify } = useNotification();
  const [users, setUsers]       = useState([]);
  const [meta, setMeta]         = useState({});
  const [loading, setLoading]   = useState(false);
  const [params, setParams]     = useState({ page: 1, per_page: 25 });
  const [roles, setRoles]       = useState([]);
  const [allPerms, setAllPerms] = useState([]);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [permSearch, setPermSearch] = useState('');

  const BLANK = { name: '', email: '', password: '', role: 'user', roles: [], username: '', permissions: [] };
  const [form, setForm] = useState(BLANK);

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
    configService.permissions.list().then(setAllPerms).catch(() => {});
  }, []);

  const openEdit = (u) => {
    setEditing(u);
    // Load direct permissions only (not inherited via roles) — fall back to all permissions if not split
    const directPerms = u.direct_permissions || u.permissions || [];
    setForm({ name: u.name, email: u.email, password: '', role: u.role || '', roles: u.roles || [], username: u.username || '', permissions: directPerms });
    setPermSearch('');
    setShowForm(true);
  };
  const openNew = () => { setEditing(null); setForm(BLANK); setPermSearch(''); setShowForm(true); };

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await configService.users.update(editing.id, form);
      else         await configService.users.create(form);
      notify.success(`User ${editing ? 'updated' : 'created'}`); setShowForm(false); load();
    } catch (err) { notify.error(err.response?.data?.message || err.message); }
    finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try { await configService.users.delete(deleting.id); notify.success('User deleted'); setDeleting(null); load(); }
    catch (err) { notify.error(err.message); }
  };

  const toggleRole = (roleName) =>
    setForm(f => ({ ...f, roles: f.roles.includes(roleName) ? f.roles.filter(r => r !== roleName) : [...f.roles, roleName] }));

  const togglePerm = (permName) =>
    setForm(f => ({ ...f, permissions: f.permissions.includes(permName) ? f.permissions.filter(p => p !== permName) : [...f.permissions, permName] }));

  // Group permissions by category, filtered by search
  const filteredPerms = permSearch
    ? allPerms.filter(p => p.name.toLowerCase().includes(permSearch.toLowerCase()))
    : allPerms;

  const groupedPerms = PERM_CATEGORIES.reduce((acc, c) => { acc[c.key] = filteredPerms.filter(p => catFor(p.name) === c.key); return acc; }, {});

  const columns = [
    { header: 'Name',  key: 'name' },
    { header: 'Email', key: 'email' },
    { header: 'Role(s)', key: 'roles', render: v => (v || []).join(', ') || '—' },
    { header: 'Direct Perms', key: 'permissions', render: v => {
      const cnt = (v || []).length;
      return cnt > 0
        ? <span className="px-2 py-0.5 rounded-full text-xs font-semibold" style={{ background: '#eef1fb', color: '#1E2D7A' }}>{cnt}</span>
        : <span className="text-gray-400 text-xs">none</span>;
    }},
    { header: 'Actions', key: 'id', render: (_, r) => (
      <div className="flex gap-1">
        <button onClick={() => openEdit(r)} className="btn-secondary btn-sm">Edit</button>
        <button onClick={() => setDeleting(r)} className="btn-danger btn-sm">Delete</button>
      </div>
    )},
  ];

  return (
    <div>
      <PageHeader title="Users" subtitle="System user management — assign roles and individual permissions"
        breadcrumbs={[{ label: 'Configuration' }, { label: 'Users' }]}
        actions={<button onClick={openNew} className="btn-primary">+ Add User</button>} />

      <div className="mb-4">
        <SearchBar onSearch={s => { const p = { ...params, search: s, page: 1 }; setParams(p); load(p); }} className="w-64" />
      </div>

      <div className="card">
        <DataTable columns={columns} data={users} loading={loading} emptyMessage="No users found." />
        <Pagination meta={meta} onPageChange={p => { const np = { ...params, page: p }; setParams(np); load(np); }} />
      </div>

      <Modal isOpen={showForm} onClose={() => setShowForm(false)}
        title={editing ? `Edit User: ${editing.name}` : 'Add User'} size="xl"
        footer={
          <>
            <button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button>
            <button form="user-form" type="submit" disabled={saving} className="btn-primary">
              {saving ? 'Saving…' : 'Save'}
            </button>
          </>
        }>
        <form id="user-form" onSubmit={handleSave} className="space-y-5">

          {/* ── Basic Info ── */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-3 pb-1 border-b" style={{ color: '#1E2D7A' }}>
              1 — Account Details
            </h4>
            <div className="grid grid-cols-2 gap-4">
              <Input label="Full Name" required value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
              <Input label="Username" value={form.username} onChange={e => setForm(f => ({ ...f, username: e.target.value }))} />
              <Input label="Email" type="email" required value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} />
              <Input label={editing ? 'Password (leave blank to keep)' : 'Password *'}
                type="password" required={!editing} value={form.password}
                onChange={e => setForm(f => ({ ...f, password: e.target.value }))} />
            </div>
          </div>

          {/* ── Roles ── */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-3 pb-1 border-b" style={{ color: '#1E2D7A' }}>
              2 — Assign Roles
            </h4>
            {roles.length === 0 ? (
              <p className="text-xs text-gray-400 italic">No roles defined. Create roles first in the Roles page.</p>
            ) : (
              <div className="flex flex-wrap gap-2">
                {roles.map(r => (
                  <label key={r.id} className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg border cursor-pointer text-sm font-medium transition-colors
                    ${form.roles.includes(r.name) ? 'text-white border-transparent' : 'border-gray-200 text-gray-600 hover:border-blue-300'}`}
                    style={form.roles.includes(r.name) ? { background: '#1E2D7A', borderColor: '#1E2D7A' } : {}}>
                    <input type="checkbox" className="sr-only" checked={form.roles.includes(r.name)} onChange={() => toggleRole(r.name)} />
                    {r.name}
                    {form.roles.includes(r.name) && <span className="ml-0.5 text-white opacity-75">✓</span>}
                  </label>
                ))}
              </div>
            )}
          </div>

          {/* ── Direct Permissions ── */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-1 pb-1 border-b" style={{ color: '#1E2D7A' }}>
              3 — Individual Permissions
            </h4>
            <p className="text-xs text-gray-500 mb-3">
              Grant extra permissions beyond what this user's role already provides.
              Super Admin bypasses all permission checks automatically.
            </p>

            {allPerms.length === 0 ? (
              <p className="text-xs text-gray-400 italic">No permissions defined yet. Create permissions in the Permissions page first.</p>
            ) : (
              <>
                {form.permissions.length > 0 && (
                  <div className="mb-3 flex flex-wrap gap-1.5">
                    {form.permissions.map(pn => {
                      const cat = PERM_CATEGORIES.find(c => c.key === catFor(pn));
                      return (
                        <span key={pn} className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-mono font-semibold"
                          style={{ background: cat?.bg || '#f3f4f6', color: cat?.color || '#374151' }}>
                          {pn}
                          <button type="button" onClick={() => togglePerm(pn)} className="opacity-70 hover:opacity-100 ml-0.5">×</button>
                        </span>
                      );
                    })}
                  </div>
                )}

                <div className="mb-2">
                  <input className="input py-1.5 text-sm w-full" placeholder="Filter permissions…"
                    value={permSearch} onChange={e => setPermSearch(e.target.value)} />
                </div>

                <div className="space-y-3 max-h-56 overflow-y-auto rounded-lg border border-gray-100 p-3">
                  {PERM_CATEGORIES.map(cat => {
                    const items = groupedPerms[cat.key] || [];
                    if (!items.length) return null;
                    return (
                      <div key={cat.key}>
                        <p className="text-xs font-semibold mb-1.5" style={{ color: cat.color }}>{cat.label}</p>
                        <div className="flex flex-wrap gap-1.5">
                          {items.map(p => (
                            <label key={p.id} className="flex items-center gap-1 px-2 py-0.5 rounded-lg border cursor-pointer text-xs font-mono transition-colors"
                              style={form.permissions.includes(p.name)
                                ? { background: cat.bg, borderColor: cat.color, color: cat.color, fontWeight: 700 }
                                : { borderColor: '#e5e7eb', color: '#6b7280' }}>
                              <input type="checkbox" className="sr-only"
                                checked={form.permissions.includes(p.name)}
                                onChange={() => togglePerm(p.name)} />
                              {form.permissions.includes(p.name) && <span style={{ color: cat.color }}>✓ </span>}
                              {p.name}
                            </label>
                          ))}
                        </div>
                      </div>
                    );
                  })}
                </div>

                <div className="flex items-center justify-between mt-2">
                  <span className="text-xs text-gray-400">
                    {form.permissions.length > 0
                      ? `${form.permissions.length} permission(s) selected`
                      : 'No individual permissions — role permissions only'}
                  </span>
                  {form.permissions.length > 0 && (
                    <button type="button" onClick={() => setForm(f => ({ ...f, permissions: [] }))}
                      className="text-xs text-red-500 hover:text-red-700 underline">
                      Clear all
                    </button>
                  )}
                </div>
              </>
            )}
          </div>
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete User" danger message={`Delete user "${deleting?.name}"? This cannot be undone.`} />
    </div>
  );
}
