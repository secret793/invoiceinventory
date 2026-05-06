import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { useRetrievals } from '../../hooks/useRetrievals';
import { retrievalService } from '../../services/retrievalService';
import { allocationService } from '../../services/allocationService';
import { distributionService } from '../../services/distributionService';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import { useAuth } from '../../contexts/AuthContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import RetrievalFilters from '../../components/retrievals/RetrievalFilters';
import InvoiceModal from '../../components/retrievals/InvoiceModal';

export default function RetrievalsPage() {
  const { retrievals, meta, loading, fetch, changePage, changeFilters } = useRetrievals();
  const { notify } = useNotification();
  const { hasRole, user } = useAuth();
  const [aps, setAps]   = useState([]);
  const [dps, setDps]   = useState([]);
  const [dests, setDests] = useState([]);

  const [editing, setEditing]     = useState(null);
  const [deleting, setDeleting]   = useState(null);
  const [invoicing, setInvoicing] = useState(null);
  const [saving, setSaving]       = useState(false);
  const [editForm, setEditForm]   = useState({});

  const [retrieveModal, setRetrieveModal]     = useState(null);
  const [retrieveForm, setRetrieveForm]       = useState({ t1_validation_ref: '' });
  const [retrieveLoading, setRetrieveLoading] = useState(false);

  const [outstationModal, setOutstationModal]     = useState(null);
  const [outstationDpId, setOutstationDpId]       = useState('');
  const [outstationLoading, setOutstationLoading] = useState(false);

  const [waiverTarget, setWaiverTarget]   = useState(null);
  const [waiverLoading, setWaiverLoading] = useState(false);

  const [payTarget, setPayTarget]     = useState(null);
  const [payLoading, setPayLoading]   = useState(false);

  const isFinance   = hasRole(['Finance Officer', 'Super Admin']);
  const isSuperAdmin = hasRole('Super Admin');
  const canRetrieve = hasRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer']);

  useEffect(() => {
    allocationService.list().then(setAps).catch(() => {});
    distributionService.list().then(setDps).catch(() => {});
    configService.destinations.list().then(setDests).catch(() => {});
  }, []);

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      await retrievalService.update(editing.id, editForm);
      notify.success('Retrieval updated'); setEditing(null); fetch();
    } catch (e) { notify.error(e.message); }
    finally { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await retrievalService.delete(deleting.id);
      notify.success('Retrieval deleted'); setDeleting(null); fetch();
    } catch (e) { notify.error(e.message); }
  };

  const handleRetrieve = async () => {
    setRetrieveLoading(true);
    try {
      await api.post(`/device-retrievals/${retrieveModal.id}/retrieve`, retrieveForm);
      notify.success('Device retrieved successfully');
      setRetrieveModal(null); setRetrieveForm({ t1_validation_ref: '' }); fetch();
    } catch (e) { notify.error(e.message); }
    finally { setRetrieveLoading(false); }
  };

  const handleReturnOutstation = async () => {
    if (!outstationDpId) { notify.error('Select a distribution point'); return; }
    setOutstationLoading(true);
    try {
      await api.post(`/device-retrievals/${outstationModal.id}/return-outstation`, { distribution_point_id: outstationDpId });
      notify.success('Device returned to outstation');
      setOutstationModal(null); setOutstationDpId(''); fetch();
    } catch (e) { notify.error(e.message); }
    finally { setOutstationLoading(false); }
  };

  const handleWaiver = async () => {
    setWaiverLoading(true);
    try {
      await api.post(`/device-retrievals/${waiverTarget.id}/waiver`);
      notify.success('Overstay fee waived');
      setWaiverTarget(null); fetch();
    } catch (e) { notify.error(e.message); }
    finally { setWaiverLoading(false); }
  };

  const handleApprovePayment = async () => {
    setPayLoading(true);
    try {
      await api.post(`/device-retrievals/${payTarget.id}/approve-payment`);
      notify.success('Payment approved');
      setPayTarget(null); fetch();
    } catch (e) { notify.error(e.message); }
    finally { setPayLoading(false); }
  };

  const columns = [
    { header: 'Affix Date',     key: 'date',               render: v => v ? new Date(v).toLocaleDateString() : '—' },
    { header: 'Device ID',      key: 'device_identifier',  render: v => <span className="font-mono font-semibold">{v || '—'}</span> },
    { header: 'BOE',            key: 'boe',                render: v => <span className="font-mono">{v || '—'}</span> },
    { header: 'Trans. Type',    key: 'transaction_type',   render: v => v ? <StatusBadge status={v} /> : '—' },
    { header: 'T1 Ref',         key: 't1_validation_ref',  render: v => v || '—' },
    { header: 'Vehicle',        key: 'vehicle_number',     render: v => v || '—' },
    { header: 'Regime',         key: 'regime',             render: v => v || '—' },
    { header: 'Station',        key: 'allocation_point_name', render: v => v || '—' },
    { header: 'Destination',    key: 'destination_name',   render: v => v || '—' },
    { header: 'Retrieval',      key: 'retrieval_status',   render: v => <StatusBadge status={v} /> },
    { header: 'Overstay',       key: 'overstay_days',
      render: v => (v || 0) > 0
        ? <span className="badge-red">{v} days</span>
        : <span className="badge-green">0</span>
    },
    { header: 'Overstay Amt',   key: 'overstay_amount',
      render: v => v > 0 ? <span className="font-semibold text-red-700">GMD {Number(v).toLocaleString()}</span> : '—'
    },
    { header: 'Payment',        key: 'payment_status',     render: v => <StatusBadge status={v || 'PP'} /> },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <div className="flex gap-1 flex-wrap">
          <button onClick={() => { setEditing(row); setEditForm({ retrieval_status: row.retrieval_status, payment_status: row.payment_status, overstay_amount: row.overstay_amount }); }}
            className="btn-secondary btn-sm">Edit</button>
          <button onClick={() => setInvoicing(row)} className="btn-primary btn-sm">Invoice</button>

          {canRetrieve && row.retrieval_status === 'NOT_RETRIEVED' && (
            <button onClick={() => { setRetrieveModal(row); setRetrieveForm({ t1_validation_ref: '' }); }}
              className="btn-success btn-sm">Retrieve</button>
          )}
          {canRetrieve && row.retrieval_status === 'RETRIEVED' && (
            <button onClick={() => { setOutstationModal(row); setOutstationDpId(''); }}
              className="btn-warning btn-sm">Return to Outstation</button>
          )}
          {(row.overstay_days || 0) >= 1 && row.payment_status === 'PP' && (
            <button onClick={() => api.post(`/device-retrievals/${row.id}/generate-invoice`).then(() => { notify.success('Invoice generated'); fetch(); }).catch(e => notify.error(e.message))}
              className="btn-danger btn-sm">Gen. Bill</button>
          )}
          {isSuperAdmin && (row.overstay_days || 0) > 0 && row.payment_status === 'PP' && (
            <button onClick={() => setWaiverTarget(row)} className="btn-secondary btn-sm">Waive</button>
          )}
          {isFinance && row.payment_status === 'PP' && (row.overstay_amount || 0) > 0 && (
            <button onClick={() => setPayTarget(row)} className="btn-success btn-sm">Approve Pmt</button>
          )}
          <button onClick={() => setDeleting(row)} className="btn-danger btn-sm">Del</button>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader title="Device Retrievals" subtitle="Track and manage device retrieval records"
        actions={
          <a href="/api/device-retrievals/export" target="_blank" className="btn-secondary">Export CSV</a>
        } />

      <div className="mb-4">
        <RetrievalFilters onFilter={changeFilters} allocationPoints={aps} destinations={dests} />
      </div>

      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={retrievals} loading={loading}
          emptyMessage="No retrieval records found." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination meta={meta} onPageChange={changePage} />
        </div>
      </div>

      {/* Edit Modal */}
      <Modal isOpen={!!editing} onClose={() => setEditing(null)} title="Update Retrieval" size="sm"
        footer={
          <>
            <button onClick={() => setEditing(null)} className="btn-secondary">Cancel</button>
            <button form="ret-form" type="submit" disabled={saving} className="btn-primary">{saving ? 'Saving…' : 'Save'}</button>
          </>
        }>
        <form id="ret-form" onSubmit={handleSave} className="space-y-4">
          <div>
            <label className="label">Retrieval Status</label>
            <select className="input" value={editForm.retrieval_status || ''} onChange={e => setEditForm(f => ({ ...f, retrieval_status: e.target.value }))}>
              <option value="NOT_RETRIEVED">Not Retrieved</option>
              <option value="RETRIEVED">Retrieved</option>
              <option value="OVERDUE">Overdue</option>
            </select>
          </div>
          {isFinance && (
            <>
              <div>
                <label className="label">Payment Status</label>
                <select className="input" value={editForm.payment_status || ''} onChange={e => setEditForm(f => ({ ...f, payment_status: e.target.value }))}>
                  <option value="PP">Pending</option>
                  <option value="PD">Paid</option>
                  <option value="WAIVED">Waived</option>
                  <option value="EXEMPTED">Exempted</option>
                </select>
              </div>
              <div>
                <label className="label">Overstay Amount (GMD)</label>
                <input type="number" className="input" value={editForm.overstay_amount || ''} onChange={e => setEditForm(f => ({ ...f, overstay_amount: e.target.value }))} />
              </div>
            </>
          )}
        </form>
      </Modal>

      {/* Invoice Modal */}
      <InvoiceModal retrieval={invoicing} isOpen={!!invoicing} onClose={() => setInvoicing(null)}
        onGenerated={() => { fetch(); }} />

      {/* Retrieve Device Modal */}
      <Modal isOpen={!!retrieveModal} onClose={() => setRetrieveModal(null)} title="Retrieve Device"
        footer={
          <>
            <button onClick={() => setRetrieveModal(null)} className="btn-secondary">Cancel</button>
            <button onClick={handleRetrieve} disabled={retrieveLoading} className="btn-success">
              {retrieveLoading ? 'Retrieving…' : 'Confirm Retrieval'}
            </button>
          </>
        }>
        {retrieveModal && (
          <div className="space-y-4">
            <div className="bg-gray-50 rounded-lg p-3 text-sm space-y-1">
              <p className="text-gray-500">BOE: <strong className="font-mono">{retrieveModal.boe || '—'}</strong></p>
              <p className="text-gray-500">Vehicle: <strong>{retrieveModal.vehicle_number || '—'}</strong></p>
              <p className="text-gray-500">Device: <strong className="font-mono">{retrieveModal.device_identifier || '—'}</strong></p>
            </div>
            {retrieveModal.transaction_type === 'SAD' && (
              <div>
                <label className="label">T1 Validation Reference</label>
                <input type="text" className="input" maxLength={100}
                  value={retrieveForm.t1_validation_ref}
                  onChange={e => setRetrieveForm(f => ({ ...f, t1_validation_ref: e.target.value }))}
                  placeholder="Enter T1 reference (if last device on SAD receipt)" />
              </div>
            )}
            {(retrieveModal.overstay_days || 0) > 0 && (
              <div className="rounded-lg p-3 border border-red-200 bg-red-50">
                <p className="text-sm text-red-700 font-medium">
                  ⚠️ This device has <strong>{retrieveModal.overstay_days} overstay day(s)</strong>.
                  Payment status: <StatusBadge status={retrieveModal.payment_status || 'PP'} />
                </p>
              </div>
            )}
          </div>
        )}
      </Modal>

      {/* Return to Outstation Modal */}
      <Modal isOpen={!!outstationModal} onClose={() => setOutstationModal(null)} title="Return to Outstation"
        footer={
          <>
            <button onClick={() => setOutstationModal(null)} className="btn-secondary">Cancel</button>
            <button onClick={handleReturnOutstation} disabled={outstationLoading || !outstationDpId} className="btn-warning">
              {outstationLoading ? 'Returning…' : 'Return to Outstation'}
            </button>
          </>
        }>
        <div className="space-y-4">
          <p className="text-sm text-gray-600">Select the distribution point to return the device to:</p>
          <div>
            <label className="label">Distribution Point <span className="text-red-500">*</span></label>
            <select className="input" value={outstationDpId} onChange={e => setOutstationDpId(e.target.value)}>
              <option value="">Select distribution point…</option>
              {dps.map(dp => <option key={dp.id} value={dp.id}>{dp.name} — {dp.location}</option>)}
            </select>
          </div>
          <div className="rounded-lg p-3 border border-amber-200 bg-amber-50">
            <p className="text-xs text-amber-700">
              The device will be set to PENDING status at the selected DP. The retrieval record will be archived (invoices preserved).
            </p>
          </div>
        </div>
      </Modal>

      {/* Waiver Confirm */}
      <ConfirmDialog isOpen={!!waiverTarget} onClose={() => setWaiverTarget(null)} onConfirm={handleWaiver}
        loading={waiverLoading} title="Admin Waiver"
        message={`Waive the overstay fee for BOE "${waiverTarget?.boe}"? This cannot be undone.`} />

      {/* Approve Payment Confirm */}
      <ConfirmDialog isOpen={!!payTarget} onClose={() => setPayTarget(null)} onConfirm={handleApprovePayment}
        loading={payLoading} title="Approve Payment"
        message={`Mark payment as PAID for BOE "${payTarget?.boe}" (GMD ${Number(payTarget?.overstay_amount || 0).toLocaleString()})?`} />

      {/* Delete Confirm */}
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Retrieval" danger message={`Delete retrieval for "${deleting?.boe}"?`} />
    </div>
  );
}
