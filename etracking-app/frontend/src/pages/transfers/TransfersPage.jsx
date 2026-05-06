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
  const [transfers, setTransfers]   = useState([]);
  const [meta, setMeta]             = useState({});
  const [loading, setLoading]       = useState(false);
  const [params, setParams]         = useState({ page: 1, per_page: 25 });
  const [showForm, setShowForm]     = useState(false);
  const [saving, setSaving]         = useState(false);
  const [deleting, setDeleting]     = useState(null);
  const [selected, setSelected]     = useState([]);

  const [showCancel, setShowCancel]     = useState(false);
  const [cancelReason, setCancelReason] = useState('');
  const [cancelling, setCancelling]     = useState(false);

  const [showApprove, setShowApprove] = useState(false);
  const [approving, setApproving]     = useState(false);

  const [statusFilter, setStatusFilter] = useState('');
  const [typeFilter, setTypeFilter]     = useState('');

  const load = useCallback(async (p = params) => {
    setLoading(true);
    try {
      const { data } = await api.get('/transfers', { params: p });
      setTransfers(data.data || []); setMeta(data.meta || {});
    } catch { } finally { setLoading(false); }
  }, [params]);

  useEffect(() => { load(); }, []);

  const applyFilters = (extra) => {
    const p = { ...params, ...extra, page: 1 };
    setParams(p); load(p);
  };

  const handleCreate = async (form) => {
    setSaving(true);
    try {
      await api.post('/transfers', form);
      notify.success('Transfer created'); setShowForm(false); load();
    } catch (e) { notify.error(e.message); }
    finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await api.delete(`/transfers/${deleting.id}`);
      notify.success('Transfer deleted'); setDeleting(null); load();
    } catch (e) { notify.error(e.message); }
  };

  const handleBulkCancel = async () => {
    if (!cancelReason.trim()) { notify.error('Please provide a cancellation reason'); return; }
    setCancelling(true);
    try {
      await api.post('/transfers/bulk-cancel', { ids: selected, cancellation_reason: cancelReason });
      notify.success(`${selected.length} transfer(s) cancelled`);
      setShowCancel(false); setSelected([]); setCancelReason(''); load();
    } catch (e) { notify.error(e.message); }
    finally { setCancelling(false); }
  };

  const handleBulkApprove = async () => {
    setApproving(true);
    try {
      const res = await api.post('/transfers/bulk-approve', { ids: selected });
      notify.success(`${res.data.data?.approved ?? selected.length} transfer(s) approved`);
      setShowApprove(false); setSelected([]); load();
    } catch (e) { notify.error(e.message); }
    finally { setApproving(false); }
  };

  const columns = [
    { header: 'Device',         key: 'device_identifier', render: v => <span className="font-mono font-semibold">{v || '—'}</span> },
    { header: 'Serial',         key: 'device_serial',      render: v => v || '—' },
    { header: 'Type',           key: 'transfer_type',      render: v => <StatusBadge status={v} /> },
    { header: 'Status',         key: 'transfer_status',    render: v => <StatusBadge status={v} /> },
    { header: 'Quantity',       key: 'quantity',            render: v => v || 1 },
    { header: 'Date',           key: 'created_at',          render: v => v ? new Date(v).toLocaleDateString() : '—' },
    { header: 'Cancelled At',   key: 'cancelled_at',        render: v => v ? new Date(v).toLocaleDateString() : '—' },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <button onClick={() => setDeleting(row)} className="btn-danger btn-sm">Delete</button>
      ),
    },
  ];

  const pendingSelected = selected.filter(id =>
    transfers.find(t => t.id === id && t.transfer_status === 'PENDING')
  ).length;

  return (
    <div>
      <PageHeader title="Transfers" subtitle="Device allocation and distribution transfers"
        actions={
          <button onClick={() => setShowForm(true)} className="btn-primary">+ New Transfer</button>
        } />

      {/* Filters */}
      <div className="flex flex-wrap gap-3 mb-4">
        <select className="input w-44" value={typeFilter}
          onChange={e => { setTypeFilter(e.target.value); applyFilters({ transfer_type: e.target.value }); }}>
          <option value="">All Types</option>
          <option value="DISTRIBUTION">Distribution</option>
          <option value="ALLOCATION">Allocation</option>
        </select>
        <select className="input w-44" value={statusFilter}
          onChange={e => { setStatusFilter(e.target.value); applyFilters({ transfer_status: e.target.value }); }}>
          <option value="">All Statuses</option>
          <option value="PENDING">Pending</option>
          <option value="COMPLETED">Completed</option>
          <option value="CANCELLED">Cancelled</option>
        </select>
      </div>

      {/* Bulk Actions Bar */}
      {selected.length > 0 && (
        <div className="flex flex-wrap items-center gap-3 mb-4 rounded-xl px-4 py-3 border"
          style={{ background: '#eef1fb', borderColor: '#c7cef0' }}>
          <span className="text-sm font-semibold" style={{ color: '#1E2D7A' }}>{selected.length} selected</span>
          {pendingSelected > 0 && (
            <>
              <button onClick={() => setShowApprove(true)} className="btn-success btn-sm">
                Approve Transfers ({pendingSelected})
              </button>
              <button onClick={() => setShowCancel(true)} className="btn-warning btn-sm">
                Cancel Transfers ({pendingSelected})
              </button>
            </>
          )}
          <button onClick={() => setSelected([])} className="btn-secondary btn-sm ml-auto">Clear</button>
        </div>
      )}

      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={transfers} loading={loading}
          selectable selected={selected}
          onSelect={(id, checked) => setSelected(prev => checked ? [...prev, id] : prev.filter(x => x !== id))}
          onSelectAll={checked => setSelected(checked ? transfers.map(t => t.id) : [])}
          emptyMessage="No transfers found." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination meta={meta} onPageChange={p => { const np = { ...params, page: p }; setParams(np); load(np); }} />
        </div>
      </div>

      {/* New Transfer Modal */}
      <Modal isOpen={showForm} onClose={() => setShowForm(false)} title="Create Transfer" size="lg">
        <TransferForm onSubmit={handleCreate} loading={saving} onCancel={() => setShowForm(false)} />
      </Modal>

      {/* Delete Confirm */}
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Transfer" danger message="Delete this transfer? This cannot be undone." />

      {/* Approve Confirm */}
      <Modal isOpen={showApprove} onClose={() => setShowApprove(false)} title="Approve Transfers"
        footer={
          <>
            <button onClick={() => setShowApprove(false)} className="btn-secondary">Cancel</button>
            <button onClick={handleBulkApprove} disabled={approving} className="btn-success">
              {approving ? 'Approving…' : `Approve ${pendingSelected} Transfer(s)`}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-700">
            Approving <strong>{pendingSelected}</strong> pending transfer(s) will:
          </p>
          <ul className="text-sm text-gray-600 list-disc list-inside space-y-1">
            <li>Set each device's status to <strong>RECEIVED</strong></li>
            <li>Update each device's location to the destination point</li>
            <li>Mark transfers as <strong>COMPLETED</strong> and remove them</li>
          </ul>
        </div>
      </Modal>

      {/* Cancel Modal */}
      <Modal isOpen={showCancel} onClose={() => setShowCancel(false)} title="Cancel Transfers"
        footer={
          <>
            <button onClick={() => setShowCancel(false)} className="btn-secondary">Back</button>
            <button onClick={handleBulkCancel} disabled={cancelling} className="btn-warning">
              {cancelling ? 'Cancelling…' : `Cancel ${pendingSelected} Transfer(s)`}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">Cancel <strong>{pendingSelected}</strong> transfer(s)? Provide a reason:</p>
          <textarea className="input" rows={3} value={cancelReason}
            onChange={e => setCancelReason(e.target.value)} placeholder="Reason for cancellation…" />
        </div>
      </Modal>
    </div>
  );
}
