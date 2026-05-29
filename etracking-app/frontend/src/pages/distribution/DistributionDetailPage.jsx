import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../../services/api';
import { distributionService } from '../../services/distributionService';
import { allocationService } from '../../services/allocationService';
import { distributionService as dpSvc } from '../../services/distributionService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import ConfirmDialog from '../../components/common/ConfirmDialog';

const STATUSES = ['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'RECEIVED', 'PENDING'];
const STATUS_COLORS = {
  ONLINE: { bg: '#dcfce7', text: '#166534' }, OFFLINE: { bg: '#fee2e2', text: '#991b1b' },
  DAMAGED: { bg: '#ffedd5', text: '#9a3412' }, FIXED: { bg: '#f3e8ff', text: '#6b21a8' },
  LOST: { bg: '#f3f4f6', text: '#374151' }, RECEIVED: { bg: '#dbeafe', text: '#1e40af' },
  PENDING: { bg: '#fef9c3', text: '#854d0e' },
};

export default function DistributionDetailPage() {
  const { id } = useParams();
  const { notify } = useNotification();
  const [dp, setDp]           = useState(null);
  const [devices, setDevices] = useState([]);
  const [counts, setCounts]   = useState({});
  const [meta, setMeta]       = useState({});
  const [loading, setLoading] = useState(true);
  const [page, setPage]       = useState(1);
  const [perPage, setPerPage] = useState(25);
  const [statusFilter, setStatusFilter] = useState('');
  const [selected, setSelected] = useState([]);
  const [aps, setAps]           = useState([]);
  const [dps, setDps]           = useState([]);

  const [showSendAP, setShowSendAP]             = useState(false);
  const [showChangeStatus, setShowChangeStatus] = useState(false);
  const [showSendAnotherDP, setShowSendAnotherDP] = useState(false);
  const [selectedAP, setSelectedAP]   = useState('');
  const [selectedDP, setSelectedDP]   = useState('');
  const [newStatus, setNewStatus]     = useState('');
  const [actionLoading, setActionLoading] = useState(false);

  const loadDP = useCallback(() => {
    distributionService.get(id).then(setDp).catch(() => {});
  }, [id]);

  const loadDevices = useCallback(() => {
    setLoading(true);
    const params = { page, per_page: perPage, status: statusFilter || undefined };
    distributionService.devices(id, params).then(r => {
      setDevices(r.data || []); setMeta(r.meta || {});
    }).catch(() => {}).finally(() => setLoading(false));
  }, [id, page, perPage, statusFilter]);

  const loadCounts = useCallback(() => {
    api.get(`/distribution-points/${id}/status-counts`).then(r => setCounts(r.data.data || {})).catch(() => {});
  }, [id]);

  useEffect(() => {
    loadDP(); loadCounts();
    allocationService.list().then(setAps).catch(() => {});
    dpSvc.list().then(setDps).catch(() => {});
  }, [id]);

  useEffect(() => { loadDevices(); }, [id, page, perPage, statusFilter]);

  const doAction = async (endpoint, body, successMsg) => {
    setActionLoading(true);
    try {
      await api.post(`/distribution-points/${id}/${endpoint}`, body);
      notify.success(successMsg);
      setSelected([]); loadDevices(); loadCounts();
    } catch (e) { notify.error(e.message); }
    finally { setActionLoading(false); }
  };

  const handleAcceptDevices = () => {
    const hasReceived = selected.some(sid => devices.find(d => d.id === sid && d.status === 'RECEIVED'));
    if (!hasReceived) { notify.error('Select devices with RECEIVED status to accept'); return; }
    doAction('accept-devices', { device_ids: selected }, 'Devices accepted successfully');
  };

  const handleAcceptReturned = () =>
    doAction('accept-returned', { device_ids: selected }, 'Returned devices accepted');

  const handleRejectDevices = () =>
    doAction('reject-devices', { device_ids: selected }, 'Devices rejected');

  const handleReturnInventory = () =>
    doAction('return-inventory', { device_ids: selected }, 'Devices returned to inventory');

  const handleSendToAP = async () => {
    if (!selectedAP) { notify.error('Select an allocation point'); return; }
    await doAction('send-to-ap', { device_ids: selected, allocation_point_id: selectedAP }, 'Devices sent to allocation point');
    setShowSendAP(false); setSelectedAP('');
  };

  const handleChangeStatus = async () => {
    if (!newStatus) { notify.error('Select a status'); return; }
    await doAction('change-status', { device_ids: selected, status: newStatus }, 'Status updated');
    setShowChangeStatus(false); setNewStatus('');
  };

  const handleSendAnotherDP = async () => {
    if (!selectedDP) { notify.error('Select a distribution point'); return; }
    await doAction('send-to-another-dp', { device_ids: selected, target_distribution_point_id: selectedDP }, 'Devices sent to another distribution point');
    setShowSendAnotherDP(false); setSelectedDP('');
  };

  const hasReceived = selected.some(sid => devices.find(d => d.id === sid && d.status === 'RECEIVED'));
  const hasPending  = selected.some(sid => devices.find(d => d.id === sid && d.status === 'PENDING'));
  const hasNonReceived = selected.some(sid => devices.find(d => d.id === sid && d.status !== 'RECEIVED'));

  const columns = [
    { header: 'Device ID',   key: 'device_id',     render: v => <span className="font-mono font-semibold">{v}</span> },
    { header: 'Type',        key: 'device_type',   render: v => v || '—' },
    { header: 'Batch',       key: 'batch_number',  render: v => v || '—' },
    { header: 'SIM',         key: 'sim_number',    render: v => v || '—' },
    { header: 'Status',      key: 'status',        render: v => <StatusBadge status={v} /> },
    { header: 'Date Received', key: 'date_received', render: v => v ? new Date(v).toLocaleDateString() : '—' },
  ];

  return (
    <div>
      <PageHeader
        title={dp?.name || 'Distribution Point'}
        subtitle={dp?.location || ''}
        breadcrumbs={[{ label: 'Distribution Points', path: '/distribution' }, { label: dp?.name || id }]}
      />

      {/* Status Counts */}
      <div className="flex flex-wrap gap-3 mb-5">
        <button onClick={() => setStatusFilter('')}
          className={`card-sm flex items-center gap-2 cursor-pointer hover:shadow-md transition-all ${!statusFilter ? 'ring-2' : ''}`}
          style={{ ringColor: '#1E2D7A', borderColor: !statusFilter ? '#1E2D7A' : undefined }}>
          <p className="text-xs text-gray-500">All</p>
          <p className="text-lg font-bold" style={{ color: '#1E2D7A' }}>
            {Object.values(counts).reduce((a, b) => a + b, 0)}
          </p>
        </button>
        {STATUSES.map(s => (
          <button key={s} onClick={() => setStatusFilter(statusFilter === s ? '' : s)}
            className={`card-sm flex items-center gap-2 cursor-pointer hover:shadow-md transition-all ${statusFilter === s ? 'ring-2' : ''}`}
            style={{ ringColor: '#1E2D7A', borderColor: statusFilter === s ? '#1E2D7A' : undefined }}>
            <div style={{ background: STATUS_COLORS[s]?.bg, borderRadius: 4, padding: '2px 6px' }}>
              <p className="text-xs font-semibold" style={{ color: STATUS_COLORS[s]?.text }}>{s}</p>
            </div>
            <p className="text-lg font-bold text-gray-900">{counts[s] || 0}</p>
          </button>
        ))}
      </div>

      {/* Action Buttons when devices selected */}
      {selected.length > 0 && (
        <div className="flex flex-wrap items-center gap-2 mb-4 rounded-xl px-4 py-3 border"
          style={{ background: '#eef1fb', borderColor: '#c7cef0' }}>
          <span className="text-sm font-semibold mr-2" style={{ color: '#1E2D7A' }}>
            {selected.length} selected
          </span>
          {hasReceived && (
            <button onClick={handleAcceptDevices} disabled={actionLoading} className="btn-success btn-sm">
              Accept Devices
            </button>
          )}
          {hasPending && (
            <>
              <button onClick={handleAcceptReturned} disabled={actionLoading} className="btn-success btn-sm">
                Accept Returned
              </button>
              <button onClick={handleRejectDevices} disabled={actionLoading} className="btn-danger btn-sm">
                Reject Device(s)
              </button>
            </>
          )}
          {hasNonReceived && (
            <>
              <button onClick={() => setShowSendAP(true)} disabled={actionLoading} className="btn-primary btn-sm">
                Send to Allocation Point
              </button>
              <button onClick={handleReturnInventory} disabled={actionLoading} className="btn-secondary btn-sm">
                Return to Inventory
              </button>
              <button onClick={() => setShowChangeStatus(true)} disabled={actionLoading} className="btn-warning btn-sm">
                Change Status
              </button>
              <button onClick={() => setShowSendAnotherDP(true)} disabled={actionLoading} className="btn-secondary btn-sm">
                Send to Another DP
              </button>
            </>
          )}
          <button onClick={() => setSelected([])} className="btn-secondary btn-sm ml-auto">Clear</button>
        </div>
      )}

      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={devices} loading={loading}
          selectable selected={selected}
          onSelect={(sid, checked) => setSelected(prev => checked ? [...prev, sid] : prev.filter(x => x !== sid))}
          onSelectAll={checked => setSelected(checked ? devices.map(d => d.id) : [])}
          emptyMessage="No devices at this distribution point." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination
            meta={meta}
            onPageChange={setPage}
            onPerPageChange={(nextPerPage) => { setPerPage(nextPerPage); setPage(1); }}
            allowAll
          />
        </div>
      </div>

      {/* Send to AP Modal */}
      <Modal isOpen={showSendAP} onClose={() => setShowSendAP(false)} title="Send to Allocation Point"
        footer={
          <>
            <button onClick={() => setShowSendAP(false)} className="btn-secondary">Cancel</button>
            <button onClick={handleSendToAP} disabled={!selectedAP || actionLoading} className="btn-primary">
              {actionLoading ? 'Sending…' : 'Send'}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">Send <strong>{selected.length}</strong> device(s) to allocation point:</p>
          <div>
            <label className="label">Allocation Point <span className="text-red-500">*</span></label>
            <select className="input" value={selectedAP} onChange={e => setSelectedAP(e.target.value)}>
              <option value="">Select allocation point…</option>
              {aps.map(ap => <option key={ap.id} value={ap.id}>{ap.name} — {ap.location}</option>)}
            </select>
          </div>
        </div>
      </Modal>

      {/* Change Status Modal */}
      <Modal isOpen={showChangeStatus} onClose={() => setShowChangeStatus(false)} title="Change Device Status"
        footer={
          <>
            <button onClick={() => setShowChangeStatus(false)} className="btn-secondary">Cancel</button>
            <button onClick={handleChangeStatus} disabled={!newStatus || actionLoading} className="btn-primary">
              {actionLoading ? 'Updating…' : 'Apply'}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">Change status for <strong>{selected.length}</strong> device(s):</p>
          <div>
            <label className="label">New Status <span className="text-red-500">*</span></label>
            <select className="input" value={newStatus} onChange={e => setNewStatus(e.target.value)}>
              <option value="">Select status…</option>
              {['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST'].map(s =>
                <option key={s} value={s}>{s}</option>
              )}
            </select>
          </div>
        </div>
      </Modal>

      {/* Send to Another DP Modal */}
      <Modal isOpen={showSendAnotherDP} onClose={() => setShowSendAnotherDP(false)} title="Send to Another Distribution Point"
        footer={
          <>
            <button onClick={() => setShowSendAnotherDP(false)} className="btn-secondary">Cancel</button>
            <button onClick={handleSendAnotherDP} disabled={!selectedDP || actionLoading} className="btn-primary">
              {actionLoading ? 'Sending…' : 'Send'}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">Send <strong>{selected.length}</strong> device(s) to another distribution point:</p>
          <div>
            <label className="label">Target Distribution Point <span className="text-red-500">*</span></label>
            <select className="input" value={selectedDP} onChange={e => setSelectedDP(e.target.value)}>
              <option value="">Select distribution point…</option>
              {dps.filter(d => d.id != id).map(d => <option key={d.id} value={d.id}>{d.name} — {d.location}</option>)}
            </select>
          </div>
        </div>
      </Modal>
    </div>
  );
}
