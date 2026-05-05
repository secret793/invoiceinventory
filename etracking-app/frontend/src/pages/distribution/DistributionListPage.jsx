import React, { useState, useEffect } from 'react';
import { distributionService } from '../../services/distributionService';
import { useNotification } from '../../contexts/NotificationContext';
import { useAuth } from '../../contexts/AuthContext';
import PageHeader from '../../components/common/PageHeader';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import DistributionPointCard from '../../components/distribution/DistributionPointCard';
import { Input } from '../../components/common/FormField';

export default function DistributionListPage() {
  const [points, setPoints]   = useState([]);
  const [loading, setLoading] = useState(true);
  const { canManageInventory } = useAuth();
  const { notify } = useNotification();
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing]   = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [saving, setSaving]     = useState(false);
  const [form, setForm] = useState({ name: '', location: '' });

  const load = () => {
    setLoading(true);
    distributionService.list().then(setPoints).catch(() => {}).finally(() => setLoading(false));
  };
  useEffect(load, []);

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await distributionService.update(editing.id, form);
      else         await distributionService.create(form);
      notify.success(`Distribution point ${editing ? 'updated' : 'created'}`);
      setShowForm(false); load();
    } catch (e) { notify.error(e.message); }
    finally     { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await distributionService.delete(deleting.id);
      notify.success('Distribution point deleted'); setDeleting(null); load();
    } catch (e) { notify.error(e.message); }
  };

  return (
    <div>
      <PageHeader title="Distribution Points" subtitle="Manage GPS tracker distribution stations"
        actions={canManageInventory() && (
          <button onClick={() => { setEditing(null); setForm({ name: '', location: '' }); setShowForm(true); }} className="btn-primary">
            + Add Distribution Point
          </button>
        )} />

      {loading ? (
        <div className="flex justify-center py-16"><div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" /></div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {points.map(dp => (
            <DistributionPointCard key={dp.id} dp={dp}
              onEdit={canManageInventory() ? (dp) => { setEditing(dp); setForm({ name: dp.name, location: dp.location || '' }); setShowForm(true); } : null}
              onDelete={canManageInventory() ? (dp) => setDeleting(dp) : null} />
          ))}
          {points.length === 0 && <div className="col-span-full text-center py-12 text-gray-400">No distribution points found.</div>}
        </div>
      )}

      <Modal isOpen={showForm} onClose={() => setShowForm(false)} title={editing ? 'Edit Distribution Point' : 'Add Distribution Point'}
        footer={
          <>
            <button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button>
            <button form="dp-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button>
          </>
        }>
        <form id="dp-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Name" required value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
          <Input label="Location" required value={form.location} onChange={e => setForm(f => ({ ...f, location: e.target.value }))} />
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Distribution Point" danger message={`Delete "${deleting?.name}"?`} />
    </div>
  );
}
