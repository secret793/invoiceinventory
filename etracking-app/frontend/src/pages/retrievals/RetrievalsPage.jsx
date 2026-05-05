import React, { useState, useEffect } from 'react';
import { useRetrievals } from '../../hooks/useRetrievals';
import { retrievalService } from '../../services/retrievalService';
import { allocationService } from '../../services/allocationService';
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
  const { hasRole } = useAuth();
  const [aps, setAps]   = useState([]);
  const [dests, setDests] = useState([]);
  const [editing, setEditing]     = useState(null);
  const [deleting, setDeleting]   = useState(null);
  const [invoicing, setInvoicing] = useState(null);
  const [saving, setSaving]       = useState(false);
  const [editForm, setEditForm]   = useState({});

  useEffect(() => {
    allocationService.list().then(setAps).catch(() => {});
    configService.destinations.list().then(setDests).catch(() => {});
  }, []);

  const isFinance = hasRole(['Finance Officer', 'Super Admin']);

  const handleSave = async (e) => {
    e.preventDefault(); setSaving(true);
    try {
      await retrievalService.update(editing.id, editForm);
      notify.success('Retrieval updated'); setEditing(null); fetch();
    } catch (e) { notify.error(e.message); }
    finally     { setSaving(false); }
  };

  const handleDelete = async () => {
    try {
      await retrievalService.delete(deleting.id);
      notify.success('Retrieval deleted'); setDeleting(null); fetch();
    } catch (e) { notify.error(e.message); }
  };

  const columns = [
    { header: 'BOE',      key: 'boe',            render: v => <span className="font-mono font-medium">{v}</span> },
    { header: 'Vehicle',  key: 'vehicle_number' },
    { header: 'Device',   key: 'device_identifier', render: v => v || '—' },
    { header: 'Station',  key: 'allocation_point_name', render: v => v || '—' },
    { header: 'Destination', key: 'destination_name', render: v => v || '—' },
    { header: 'Overstay', key: 'overstay_days',   render: v => v > 0 ? <span className="badge-red">{v} days</span> : <span className="badge-green">0</span> },
    { header: 'Retrieval',key: 'retrieval_status', render: v => <StatusBadge status={v} /> },
    { header: 'Payment',  key: 'payment_status',   render: v => <StatusBadge status={v} /> },
    { header: 'Date',     key: 'date',             render: v => v ? new Date(v).toLocaleDateString() : '—' },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <div className="flex gap-1">
          <button onClick={() => { setEditing(row); setEditForm({ retrieval_status: row.retrieval_status, payment_status: row.payment_status, overstay_amount: row.overstay_amount }); }} className="btn-secondary btn-sm">Edit</button>
          <button onClick={() => setInvoicing(row)} className="btn-primary btn-sm">Invoice</button>
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

      <RetrievalFilters onFilter={changeFilters} allocationPoints={aps} destinations={dests} />

      <div className="card">
        <DataTable columns={columns} data={retrievals} loading={loading} emptyMessage="No retrieval records found." />
        <Pagination meta={meta} onPageChange={changePage} />
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
            <div>
              <label className="label">Payment Status</label>
              <select className="input" value={editForm.payment_status || ''} onChange={e => setEditForm(f => ({ ...f, payment_status: e.target.value }))}>
                <option value="PP">Pending</option>
                <option value="PAID">Paid</option>
                <option value="WAIVED">Waived</option>
                <option value="EXEMPTED">Exempted</option>
              </select>
            </div>
          )}
          {isFinance && (
            <div>
              <label className="label">Overstay Amount (GMD)</label>
              <input type="number" className="input" value={editForm.overstay_amount || ''} onChange={e => setEditForm(f => ({ ...f, overstay_amount: e.target.value }))} />
            </div>
          )}
        </form>
      </Modal>

      {/* Invoice Modal */}
      <InvoiceModal retrieval={invoicing} isOpen={!!invoicing} onClose={() => setInvoicing(null)}
        onGenerated={() => { fetch(); }} />

      {/* Delete Confirm */}
      <ConfirmDialog isOpen={!!deleting} onClose={() => setDeleting(null)} onConfirm={handleDelete}
        title="Delete Retrieval" danger message={`Delete retrieval for "${deleting?.boe}"?`} />
    </div>
  );
}
