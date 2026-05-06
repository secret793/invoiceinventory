import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { dataEntryService } from '../../services/dataEntryService';
import { allocationService } from '../../services/allocationService';
import api from '../../services/api';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';
import AssignToAgentForm from '../../components/dataentry/AssignToAgentForm';

const DEVICE_STATUSES = ['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'RECEIVED'];

export default function DataEntryDetailPage() {
  const { id } = useParams();
  const { notify }      = useNotification();
  const [ap, setAp]     = useState(null);
  const [devices, setDevices]       = useState([]);
  const [assignments, setAssignments] = useState([]);
  const [receipts, setReceipts]     = useState([]);
  const [routes, setRoutes]         = useState([]);
  const [longRoutes, setLongRoutes] = useState([]);
  const [regimes, setRegimes]       = useState([]);
  const [destinations, setDestinations] = useState([]);
  const [loading, setLoading]       = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [selectedDevices, setSelectedDevices] = useState([]);

  const [showAssign, setShowAssign]     = useState(false);
  const [assigning, setAssigning]       = useState(false);
  const [returnModal, setReturnModal]   = useState(null);
  const [returnNote, setReturnNote]     = useState('');
  const [returning, setReturning]       = useState(false);

  const [showReceipts, setShowReceipts]   = useState(false);
  const [showNewReceipt, setShowNewReceipt] = useState(false);
  const [receiptSaving, setReceiptSaving]  = useState(false);
  const [receiptForm, setReceiptForm]      = useState({
    receipt_number: '', sad_number: '', transaction_type: 'SAD',
    route_id: '', long_route_id: '', quantity: 1, destination_id: '',
  });

  const [showDispatch, setShowDispatch]   = useState(false);
  const [dispatchSaving, setDispatchSaving] = useState(false);
  const [dispatchForm, setDispatchForm]   = useState({
    receipt_id: '', vehicle_number: '', regime_id: '', destination_id: '',
    route_id: '', long_route_id: '', manifest_date: '', agency: '',
    agent_contact: '', truck_number: '', driver_name: '', dispatch_type: 'Regular Dispatch',
    etd: '', eta: '', completion_rules: '1', container: '', consignee: '', goods: '', carrier: '',
    cargo_description: '', waybill_name: '',
  });

  const load = () => {
    setLoading(true);
    Promise.all([
      allocationService.get(id).catch(() => null),
      api.get('/devices', { params: { allocation_point_id: id, per_page: 100, status: statusFilter || undefined } }).catch(() => ({ data: { data: [] } })),
      dataEntryService.list({ allocation_point_id: id, per_page: 50 }).catch(() => ({ data: [] })),
      api.get('/receipts', { params: { allocation_point_id: id, per_page: 100 } }).catch(() => ({ data: { data: [] } })),
      api.get('/routes').catch(() => ({ data: { data: [] } })),
      api.get('/long-routes').catch(() => ({ data: { data: [] } })),
      api.get('/regimes').catch(() => ({ data: { data: [] } })),
      api.get('/destinations').catch(() => ({ data: { data: [] } })),
    ]).then(([apData, devsRes, assignRes, receiptsRes, routesRes, longRes, regimesRes, destRes]) => {
      setAp(apData);
      setDevices(devsRes.data?.data || []);
      setAssignments(assignRes.data || []);
      setReceipts(receiptsRes.data?.data || []);
      setRoutes(routesRes.data?.data || []);
      setLongRoutes(longRes.data?.data || []);
      setRegimes(regimesRes.data?.data || []);
      setDestinations(destRes.data?.data || []);
    }).catch(() => {}).finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, [id, statusFilter]);

  const handleAssign = async (form) => {
    setAssigning(true);
    try {
      const newest = assignments[0];
      if (!newest) { notify.error('No assignment found for this allocation point'); return; }
      await dataEntryService.assignToAgent(newest.id, form);
      notify.success('Assigned to agent successfully');
      setShowAssign(false); load();
    } catch (e) { notify.error(e.message); }
    finally { setAssigning(false); }
  };

  const handleReturn = async () => {
    if (!returnModal) return;
    setReturning(true);
    try {
      await dataEntryService.returnDevice(returnModal.id, returnNote);
      notify.success('Device returned'); setReturnModal(null); setReturnNote(''); load();
    } catch (e) { notify.error(e.message); }
    finally { setReturning(false); }
  };

  const handleNewReceipt = async (e) => {
    e.preventDefault();
    if (!receiptForm.route_id && !receiptForm.long_route_id) {
      notify.error('Please select either Route or Long Route before submitting.');
      return;
    }
    setReceiptSaving(true);
    try {
      await api.post('/receipts', { ...receiptForm, allocation_point_id: id });
      notify.success('Receipt created');
      setShowNewReceipt(false);
      setReceiptForm({ receipt_number: '', sad_number: '', transaction_type: 'SAD', route_id: '', long_route_id: '', quantity: 1, destination_id: '' });
      load();
    } catch (e) { notify.error(e.message); }
    finally { setReceiptSaving(false); }
  };

  const handleDispatch = async (e) => {
    e.preventDefault();
    if (!selectedDevices.length) { notify.error('Select at least one device to dispatch'); return; }
    setDispatchSaving(true);
    try {
      const selectedReceipt  = receipts.find(r => String(r.id) === String(dispatchForm.receipt_id));
      const selectedRegime   = regimes.find(r => String(r.id) === String(dispatchForm.regime_id));
      const selectedDest     = destinations.find(d => String(d.id) === String(dispatchForm.destination_id));

      const payload = {
        ...dispatchForm,
        allocation_point_id: id,
        status:              'PENDING',
        date:                new Date().toISOString(),
        boe:                 selectedReceipt?.sad_number || selectedReceipt?.receipt_number || '',
        sad_number:          selectedReceipt?.sad_number || '',
        regime:              selectedRegime?.name  || dispatchForm.regime_id,
        destination:         selectedDest?.name    || dispatchForm.destination_id,
        transaction_type:    selectedReceipt?.transaction_type || 'SAD',
      };

      await Promise.all(
        selectedDevices.map(deviceId =>
          api.post('/confirmed-affixed', { ...payload, device_id: deviceId })
        )
      );

      notify.success(`Dispatched ${selectedDevices.length} device(s) successfully`);
      setShowDispatch(false); setSelectedDevices([]); load();
    } catch (err) { notify.error(err.response?.data?.message || err.message); }
    finally { setDispatchSaving(false); }
  };

  const STATUS_COLORS = {
    ONLINE: { bg: '#dcfce7', text: '#166534' }, OFFLINE: { bg: '#fee2e2', text: '#991b1b' },
    DAMAGED: { bg: '#ffedd5', text: '#9a3412' }, FIXED: { bg: '#f3e8ff', text: '#6b21a8' },
    LOST: { bg: '#f3f4f6', text: '#374151' }, RECEIVED: { bg: '#dbeafe', text: '#1e40af' },
  };

  const deviceColumns = [
    { header: 'Device ID', key: 'device_id', render: v => <span className="font-mono font-semibold">{v}</span> },
    { header: 'Type',      key: 'device_type', render: v => v || '—' },
    { header: 'Status',    key: 'status', render: v => <StatusBadge status={v} /> },
    { header: 'SIM',       key: 'sim_number', render: v => v || '—' },
  ];

  const assignColumns = [
    { header: '#',      key: 'id' },
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

  const counts = DEVICE_STATUSES.reduce((acc, s) => {
    acc[s] = devices.filter(d => d.status === s).length;
    return acc;
  }, {});

  return (
    <div>
      <PageHeader
        title={ap?.name || `Data Entry #${id}`}
        subtitle={ap?.location || ''}
        breadcrumbs={[{ label: 'Data Entry', path: '/data-entry' }, { label: ap?.name || id }]}
        actions={
          <div className="flex flex-wrap gap-2">
            <button onClick={() => setShowNewReceipt(true)} className="btn-info">
              🎫 New Receipt
            </button>
            <button onClick={() => setShowDispatch(true)} className="btn-warning">
              🚛 Dispatch
            </button>
            <button onClick={() => setShowReceipts(true)} className="btn-primary">
              📋 Receipts
            </button>
            <a href={`/api/reports/dispatch/${id}`} target="_blank" className="btn-success">
              📊 Dispatch Report
            </a>
          </div>
        } />

      {/* Status Filter Tabs */}
      <div className="flex flex-wrap gap-2 mb-5">
        <button onClick={() => setStatusFilter('')}
          className={`card-sm text-xs font-semibold cursor-pointer ${!statusFilter ? 'ring-2' : ''}`}
          style={{ borderColor: !statusFilter ? '#1E2D7A' : undefined }}>
          All ({devices.length})
        </button>
        {DEVICE_STATUSES.map(s => (
          <button key={s} onClick={() => setStatusFilter(statusFilter === s ? '' : s)}
            className={`card-sm cursor-pointer hover:shadow-md transition-all ${statusFilter === s ? 'ring-2' : ''}`}
            style={{
              background: STATUS_COLORS[s]?.bg,
              borderColor: statusFilter === s ? '#1E2D7A' : STATUS_COLORS[s]?.bg,
            }}>
            <p className="text-xs font-semibold" style={{ color: STATUS_COLORS[s]?.text }}>{s} ({counts[s]})</p>
          </button>
        ))}
      </div>

      {/* Devices Table */}
      {selectedDevices.length > 0 && (
        <div className="flex items-center gap-3 mb-3 rounded-xl px-4 py-3 border"
          style={{ background: '#eef1fb', borderColor: '#c7cef0' }}>
          <span className="text-sm font-semibold" style={{ color: '#1E2D7A' }}>{selectedDevices.length} device(s) selected for dispatch</span>
          <button onClick={() => setShowDispatch(true)} className="btn-warning btn-sm">🚛 Dispatch</button>
          <button onClick={() => setSelectedDevices([])} className="btn-secondary btn-sm ml-auto">Clear</button>
        </div>
      )}

      <div className="card p-0 overflow-hidden mb-6">
        <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-semibold text-gray-900">Devices at this Allocation Point</h3>
          <span className="text-xs text-gray-500">{devices.length} device(s)</span>
        </div>
        <DataTable columns={deviceColumns} data={devices} loading={loading}
          selectable selected={selectedDevices}
          onSelect={(did, checked) => setSelectedDevices(prev => checked ? [...prev, did] : prev.filter(x => x !== did))}
          onSelectAll={checked => setSelectedDevices(checked ? devices.map(d => d.id) : [])}
          emptyMessage="No devices at this allocation point." />
      </div>

      {/* Assignments */}
      <div className="card p-0 overflow-hidden">
        <div className="px-4 py-3 border-b border-gray-100">
          <h3 className="font-semibold text-gray-900">Data Entry Assignments</h3>
        </div>
        <DataTable columns={assignColumns} data={assignments} loading={loading} emptyMessage="No assignments yet." />
      </div>

      {/* Assign Modal */}
      <Modal isOpen={showAssign} onClose={() => setShowAssign(false)} title="Assign Device to Agent" size="lg">
        <AssignToAgentForm assignment={{ ...assignments[0], allocation_point_name: ap?.name }}
          onSubmit={handleAssign} loading={assigning} onCancel={() => setShowAssign(false)} />
      </Modal>

      {/* Return Modal */}
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

      {/* New Receipt Modal */}
      <Modal isOpen={showNewReceipt} onClose={() => setShowNewReceipt(false)} title="New Receipt" size="lg"
        footer={
          <>
            <button onClick={() => setShowNewReceipt(false)} className="btn-secondary">Cancel</button>
            <button form="receipt-form" type="submit" disabled={receiptSaving} className="btn-primary">
              {receiptSaving ? 'Saving…' : 'Create Receipt'}
            </button>
          </>
        }>
        <form id="receipt-form" onSubmit={handleNewReceipt} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Receipt Number <span className="text-red-500">*</span></label>
              <input required className="input" value={receiptForm.receipt_number}
                onChange={e => setReceiptForm(f => ({ ...f, receipt_number: e.target.value }))} />
            </div>
            <div>
              <label className="label">SAD Number</label>
              <input className="input" value={receiptForm.sad_number}
                onChange={e => setReceiptForm(f => ({ ...f, sad_number: e.target.value }))} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Transaction Type</label>
              <select className="input" value={receiptForm.transaction_type}
                onChange={e => setReceiptForm(f => ({ ...f, transaction_type: e.target.value }))}>
                <option value="SAD">SAD</option>
                <option value="TRUCK">TRUCK</option>
              </select>
            </div>
            <div>
              <label className="label">Quantity (Used)</label>
              <input type="number" min={1} className="input" value={receiptForm.quantity}
                onChange={e => setReceiptForm(f => ({ ...f, quantity: e.target.value }))} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Route</label>
              <select className="input" value={receiptForm.route_id}
                onChange={e => setReceiptForm(f => ({ ...f, route_id: e.target.value }))}>
                <option value="">Select route…</option>
                {routes.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
              </select>
            </div>
            <div>
              <label className="label">Long Route</label>
              <select className="input" value={receiptForm.long_route_id}
                onChange={e => setReceiptForm(f => ({ ...f, long_route_id: e.target.value }))}>
                <option value="">Select long route…</option>
                {longRoutes.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
              </select>
            </div>
          </div>
          <div>
            <label className="label">Destination</label>
            <select className="input" value={receiptForm.destination_id}
              onChange={e => setReceiptForm(f => ({ ...f, destination_id: e.target.value }))}>
              <option value="">Select destination…</option>
              {destinations.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
            </select>
          </div>
          <p className="text-xs text-amber-600">* At least one of Route or Long Route must be selected.</p>
        </form>
      </Modal>

      {/* Dispatch Modal */}
      <Modal isOpen={showDispatch} onClose={() => setShowDispatch(false)} title="Dispatch" size="xl"
        footer={
          <>
            <button onClick={() => setShowDispatch(false)} className="btn-secondary">Cancel</button>
            <button form="dispatch-form" type="submit" disabled={dispatchSaving} className="btn-warning">
              {dispatchSaving ? 'Dispatching…' : 'Confirm Dispatch'}
            </button>
          </>
        }>
        <form id="dispatch-form" onSubmit={handleDispatch} className="space-y-4">
          <div className="bg-blue-50 rounded-lg p-3 text-sm">
            <p className="font-medium text-blue-800">Selected Devices ({selectedDevices.length}):</p>
            <p className="text-blue-600 font-mono text-xs mt-1">
              {selectedDevices.map(sid => devices.find(d => d.id === sid)?.device_id).filter(Boolean).join(', ') || 'None selected'}
            </p>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Receipt <span className="text-red-500">*</span></label>
              <select required className="input" value={dispatchForm.receipt_id}
                onChange={e => setDispatchForm(f => ({ ...f, receipt_id: e.target.value }))}>
                <option value="">Select receipt…</option>
                {receipts.map(r => <option key={r.id} value={r.id}>{r.receipt_number} — {r.sad_number || r.transaction_type}</option>)}
              </select>
            </div>
            <div>
              <label className="label">Vehicle Number <span className="text-red-500">*</span></label>
              <input required className="input" value={dispatchForm.vehicle_number}
                onChange={e => setDispatchForm(f => ({ ...f, vehicle_number: e.target.value }))}
                placeholder="e.g. BJL 1234" />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Regime <span className="text-red-500">*</span></label>
              <select required className="input" value={dispatchForm.regime_id}
                onChange={e => setDispatchForm(f => ({ ...f, regime_id: e.target.value }))}>
                <option value="">Select regime…</option>
                {regimes.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
              </select>
            </div>
            <div>
              <label className="label">Destination <span className="text-red-500">*</span></label>
              <select required className="input" value={dispatchForm.destination_id}
                onChange={e => setDispatchForm(f => ({ ...f, destination_id: e.target.value }))}>
                <option value="">Select destination…</option>
                {destinations.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
              </select>
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Route</label>
              <select className="input" value={dispatchForm.route_id}
                onChange={e => setDispatchForm(f => ({ ...f, route_id: e.target.value }))}>
                <option value="">Select route…</option>
                {routes.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
              </select>
            </div>
            <div>
              <label className="label">Long Route</label>
              <select className="input" value={dispatchForm.long_route_id}
                onChange={e => setDispatchForm(f => ({ ...f, long_route_id: e.target.value }))}>
                <option value="">Select long route…</option>
                {longRoutes.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
              </select>
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Estimated Departure (ETD) <span className="text-red-500">*</span></label>
              <input required type="datetime-local" className="input" value={dispatchForm.etd}
                onChange={e => setDispatchForm(f => ({ ...f, etd: e.target.value }))} />
            </div>
            <div>
              <label className="label">Estimated Arrival (ETA) <span className="text-red-500">*</span></label>
              <input required type="datetime-local" className="input" value={dispatchForm.eta}
                onChange={e => setDispatchForm(f => ({ ...f, eta: e.target.value }))} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Manifest Date</label>
              <input type="datetime-local" className="input" value={dispatchForm.manifest_date}
                onChange={e => setDispatchForm(f => ({ ...f, manifest_date: e.target.value }))} />
            </div>
            <div>
              <label className="label">Dispatch Type <span className="text-red-500">*</span></label>
              <select required className="input" value={dispatchForm.dispatch_type}
                onChange={e => setDispatchForm(f => ({ ...f, dispatch_type: e.target.value }))}>
                <option>Regular Dispatch</option>
                <option>Receipt of Payment</option>
                <option>Planned Dispatch</option>
                <option>Analysis starts as scheduled</option>
              </select>
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Completion Rules <span className="text-red-500">*</span></label>
              <select required className="input" value={dispatchForm.completion_rules}
                onChange={e => setDispatchForm(f => ({ ...f, completion_rules: e.target.value }))}>
                <option value="1">1 — Complete (Leave destination)</option>
                <option value="2">2 — Complete (Reach destination)</option>
                <option value="3">3 — Complete (Unlock in destination)</option>
                <option value="4">4 — Complete (Return starting point)</option>
              </select>
            </div>
            <div>
              <label className="label">Driver Name</label>
              <input className="input" value={dispatchForm.driver_name}
                onChange={e => setDispatchForm(f => ({ ...f, driver_name: e.target.value }))}
                placeholder="Driver name…" />
            </div>
          </div>
          <div className="grid grid-cols-3 gap-4">
            <div>
              <label className="label">Agency</label>
              <input className="input" value={dispatchForm.agency}
                onChange={e => setDispatchForm(f => ({ ...f, agency: e.target.value }))} />
            </div>
            <div>
              <label className="label">Agent Contact</label>
              <input type="tel" className="input" value={dispatchForm.agent_contact}
                onChange={e => setDispatchForm(f => ({ ...f, agent_contact: e.target.value }))} />
            </div>
            <div>
              <label className="label">Truck Number</label>
              <input className="input" value={dispatchForm.truck_number}
                onChange={e => setDispatchForm(f => ({ ...f, truck_number: e.target.value }))} />
            </div>
          </div>
          <div className="grid grid-cols-3 gap-4">
            <div>
              <label className="label">Container</label>
              <input className="input" value={dispatchForm.container}
                onChange={e => setDispatchForm(f => ({ ...f, container: e.target.value }))} />
            </div>
            <div>
              <label className="label">Consignee</label>
              <input className="input" value={dispatchForm.consignee}
                onChange={e => setDispatchForm(f => ({ ...f, consignee: e.target.value }))} />
            </div>
            <div>
              <label className="label">Goods</label>
              <input className="input" value={dispatchForm.goods}
                onChange={e => setDispatchForm(f => ({ ...f, goods: e.target.value }))} />
            </div>
          </div>
          <div>
            <label className="label">Cargo Description</label>
            <textarea className="input" rows={2} value={dispatchForm.cargo_description}
              onChange={e => setDispatchForm(f => ({ ...f, cargo_description: e.target.value }))}
              placeholder="Cargo description…" />
          </div>
          <div>
            <label className="label">Trip / Waybill Name</label>
            <input className="input" value={dispatchForm.waybill_name}
              onChange={e => setDispatchForm(f => ({ ...f, waybill_name: e.target.value }))} />
          </div>
        </form>
      </Modal>

      {/* Receipts Modal */}
      <Modal isOpen={showReceipts} onClose={() => setShowReceipts(false)} title="Receipts" size="xl">
        <div className="space-y-4">
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Receipt No.</th><th>SAD No.</th><th>Trans. Type</th>
                  <th>Route</th><th>Destination</th><th>Qty</th>
                </tr>
              </thead>
              <tbody className="bg-white">
                {receipts.length === 0 ? (
                  <tr><td colSpan={6} className="text-center py-8 text-gray-400">No receipts found.</td></tr>
                ) : receipts.map(r => (
                  <tr key={r.id}>
                    <td className="font-mono font-semibold">{r.receipt_number}</td>
                    <td className="font-mono">{r.sad_number || '—'}</td>
                    <td><StatusBadge status={r.transaction_type} /></td>
                    <td>{r.route_name || r.long_route_name || '—'}</td>
                    <td>{r.destination_name || '—'}</td>
                    <td>{r.quantity || '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </Modal>
    </div>
  );
}
