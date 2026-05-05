import React, { useState } from 'react';
import { useAllocationPoints } from '../../hooks/useAllocationPoints';
import { allocationService } from '../../services/allocationService';
import { useNotification } from '../../contexts/NotificationContext';
import { useAuth } from '../../contexts/AuthContext';
import PageHeader from '../../components/common/PageHeader';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import AllocationPointCard from '../../components/allocation/AllocationPointCard';
import { Input } from '../../components/common/FormField';

export default function AllocationListPage() {
  const { allocationPoints, loading, fetch } = useAllocationPoints();
  const { canManageInventory } = useAuth();
  const { notify }  = useNotification();
  const [showForm, setShowForm]   = useState(false);
  const [editing, setEditing]     = useState(null);
  const [deleting, setDeleting]   = useState(null);
  const [saving, setSaving]       = useState(false);
  const [form, setForm] = useState({ name: '', location: '', region: '' });

  const openEdit = (ap) => { setEditing(ap); setForm({ name: ap.name, location: ap.location || '', region: ap.region || '' }); setShowForm(true); };
  const openNew  = ()   => { setEditing(null); setForm({ name: '', location: '', region: '' }); setShowForm(true); };

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      if (editing) await allocationService.update(editing.id, form);
      else         await allocationService.create(form);
      notify.success(`Allocation point ${editing ? 'updated' : 'created'}`);
      setShowForm(false); fetch();
    } catch (e) { notify.error(e.message); }
    finally     { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await allocationService.delete(deleting.id);
      notify.success('Allocation point deleted'); setDeleting(null); fetch();
    } catch (e) { notify.error(e.message); }
  };

  return (
    <div>
      <PageHeader title="Allocation Points" subtitle="Manage GPS tracker allocation stations"
        actions={canManageInventory() && (
          <button onClick={openNew} className="btn-primary">+ Add Allocation Point</button>
        )} />

      {loading ? (
        <div className="flex justify-center py-16"><div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" /></div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {allocationPoints.map(ap => (
            <AllocationPointCard key={ap.id} ap={ap}
              onEdit={canManageInventory() ? openEdit : null}
              onDelete={canManageInventory() ? (ap) => setDeleting(ap) : null} />
          ))}
          {allocationPoints.length === 0 && (
            <div className="col-span-full text-center py-12 text-gray-400">
              No allocation points found.
            </div>
          )}
        </div>
      )}

      <Modal isOpen={showForm} onClose={() => setShowForm(false)} title={editing ? 'Edit Allocation Point' : 'Add Allocation Point'}
        footer={
          <>
            <button onClick={() => setShowForm(false)} className="btn-secondary">Cancel</button>
            <button form="ap-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button>
          </>
        }>
        <form id="ap-form" onSubmit={handleSave} className="space-y-4">
          <Input label="Name" required value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
          <Input label="Location" required value={form.location} onChange={e => setForm(f => ({ ...f, location: e.target.value }))} />
          <Input label="Region" value={form.region} onChange={e => setForm(f => ({ ...f, region: e.target.value }))} />
        </form>
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Allocation Point" danger message={`Delete "${deleting?.name}"? All associated data may be affected.`} />
    </div>
  );
}
