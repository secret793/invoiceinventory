import React, { useState, useEffect, useCallback } from 'react';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';

const CATEGORIES = [
  { key: 'allocation_points', label: 'Allocation Points', color: '#1E2D7A', bg: '#eef1fb',
    desc: 'Control which allocation points a user can view or enter data for.',
    match: n => n.includes('allocationpoint') || (n.includes('data_entry') && !n.includes('destination')) },
  { key: 'destinations',      label: 'Destinations',      color: '#085E37', bg: '#dcfce7',
    desc: 'Restrict which destinations a Read Only Tracker Officer can see.',
    match: n => n.includes('destination') },
  { key: 'other',             label: 'General',           color: '#6b21a8', bg: '#f3e8ff',
    desc: 'Custom permissions for any other access control need.',
    match: () => true },
];

function categorise(name) {
  for (const c of CATEGORIES) {
    if (c.match(name)) return c.key;
  }
  return 'other';
}

const PERM_TEMPLATES = {
  allocation_point: ['view_allocationpoint_{slug}', 'edit_allocationpoint_{slug}', 'view_data_entry_{slug}'],
  destination:      ['view_destination_{slug}', 'manage_devices_destination_{slug}'],
};

export default function PermissionsPage() {
  const { notify } = useNotification();
  const [perms, setPerms]         = useState([]);
  const [loading, setLoading]     = useState(false);
  const [showForm, setShowForm]   = useState(false);
  const [editing, setEditing]     = useState(null);
  const [deleting, setDeleting]   = useState(null);
  const [saving, setSaving]       = useState(false);
  const [form, setForm]           = useState({ name: '' });
  const [showAuto, setShowAuto]   = useState(false);
  const [autoType, setAutoType]   = useState('allocation_point');
  const [autoSlug, setAutoSlug]   = useState('');
  const [autoSaving, setAutoSaving] = useState(false);
  const [search, setSearch]       = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try { setPerms(await configService.permissions.list()); }
    catch { } finally { setLoading(false); }
  }, []);

  useEffect(() => { load(); }, []);

  const openEdit = (p) => { setEditing(p); setForm({ name: p.name }); setShowForm(true); };
  const openNew  = ()  => { setEditing(null); setForm({ name: '' }); setShowForm(true); };

  const handleSave = async (e) => {
    e.preventDefault();
    const name = form.name.trim().toLowerCase().replace(/\s+/g, '_');
    if (!name) { notify.error('Permission name is required'); return; }
    setSaving(true);
    try {
      if (editing) await configService.permissions.update(editing.id, { name });
      else         await configService.permissions.create({ name });
      notify.success(`Permission ${editing ? 'updated' : 'created'}`);
      setShowForm(false); load();
    } catch (e) { notify.error(e.response?.data?.message || e.message); }
    finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await configService.permissions.delete(deleting.id);
      notify.success('Permission deleted'); setDeleting(null); load();
    } catch (e) { notify.error(e.response?.data?.message || e.message); }
  };

  const handleAutoCreate = async (e) => {
    e.preventDefault();
    const slug = autoSlug.trim().toLowerCase().replace(/\s+/g, '_');
    if (!slug) { notify.error('Slug is required'); return; }
    setAutoSaving(true);
    try {
      await configService.permissions.autoCreate(autoType, slug);
      const names = PERM_TEMPLATES[autoType].map(t => t.replace('{slug}', slug));
      notify.success(`Created: ${names.join(', ')}`);
      setShowAuto(false); setAutoSlug(''); load();
    } catch (e) { notify.error(e.response?.data?.message || e.message); }
    finally { setAutoSaving(false); }
  };

  const filtered = search
    ? perms.filter(p => p.name.toLowerCase().includes(search.toLowerCase()))
    : perms;

  const grouped = CATEGORIES.reduce((acc, c) => { acc[c.key] = []; return acc; }, {});
  filtered.forEach(p => { const k = categorise(p.name); grouped[k] = grouped[k] || []; grouped[k].push(p); });

  return (
    <div>
      <PageHeader title="Permissions" subtitle="Create and manage granular access permissions"
        breadcrumbs={[{ label: 'Configuration' }, { label: 'Permissions' }]}
        actions={
          <div className="flex gap-2">
            <button onClick={() => setShowAuto(true)} className="btn-secondary">⚡ Auto-Create</button>
            <button onClick={openNew} className="btn-primary">+ Add Permission</button>
          </div>
        } />

      {/* Info banner */}
      <div className="mb-5 rounded-xl px-5 py-4 text-sm border" style={{ background: '#fefce8', borderColor: '#fde047', color: '#713f12' }}>
        <strong>How permissions work:</strong> Permissions are assigned to <strong>roles</strong> (Roles page) or directly to <strong>individual users</strong> (Users page).
        When a user logs in, they inherit all permissions from their role(s) plus any direct permissions. Super Admin bypasses all checks.
        <div className="mt-2 flex flex-wrap gap-3 text-xs font-mono">
          <span className="px-2 py-0.5 rounded" style={{ background: '#eef1fb', color: '#1E2D7A' }}>view_allocationpoint_banjul</span>
          <span className="px-2 py-0.5 rounded" style={{ background: '#dcfce7', color: '#085E37' }}>view_destination_senegal</span>
          <span className="px-2 py-0.5 rounded" style={{ background: '#f3e8ff', color: '#6b21a8' }}>manage_devices</span>
        </div>
      </div>

      {/* Search */}
      <div className="mb-4">
        <input className="input w-72" placeholder="Search permissions…"
          value={search} onChange={e => setSearch(e.target.value)} />
      </div>

      {loading ? (
        <div className="text-center py-12 text-gray-400">Loading…</div>
      ) : (
        <div className="space-y-6">
          {CATEGORIES.map(cat => {
            const items = grouped[cat.key] || [];
            if (!items.length && search) return null;
            return (
              <div key={cat.key} className="card">
                <div className="flex items-center justify-between mb-1">
                  <div className="flex items-center gap-2">
                    <span className="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold"
                      style={{ background: cat.bg, color: cat.color }}>{cat.label}</span>
                    <span className="text-xs text-gray-400">{items.length} permission{items.length !== 1 ? 's' : ''}</span>
                  </div>
                </div>
                <p className="text-xs text-gray-500 mb-3">{cat.desc}</p>

                {items.length === 0 ? (
                  <p className="text-xs italic text-gray-400">No {cat.label.toLowerCase()} permissions yet. Use the buttons above to create some.</p>
                ) : (
                  <div className="flex flex-wrap gap-2">
                    {items.map(p => (
                      <div key={p.id} className="flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-mono"
                        style={{ background: cat.bg, borderColor: cat.color + '44', color: cat.color }}>
                        <span>{p.name}</span>
                        <button onClick={() => openEdit(p)} title="Edit"
                          className="ml-1 opacity-60 hover:opacity-100 transition-opacity text-gray-600 hover:text-blue-700">✏️</button>
                        <button onClick={() => setDeleting(p)} title="Delete"
                          className="opacity-60 hover:opacity-100 transition-opacity text-gray-600 hover:text-red-600">×</button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Add / Edit modal */}
      <Modal isOpen={showForm} onClose={() => setShowForm(false)}
        title={editing ? `Edit Permission` : 'Add Permission'}
        footer={
          <>
            <button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button>
            <button form="perm-form" type="submit" disabled={saving} className="btn-primary">
              {saving ? 'Saving…' : editing ? 'Update' : 'Create'}
            </button>
          </>
        }>
        <form id="perm-form" onSubmit={handleSave} className="space-y-4">
          <div>
            <label className="label">Permission Name <span className="text-red-500">*</span></label>
            <input required className="input font-mono" placeholder="e.g. view_allocationpoint_banjul"
              value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
            <p className="text-xs text-gray-400 mt-1">
              Use lowercase with underscores. Category is auto-detected from the name.
              Pattern: <span className="font-mono">view_allocationpoint_slug</span>, <span className="font-mono">view_destination_slug</span>, or any custom name.
            </p>
          </div>
          {form.name && (
            <div className="rounded-lg px-3 py-2 text-xs"
              style={{ background: CATEGORIES.find(c => c.key === categorise(form.name.toLowerCase()))?.bg || '#f3f4f6',
                       color:      CATEGORIES.find(c => c.key === categorise(form.name.toLowerCase()))?.color || '#374151' }}>
              Category: <strong>{CATEGORIES.find(c => c.key === categorise(form.name.toLowerCase()))?.label}</strong>
            </div>
          )}
        </form>
      </Modal>

      {/* Auto-create modal */}
      <Modal isOpen={showAuto} onClose={() => setShowAuto(false)}
        title="⚡ Auto-Create Permission Set"
        footer={
          <>
            <button onClick={() => setShowAuto(false)} className="btn-secondary">Cancel</button>
            <button form="auto-form" type="submit" disabled={autoSaving} className="btn-primary">
              {autoSaving ? 'Creating…' : 'Create Set'}
            </button>
          </>
        }>
        <form id="auto-form" onSubmit={handleAutoCreate} className="space-y-4">
          <p className="text-sm text-gray-600">
            Automatically generate the standard permission set for an allocation point or destination.
            Use the same slug that appears in the name (e.g. <span className="font-mono font-bold">banjul</span> → <span className="font-mono">view_allocationpoint_banjul</span>).
          </p>
          <div>
            <label className="label">Type <span className="text-red-500">*</span></label>
            <select className="input" value={autoType} onChange={e => setAutoType(e.target.value)}>
              <option value="allocation_point">Allocation Point</option>
              <option value="destination">Destination</option>
            </select>
          </div>
          <div>
            <label className="label">Slug <span className="text-red-500">*</span></label>
            <input required className="input font-mono" placeholder="e.g. banjul"
              value={autoSlug} onChange={e => setAutoSlug(e.target.value)} />
          </div>
          {autoSlug.trim() && (
            <div className="rounded-lg px-3 py-2 space-y-1"
              style={{ background: '#f8fafc', borderColor: '#e2e8f0', border: '1px solid' }}>
              <p className="text-xs font-semibold text-gray-500">Will create:</p>
              {PERM_TEMPLATES[autoType].map(t => (
                <p key={t} className="text-xs font-mono" style={{ color: '#1E2D7A' }}>
                  {t.replace('{slug}', autoSlug.trim().toLowerCase().replace(/\s+/g, '_'))}
                </p>
              ))}
            </div>
          )}
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Permission" danger
        message={`Delete permission "${deleting?.name}"? It will be removed from all roles and users.`} />
    </div>
  );
}
