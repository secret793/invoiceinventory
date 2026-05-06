import React, { useState, useEffect, useCallback } from 'react';
import api from '../../services/api';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';

export default function TransfersPage() {
  const { notify } = useNotification();
  const [transfers, setTransfers] = useState([]);
  const [meta, setMeta]           = useState({});
  const [loading, setLoading]     = useState(false);
  const [params, setParams]       = useState({ page: 1, per_page: 25 });
  const [deleting, setDeleting]   = useState(null);
  const [selected, setSelected]   = useState([]);

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
      const res = await api.post('/transfers/bulk-cancel', { ids: selected, cancellation_reason: cancelReason });
      notify.success(`${res.data.data?.cancelled ?? selected.length} transfer(s) cancelled`);
      setShowCancel(false); setSelected([]); setCancelReason(''); load();
    } catch (e) { notify.error(e.message); }
    finally { setCancelling(false); }
  };

  const handleBulkApprove = async () => {
    setApproving(true);
    try {
      const res = await api.post('/transfers/bulk-approve', { ids: selected });
      notify.success(`${res.data.data?.approved ?? selected.length} transfer(s) approved — devices now appear at their destination`);
      setShowApprove(false); setSelected([]); load();
    } catch (e) { notify.error(e.message); }
    finally { setApproving(false); }
  };

  // Helper: derive display name for From/To location
  const toLocation = (t) => {
    if (t.transfer_type === 'DISTRIBUTION') return t.to_location_name || `DP #${t.to_distribution_point_id || '—'}`;
    return t.to_ap_name || `AP #${t.to_allocation_point_id || '—'}`;
  };
  const fromLocation = (t) => {
    if (t.transfer_type === 'DISTRIBUTION') return t.from_location_name || '(no prior DP)';
    return t.from_ap_name || '(no prior AP)';
  };

  const columns = [
    { header: 'Device ID',     key: 'device_identifier', render: v => <span className="font-mono font-semibold text-gray-900">{v || '—'}</span> },
    { header: 'Device Serial', key: 'device_serial',     render: v => <span className="font-mono text-gray-600">{v || '—'}</span> },
    { header: 'Current Status', key: 'device_current_status', render: v => v ? <StatusBadge status={v} /> : '—' },
    { header: 'Quantity',      key: 'quantity',           render: v => v || 1 },
    { header: 'Transfer Date', key: 'created_at',         render: v => v ? new Date(v).toLocaleDateString() : '—' },
    { header: 'Type',          key: 'transfer_type',      render: v => <StatusBadge status={v} /> },
    { header: 'Status',        key: 'transfer_status',    render: v => <StatusBadge status={v} /> },
    {
      header: 'From',
      key: 'from_location_name',
      render: (_, row) => <span className="text-xs text-gray-600">{fromLocation(row)}</span>,
    },
    {
      header: 'To',
      key: 'to_location_name',
      render: (_, row) => <span className="text-xs font-medium text-blue-700">{toLocation(row)}</span>,
    },
    { header: 'Cancelled At', key: 'cancelled_at', render: v => v ? new Date(v).toLocaleDateString() : '—' },
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

  const pendingCount = transfers.filter(t => t.transfer_status === 'PENDING').length;

  return (
    <div>
      <PageHeader title="Transfers" subtitle="Device transfers to distribution and allocation points — approve to complete"
        actions={
          pendingCount > 0 ? (
            <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-semibold"
              style={{ background: '#fef3c7', color: '#92400e', border: '1px solid #fcd34d' }}>
              <span className="w-2 h-2 rounded-full bg-yellow-500" />
              {pendingCount} pending transfer{pendingCount !== 1 ? 's' : ''}
            </div>
          ) : null
        } />

      {/* Info banner */}
      <div className="rounded-lg px-4 py-3 mb-4 text-sm flex items-start gap-2" style={{ background: '#eff6ff', border: '1px solid #bfdbfe', color: '#1e40af' }}>
        <span className="mt-0.5">ℹ️</span>
        <div>
          <p className="font-semibold mb-0.5">Transfer Flow</p>
          <p>Transfers are created from the <strong>Devices / Trackers</strong> section ("Transfer to Distribution Point"). Select PENDING transfers below and click <strong>Approve Transfers</strong> to move devices to their destination.</p>
        </div>
      </div>

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
                ✓ Approve Transfers ({pendingSelected})
              </button>
              <button onClick={() => setShowCancel(true)} className="btn-warning btn-sm">
                ✕ Cancel Transfers ({pendingSelected})
              </button>
            </>
          )}
          {pendingSelected === 0 && (
            <span className="text-xs text-gray-500">Select PENDING transfers to approve or cancel</span>
          )}
          <button onClick={() => setSelected([])} className="btn-secondary btn-sm ml-auto">Clear</button>
        </div>
      )}

      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={transfers} loading={loading}
          selectable selected={selected}
          onSelect={(id, checked) => setSelected(prev => checked ? [...prev, id] : prev.filter(x => x !== id))}
          onSelectAll={checked => setSelected(checked ? transfers.map(t => t.id) : [])}
          emptyMessage="No transfers found. Create transfers from the Devices / Trackers section." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination meta={meta} onPageChange={p => { const np = { ...params, page: p }; setParams(np); load(np); }} />
        </div>
      </div>

      {/* Delete Confirm */}
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Transfer" danger message="Delete this transfer? This cannot be undone." />

      {/* Approve Modal */}
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
            <li>Move each device to its <strong>destination distribution point</strong></li>
            <li>Mark transfers as <strong>COMPLETED</strong> and remove them from this list</li>
          </ul>
          <div className="rounded-lg p-3 text-xs" style={{ background: '#f0fdf4', border: '1px solid #86efac', color: '#166534' }}>
            After approval, devices will appear in the corresponding Distribution Point's device list.
          </div>
        </div>
      </Modal>

      {/* Cancel Modal */}
      <Modal isOpen={showCancel} onClose={() => setShowCancel(false)} title="Cancel Transfers"
        footer={
          <>
            <button onClick={() => setShowCancel(false)} className="btn-secondary">Back</button>
            <button onClick={handleBulkCancel} disabled={cancelling || !cancelReason.trim()} className="btn-danger">
              {cancelling ? 'Cancelling…' : `Cancel ${pendingSelected} Transfer(s)`}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">
            Cancel <strong>{pendingSelected}</strong> pending transfer(s)? Devices will revert to their original location.
          </p>
          <div>
            <label className="label">Cancellation Reason <span className="text-red-500">*</span></label>
            <textarea className="input" rows={3} value={cancelReason}
              onChange={e => setCancelReason(e.target.value)}
              placeholder="Required — explain why these transfers are being cancelled…" maxLength={1000} />
            <p className="text-xs text-gray-400 mt-1">{cancelReason.length}/1000</p>
          </div>
        </div>
      </Modal>
    </div>
  );
}
