import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../../services/api';
import { allocationService } from '../../services/allocationService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';

const STATUSES = ['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'RECEIVED'];
const STATUS_COLORS = {
  ONLINE:   { bg: '#dcfce7', text: '#166534' },
  OFFLINE:  { bg: '#fee2e2', text: '#991b1b' },
  DAMAGED:  { bg: '#ffedd5', text: '#9a3412' },
  FIXED:    { bg: '#f3e8ff', text: '#6b21a8' },
  LOST:     { bg: '#f3f4f6', text: '#374151' },
  RECEIVED: { bg: '#dbeafe', text: '#1e40af' },
};

export default function AllocationDetailPage() {
  const { id } = useParams();
  const { notify } = useNotification();
  const [ap, setAp]           = useState(null);
  const [devices, setDevices] = useState([]);
  const [counts, setCounts]   = useState({});
  const [meta, setMeta]       = useState({});
  const [loading, setLoading] = useState(true);
  const [page, setPage]       = useState(1);
  const [perPage, setPerPage] = useState(25);
  const [statusFilter, setStatusFilter] = useState('');
  const [selected, setSelected]         = useState([]);
  const [allAPs, setAllAPs]             = useState([]);
  const [showSendAP, setShowSendAP]               = useState(false);
  const [showChangeStatus, setShowChangeStatus]   = useState(false);
  const [targetAP, setTargetAP]       = useState('');
  const [newStatus, setNewStatus]     = useState('');
  const [actionLoading, setActionLoading] = useState(false);

  const loadAP = useCallback(() => {
    allocationService.get(id).then(setAp).catch(() => {});
  }, [id]);

  const loadDevices = useCallback(() => {
    setLoading(true);
    allocationService.devices(id, { page, per_page: perPage, status: statusFilter || undefined }).then(r => {
      setDevices(r.data || []); setMeta(r.meta || {});
    }).catch(() => {}).finally(() => setLoading(false));
  }, [id, page, perPage, statusFilter]);

  const loadCounts = useCallback(() => {
    api.get(`/allocation-points/${id}/status-counts`).then(r => setCounts(r.data.data || {})).catch(() => {});
  }, [id]);

  useEffect(() => {
    loadAP(); loadCounts();
    allocationService.list().then(setAllAPs).catch(() => {});
  }, [id]);

  useEffect(() => { loadDevices(); }, [id, page, perPage, statusFilter]);

  const doAction = async (endpoint, body, successMsg) => {
    setActionLoading(true);
    try {
      await api.post(`/allocation-points/${id}/${endpoint}`, body);
      notify.success(successMsg);
      setSelected([]); loadDevices(); loadCounts();
    } catch (e) { notify.error(e.response?.data?.message || e.message); }
    finally { setActionLoading(false); }
  };

  const handleCollect = () => {
    const receivedSelected = selected.filter(sid => {
      const d = devices.find(dev => dev.id === sid);
      return d?.status === 'RECEIVED';
    });
    if (!receivedSelected.length) { notify.error('Select at least one RECEIVED device to accept'); return; }
    doAction('collect', { device_ids: receivedSelected }, `${receivedSelected.length} device(s) accepted — status restored to ONLINE`);
  };

  const handleSendToAP = async () => {
    if (!targetAP) { notify.error('Select an allocation point'); return; }
    await doAction('send-to-ap', { device_ids: selected, allocation_point_id: targetAP }, 'Devices sent to allocation point');
    setShowSendAP(false); setTargetAP('');
  };

  const handleReturnInventory = () =>
    doAction('return-inventory', { device_ids: selected }, 'Devices returned to inventory');

  const handleChangeStatus = async () => {
    if (!newStatus) { notify.error('Select a status'); return; }
    await doAction('change-status', { device_ids: selected, status: newStatus }, 'Status updated');
    setShowChangeStatus(false); setNewStatus('');
  };

  const hasReceivedSelected = selected.some(sid => {
    const d = devices.find(dev => dev.id === sid);
    return d?.status === 'RECEIVED';
  });

  const columns = [
    { header: 'Device ID',     key: 'device_id',     render: v => <span className="font-mono font-semibold" style={{ color: '#1E2D7A' }}>{v}</span> },
    { header: 'Type',          key: 'device_type',   render: v => v || '—' },
    { header: 'Serial',        key: 'serial_number', render: v => v || '—' },
    { header: 'SIM',           key: 'sim_number',    render: v => v || '—' },
    { header: 'Status',        key: 'status',        render: v => <StatusBadge status={v} /> },
    { header: 'Date Received', key: 'date_received', render: v => v ? new Date(v).toLocaleDateString() : '—' },
  ];

  const total = Object.values(counts).reduce((a, b) => a + b, 0);

  return (
    <div>
      <PageHeader
        title={ap?.name || 'Allocation Point'}
        subtitle={ap?.location || ''}
        breadcrumbs={[{ label: 'Allocation Points', path: '/allocation' }, { label: ap?.name || id }]}
        actions={
          <Link to={`/data-entry/${id}`} className="btn-primary">Data Entry →</Link>
        }
      />

      {/* Status Count Tabs */}
      <div className="flex flex-wrap gap-3 mb-5">
        <button onClick={() => { setStatusFilter(''); setPage(1); }}
          className={`card-sm flex items-center gap-2 cursor-pointer hover:shadow-md ${!statusFilter ? 'ring-2' : ''}`}
          style={{ borderColor: !statusFilter ? '#1E2D7A' : undefined }}>
          <p className="text-xs text-gray-500">All</p>
          <p className="text-lg font-bold" style={{ color: '#1E2D7A' }}>{total}</p>
        </button>
        {STATUSES.map(s => (
          <button key={s} onClick={() => { setStatusFilter(statusFilter === s ? '' : s); setPage(1); }}
            className={`card-sm flex items-center gap-2 cursor-pointer hover:shadow-md ${statusFilter === s ? 'ring-2' : ''}`}
            style={{ borderColor: statusFilter === s ? '#1E2D7A' : undefined }}>
            <div style={{ background: STATUS_COLORS[s]?.bg, borderRadius: 4, padding: '2px 6px' }}>
              <p className="text-xs font-semibold" style={{ color: STATUS_COLORS[s]?.text }}>{s}</p>
            </div>
            <p className="text-lg font-bold text-gray-900">{counts[s] || 0}</p>
          </button>
        ))}
      </div>

      {/* Bulk Actions */}
      {selected.length > 0 && (
        <div className="flex flex-wrap items-center gap-2 mb-4 rounded-xl px-4 py-3 border"
          style={{ background: '#eef1fb', borderColor: '#c7cef0' }}>
          <span className="text-sm font-semibold mr-2" style={{ color: '#1E2D7A' }}>
            {selected.length} selected
          </span>

          {/* Accept Devices — only shown when RECEIVED devices are selected */}
          {hasReceivedSelected && (
            <button onClick={handleCollect} disabled={actionLoading}
              className="btn-sm font-semibold text-white rounded-lg px-3 py-1.5"
              style={{ background: '#085E37' }}>
              {actionLoading ? 'Accepting…' : '✓ Accept Devices'}
            </button>
          )}

          <button onClick={() => setShowSendAP(true)} disabled={actionLoading} className="btn-primary btn-sm">
            Send to Allocation Point
          </button>
          <button onClick={handleReturnInventory} disabled={actionLoading} className="btn-secondary btn-sm">
            Return to Inventory
          </button>
          <button onClick={() => setShowChangeStatus(true)} disabled={actionLoading} className="btn-warning btn-sm">
            Change Status
          </button>
          <button onClick={() => setSelected([])} className="btn-secondary btn-sm ml-auto">Clear</button>
        </div>
      )}

      {/* RECEIVED devices info banner */}
      {(counts['RECEIVED'] || 0) > 0 && (
        <div className="mb-4 rounded-lg px-4 py-3 text-sm flex items-center gap-2"
          style={{ background: '#dbeafe', color: '#1e40af' }}>
          <span className="font-bold">ℹ</span>
          <span>
            <strong>{counts['RECEIVED']}</strong> device(s) arrived with RECEIVED status.
            Select them and click <strong>Accept Devices</strong> to restore their status and make them available for dispatch.
          </span>
        </div>
      )}

      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={devices} loading={loading}
          selectable selected={selected}
          onSelect={(sid, checked) => setSelected(prev => checked ? [...prev, sid] : prev.filter(x => x !== sid))}
          onSelectAll={checked => setSelected(checked ? devices.map(d => d.id) : [])}
          emptyMessage="No devices at this allocation point." />
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
            <button onClick={handleSendToAP} disabled={!targetAP || actionLoading} className="btn-primary">
              {actionLoading ? 'Sending…' : 'Send'}
            </button>
          </>
        }>
        <div className="space-y-3">
          <p className="text-sm text-gray-600">Send <strong>{selected.length}</strong> device(s) to another allocation point:</p>
          <div>
            <label className="label">Target Allocation Point <span className="text-red-500">*</span></label>
            <select className="input" value={targetAP} onChange={e => setTargetAP(e.target.value)}>
              <option value="">Select allocation point…</option>
              {allAPs.filter(a => a.id != id).map(a => (
                <option key={a.id} value={a.id}>{a.name} — {a.location}</option>
              ))}
            </select>
          </div>
          <p className="text-xs text-amber-600">Note: RECEIVED devices cannot be sent to another AP. They must be Accepted first.</p>
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
          <p className="text-xs text-amber-600">Note: RECEIVED devices are excluded from status change. Accept them first.</p>
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
    </div>
  );
}
