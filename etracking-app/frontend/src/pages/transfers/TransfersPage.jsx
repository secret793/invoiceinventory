import React, { useState, useEffect, useCallback } from 'react';
import api from '../../services/api';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import TransferForm from '../../components/transfers/TransferForm';

export default function TransfersPage() {
  const { notify } = useNotification();
  const [transfers, setTransfers] = useState([]);
  const [meta, setMeta]           = useState({});
  const [loading, setLoading]     = useState(false);
  const [params, setParams]       = useState({ page: 1, per_page: 25 });
  const [showForm, setShowForm]   = useState(false);
  const [saving, setSaving]       = useState(false);
  const [deleting, setDeleting]   = useState(null);
  const [selected, setSelected]   = useState([]);
  const [showCancel, setShowCancel] = useState(false);
  const [cancelReason, setCancelReason] = useState('');

  const fetch = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const { data } = await api.get('/transfers', { params: p });
      setTransfers(data.data || []); setMeta(data.meta || {});
    } catch { } finally { setLoading(false); }
  }, [params]);

  useEffect(() => { fetch(); }, []);

  const handleCreate = async (form) => {
    setSaving(true);
    try {
      await api.post('/transfers', form);
      notify.success('Transfer created'); setShowForm(false); fetch();
    } catch (e) { notify.error(e.message); }
    finally     { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await api.delete(`/transfers/${deleting.id}`);
      notify.success('Transfer deleted'); setDeleting(null); fetch();
    } catch (e) { notify.error(e.message); }
  };

  const handleBulkCancel = async () => {
    if (!cancelReason.trim()) { notify.error('Please provide a cancellation reason'); return; }
    try {
      await api.post('/transfers/bulk-cancel', { ids: selected, cancellation_reason: cancelReason });
      notify.success(`${selected.length} transfer(s) cancelled`);
      setShowCancel(false); setSelected([]); setCancelReason(''); fetch();
    } catch (e) { notify.error(e.message); }
  };

  const columns = [
    { header: 'Device',          key: 'device_identifier', render: v => <span className="font-mono">{v || '—'}</span> },
    { header: 'Type',            key: 'transfer_type' },
    { header: 'Status',          key: 'transfer_status',   render: v => <StatusBadge status={v} /> },
    { header: 'Date',            key: 'created_at',        render: v => v ? new Date(v).toLocaleDateString() : '—' },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <button onClick={() => setDeleting(row)} className="btn-danger btn-sm">Delete</button>
      ),
    },
  ];

  return (
    <div>
      <PageHeader title="Transfers" subtitle="Device allocation and distribution transfers"
        actions={
          <div className="flex gap-2">
            {selected.length > 0 && (
              <button onClick={() => setShowCancel(true)} className="btn-warning">
                Cancel Selected ({selected.length})
              </button>
            )}
            <button onClick={() => setShowForm(true)} className="btn-primary">+ New Transfer</button>
          </div>
        } />

      <div className="card">
        <DataTable columns={columns} data={transfers} loading={loading}
          selectable selected={selected}
          onSelect={(id, checked) => setSelected(prev => checked ? [...prev, id] : prev.filter(x => x !== id))}
          onSelectAll={checked => setSelected(checked ? transfers.map(t => t.id) : [])}
          emptyMessage="No transfers found." />
        <Pagination meta={meta} onPageChange={p => { const np = { ...params, page: p }; setParams(np); fetch(np); }} />
      </div>

      <Modal isOpen={showForm} onClose={() => setShowForm(false)} title="Create Transfer" size="lg">
        <TransferForm onSubmit={handleCreate} loading={saving} onCancel={() => setShowForm(false)} />
      </Modal>

      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Transfer" danger message="Delete this transfer? This cannot be undone." />

      <Modal isOpen={showCancel} onClose={() => setShowCancel(false)} title="Cancel Transfers"
        footer={
          <>
            <button onClick={() => setShowCancel(false)} className="btn-secondary">Back</button>
            <button onClick={handleBulkCancel} className="btn-warning">Cancel Transfers</button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">Cancel {selected.length} transfer(s)? Provide a reason:</p>
          <textarea className="input" rows={3} value={cancelReason}
            onChange={e => setCancelReason(e.target.value)} placeholder="Reason for cancellation…" />
        </div>
      </Modal>
    </div>
  );
}
