import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { dataEntryService } from '../../services/dataEntryService';
import { allocationService } from '../../services/allocationService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import AssignToAgentForm from '../../components/dataentry/AssignToAgentForm';

export default function DataEntryDetailPage() {
  const { id } = useParams();
  const { notify }  = useNotification();
  const [assignment, setAssignment]   = useState(null);
  const [ap, setAp]                   = useState(null);
  const [assignments, setAssignments] = useState([]);
  const [meta, setMeta]               = useState({});
  const [loading, setLoading]         = useState(true);
  const [showAssign, setShowAssign]   = useState(false);
  const [assigning, setAssigning]     = useState(false);
  const [returnModal, setReturnModal] = useState(null);
  const [returnNote, setReturnNote]   = useState('');
  const [returning, setReturning]     = useState(false);

  const load = () => {
    setLoading(true);
    Promise.all([
      allocationService.get(id).catch(() => null),
      dataEntryService.list({ allocation_point_id: id, per_page: 50 }),
    ]).then(([apData, res]) => {
      setAp(apData);
      setAssignments(res.data || []);
      setMeta(res.meta || {});
    }).catch(() => {}).finally(() => setLoading(false));
  };

  useEffect(load, [id]);

  const handleAssign = async (form) => {
    setAssigning(true);
    try {
      const newest = assignments[0];
      if (!newest) { notify.error('No assignment found for this allocation point'); return; }
      await dataEntryService.assignToAgent(newest.id, form);
      notify.success('Assigned to agent successfully');
      setShowAssign(false); load();
    } catch (e) { notify.error(e.message); }
    finally     { setAssigning(false); }
  };

  const handleReturn = async () => {
    if (!returnModal) return;
    setReturning(true);
    try {
      await dataEntryService.returnDevice(returnModal.id, returnNote);
      notify.success('Device returned'); setReturnModal(null); setReturnNote(''); load();
    } catch (e) { notify.error(e.message); }
    finally     { setReturning(false); }
  };

  const columns = [
    { header: '#',     key: 'id' },
    { header: 'Status', key: 'status', render: v => <StatusBadge status={v} /> },
    { header: 'Date',   key: 'created_at', render: v => v ? new Date(v).toLocaleDateString() : '—' },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => (
        <div className="flex gap-1">
          <button onClick={() => setShowAssign(true)} className="btn-primary btn-sm">Assign</button>
          <button onClick={() => setReturnModal(row)} className="btn-warning btn-sm">Return</button>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader
        title={ap?.name || `Data Entry #${id}`}
        subtitle={ap?.location || ''}
        breadcrumbs={[{ label: 'Data Entry', path: '/data-entry' }, { label: ap?.name || id }]}
        actions={<button onClick={() => setShowAssign(true)} className="btn-primary">+ Assign to Agent</button>} />

      <div className="grid grid-cols-3 gap-4 mb-6">
        <div className="card text-center">
          <p className="text-3xl font-bold text-blue-600">{ap?.received_count ?? 0}</p>
          <p className="text-gray-500 text-sm">Devices Available</p>
        </div>
        <div className="card text-center">
          <p className="text-3xl font-bold text-yellow-600">{assignments.filter(a => a.status === 'PENDING').length}</p>
          <p className="text-gray-500 text-sm">Pending</p>
        </div>
        <div className="card text-center">
          <p className="text-3xl font-bold text-green-600">{assignments.filter(a => a.status === 'ASSIGNED').length}</p>
          <p className="text-gray-500 text-sm">Assigned</p>
        </div>
      </div>

      <div className="card">
        <DataTable columns={columns} data={assignments} loading={loading} emptyMessage="No assignments yet." />
      </div>

      <Modal isOpen={showAssign} onClose={() => setShowAssign(false)} title="Assign Device to Agent" size="lg">
        <AssignToAgentForm assignment={{ ...assignments[0], allocation_point_name: ap?.name }}
          onSubmit={handleAssign} loading={assigning} onCancel={() => setShowAssign(false)} />
      </Modal>

      <Modal isOpen={!!returnModal} onClose={() => setReturnModal(null)} title="Return Device"
        footer={
          <>
            <button onClick={() => setReturnModal(null)} className="btn-secondary">Cancel</button>
            <button onClick={handleReturn} disabled={returning} className="btn-warning">
              {returning ? 'Returning…' : 'Return Device'}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">Provide a reason for returning this device:</p>
          <textarea className="input" rows={3} value={returnNote} onChange={e => setReturnNote(e.target.value)} placeholder="Return reason…" />
        </div>
      </Modal>
    </div>
  );
}
