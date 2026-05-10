import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../../services/api';
import { allocationService } from '../../services/allocationService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';

const DEVICE_STATUSES = ['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'RECEIVED'];
const STATUS_COLORS = {
  ONLINE:   { bg: '#dcfce7', text: '#166534' },
  OFFLINE:  { bg: '#fee2e2', text: '#991b1b' },
  DAMAGED:  { bg: '#ffedd5', text: '#9a3412' },
  FIXED:    { bg: '#f3e8ff', text: '#6b21a8' },
  LOST:     { bg: '#f3f4f6', text: '#374151' },
  RECEIVED: { bg: '#dbeafe', text: '#1e40af' },
};

const BLANK_RECEIPT = {
  receipt_number: '',
  date: new Date().toISOString().slice(0, 10),
  consignment_nature: 'CN',
  transaction_type: 'SAD',
  sad_number: '',
  route_id: '',
  long_route_id: '',
  moving_trucks: 1,
  billing_unit: '',
  destination_id: '',
  base_unit_charge_usd: '',
  exchange_rate_used: '',
  unit_charge_gmd: '',
  total_charge_gmd: '',
  agent_name: '',
  agent_phone: '',
  consignee_details: '',
  shipper_details: '',
  description_of_goods: '',
};

const BLANK_DISPATCH = {
  receipt_id: '', vehicle_number: '', regime_id: '', destination_id: '',
  route_id: '', long_route_id: '', manifest_date: '', agency: '',
  agent_contact: '', truck_number: '', driver_name: '', dispatch_type: 'Regular Dispatch',
  etd: '', eta: '', completion_rules: '1', container: '', consignee: '', goods: '',
  carrier: '', cargo_description: '', waybill_name: '',
  transaction_type: '', boe: '',
};

function fmtGMD(val) {
  if (val === '' || val === null || val === undefined) return '';
  return `D ${Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export default function DataEntryDetailPage() {
  const { id } = useParams();
  const { notify } = useNotification();

  const [ap, setAp]           = useState(null);
  const [devices, setDevices] = useState([]);
  const [receipts, setReceipts]       = useState([]);   // AP-scoped, all (for "View Receipts" modal)
  const [allReceipts, setAllReceipts] = useState([]);   // global, used > 0 (for dispatch dropdown)
  const [routes, setRoutes]   = useState([]);
  const [longRoutes, setLongRoutes] = useState([]);
  const [regimes, setRegimes] = useState([]);
  const [destinations, setDestinations] = useState([]);
  const [exchangeRate, setExchangeRate] = useState(74.07);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [selectedDevices, setSelectedDevices] = useState([]);

  // Modals
  const [showReceipts, setShowReceipts]     = useState(false);
  const [showNewReceipt, setShowNewReceipt] = useState(false);
  const [receiptSaving, setReceiptSaving]   = useState(false);
  const [receiptForm, setReceiptForm]       = useState(BLANK_RECEIPT);

  const [showDispatch, setShowDispatch]       = useState(false);
  const [dispatchSaving, setDispatchSaving]   = useState(false);
  const [dispatchForm, setDispatchForm]       = useState(BLANK_DISPATCH);

  const [showDispatchReport, setShowDispatchReport]         = useState(false);
  const [dispatchReportList, setDispatchReportList]         = useState([]);
  const [dispatchReportLoading, setDispatchReportLoading]   = useState(false);
  const [dispatchReportFilters, setDispatchReportFilters]   = useState({ search: '', from: '', to: '' });

  // ─── Load exchange rate from DB ───────────────────────────────────────────
  const loadExchangeRate = async () => {
    try {
      const { data } = await api.get('/settings/exchange-rate');
      setExchangeRate(data.data?.rate ?? 74.07);
    } catch { /* fallback stays 74.07 */ }
  };

  // ─── Load globally available receipts for dispatch dropdown ───────────────
  // No AP filter — shows ALL receipts with used > 0 across the system
  const loadAvailableReceipts = async () => {
    try {
      const { data } = await api.get('/receipts', { params: { available: 1, per_page: 500 } });
      setAllReceipts(data?.data || []);
    } catch { /* silent — dispatch dropdown stays empty */ }
  };

  // ─── Pricing auto-calculation ─────────────────────────────────────────────
  const calcPricing = (baseUsd, rate, trucks) => {
    const b = parseFloat(baseUsd) || 0;
    const r = parseFloat(rate) || 0;
    const t = parseInt(trucks) || 1;
    const unitGMD  = b * r;
    const totalGMD = unitGMD * t;
    return {
      base_unit_charge_usd: b,
      exchange_rate_used:   r,
      unit_charge_gmd:      unitGMD,
      total_charge_gmd:     totalGMD,
    };
  };

  const applyRoute = (routeId) => {
    const r = routes.find(x => String(x.id) === String(routeId));
    if (!r) return;
    const pricing = calcPricing(r.base_usd_amount, exchangeRate, receiptForm.moving_trucks);
    setReceiptForm(f => ({
      ...f,
      route_id:      routeId,
      long_route_id: '',            // mutually exclusive
      billing_unit:  'Short Route',
      ...pricing,
    }));
  };

  const applyLongRoute = (routeId) => {
    const r = longRoutes.find(x => String(x.id) === String(routeId));
    if (!r) return;
    const pricing = calcPricing(r.base_usd_amount, exchangeRate, receiptForm.moving_trucks);
    setReceiptForm(f => ({
      ...f,
      long_route_id: routeId,
      route_id:      '',            // mutually exclusive
      billing_unit:  'Long Route',
      ...pricing,
    }));
  };

  const applyMovingTrucks = (trucks) => {
    const t = parseInt(trucks) || 1;
    setReceiptForm(f => {
      const unitGMD  = parseFloat(f.unit_charge_gmd) || 0;
      const totalGMD = unitGMD * t;
      return { ...f, moving_trucks: t, total_charge_gmd: totalGMD };
    });
  };

  // ─── Data load ────────────────────────────────────────────────────────────
  const load = () => {
    setLoading(true);
    Promise.all([
      allocationService.get(id).catch(() => null),
      api.get('/devices', { params: { allocation_point_id: id, per_page: 200, status: statusFilter || undefined } }).catch(() => ({ data: { data: [] } })),
      api.get('/receipts', { params: { per_page: 500 } }).catch(() => ({ data: { data: [] } })),
      api.get('/routes').catch(() => ({ data: { data: [] } })),
      api.get('/long-routes').catch(() => ({ data: { data: [] } })),
      api.get('/regimes').catch(() => ({ data: { data: [] } })),
      api.get('/destinations').catch(() => ({ data: { data: [] } })),
    ]).then(([apData, devsRes, receiptsRes, routesRes, longRes, regimesRes, destRes]) => {
      setAp(apData);
      setDevices(devsRes.data?.data || []);
      setReceipts(receiptsRes.data?.data || []);
      setRoutes(routesRes.data?.data || []);
      setLongRoutes(longRes.data?.data || []);
      setRegimes(regimesRes.data?.data || []);
      setDestinations(destRes.data?.data || []);
    }).catch(() => {}).finally(() => setLoading(false));
  };

  useEffect(() => {
    loadExchangeRate();
    loadAvailableReceipts();
    load();
  }, [id, statusFilter]);

  // ─── Receipt create ───────────────────────────────────────────────────────
  const handleNewReceipt = async (e) => {
    e.preventDefault();
    if (!receiptForm.route_id && !receiptForm.long_route_id) {
      notify.error('At least one of Route or Long Route must be selected.');
      return;
    }
    setReceiptSaving(true);
    try {
      // Auto-generate receipt number if blank
      const rn = receiptForm.receipt_number.trim() ||
        `R-${new Date().toISOString().slice(0,10).replace(/-/g,'')}-${String(Math.floor(Math.random()*9999)+1).padStart(4,'0')}`;
      await api.post('/receipts', {
        ...receiptForm,
        receipt_number: rn,
        allocation_point_id: id,
        used: parseInt(receiptForm.moving_trucks) || 1,
      });
      notify.success('Receipt created');
      setShowNewReceipt(false);
      setReceiptForm({ ...BLANK_RECEIPT, date: new Date().toISOString().slice(0, 10) });
      load();
    } catch (err) { notify.error(err.response?.data?.message || err.message); }
    finally { setReceiptSaving(false); }
  };

  // ─── Receipt auto-fill on dispatch ───────────────────────────────────────
  const handleReceiptSelect = (receiptId) => {
    const r = allReceipts.find(rc => String(rc.id) === String(receiptId));
    if (!r) { setDispatchForm(f => ({ ...f, receipt_id: receiptId })); return; }
    const avail = parseInt(r.used) ?? 0;
    if (avail <= 0) {
      notify.error(`Receipt ${r.receipt_number} is fully used (0/${r.moving_trucks} remaining).`);
      setDispatchForm(f => ({ ...f, receipt_id: '' }));
      return;
    }
    notify.success(`Receipt selected — ${avail}/${r.moving_trucks} truck(s) available.`);
    setDispatchForm(f => ({
      ...f,
      receipt_id:       receiptId,
      transaction_type: r.transaction_type || 'SAD',
      boe:              r.sad_number || '',
      agent_contact:    r.agent_contact || r.agent_phone || '',
      destination_id:   r.destination_id ? String(r.destination_id) : f.destination_id,
      route_id:         r.route_id       ? String(r.route_id)       : f.route_id,
      long_route_id:    r.long_route_id  ? String(r.long_route_id)  : f.long_route_id,
    }));
  };

  // ─── Dispatch ─────────────────────────────────────────────────────────────
  const handleDispatch = async (e) => {
    e.preventDefault();
    if (!selectedDevices.length) { notify.error('Select at least one device to dispatch'); return; }

    const receivedIds = selectedDevices.filter(sid => {
      const d = devices.find(dev => dev.id === sid);
      return d?.status === 'RECEIVED';
    });
    if (receivedIds.length) {
      notify.error(`${receivedIds.length} selected device(s) have RECEIVED status and cannot be dispatched. Accept them at the Allocation Point first.`);
      return;
    }

    setDispatchSaving(true);
    try {
      const selectedReceipt = allReceipts.find(r => String(r.id) === String(dispatchForm.receipt_id));
      const selectedRegime  = regimes.find(r => String(r.id) === String(dispatchForm.regime_id));
      const selectedDest    = destinations.find(d => String(d.id) === String(dispatchForm.destination_id));

      const payload = {
        ...dispatchForm,
        allocation_point_id: id,
        status:              'PENDING',
        date:                new Date().toISOString(),
        boe:                 dispatchForm.boe || selectedReceipt?.sad_number || selectedReceipt?.receipt_number || '',
        sad_number:          selectedReceipt?.sad_number || '',
        regime:              selectedRegime?.name || dispatchForm.regime_id,
        destination:         selectedDest?.name  || dispatchForm.destination_id,
        transaction_type:    dispatchForm.transaction_type || selectedReceipt?.transaction_type || 'SAD',
      };

      await Promise.all(
        selectedDevices.map(deviceId =>
          api.post('/confirmed-affixed', { ...payload, device_id: deviceId })
        )
      );

      notify.success(`Dispatched ${selectedDevices.length} device(s) successfully`);
      setShowDispatch(false);
      setSelectedDevices([]);
      setDispatchForm(BLANK_DISPATCH);
      load();                    // reload devices list (dispatched devices disappear)
      loadAvailableReceipts();   // reload receipt counters (used decremented)
    } catch (err) { notify.error(err.response?.data?.message || err.message); }
    finally { setDispatchSaving(false); }
  };

  // ─── Dispatch report ──────────────────────────────────────────────────────
  const runDispatchReport = async (f = dispatchReportFilters) => {
    setDispatchReportLoading(true);
    try {
      const { data } = await api.get(`/reports/dispatch/${id}`, { params: f });
      setDispatchReportList(Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []));
    } catch (err) { notify.error(err.message); }
    finally { setDispatchReportLoading(false); }
  };
  const openDispatchReport = () => { setShowDispatchReport(true); runDispatchReport(); };
  const dispatchReportExportUrl = () => {
    const p = new URLSearchParams();
    if (dispatchReportFilters.search) p.set('search', dispatchReportFilters.search);
    if (dispatchReportFilters.from)   p.set('from',   dispatchReportFilters.from);
    if (dispatchReportFilters.to)     p.set('to',     dispatchReportFilters.to);
    return `/api/reports/dispatch/${id}?${p.toString()}&export=1`;
  };

  const filteredDevices = statusFilter ? devices.filter(d => d.status === statusFilter) : devices;
  const counts = DEVICE_STATUSES.reduce((acc, s) => { acc[s] = devices.filter(d => d.status === s).length; return acc; }, {});
  const receivedCount = counts['RECEIVED'] || 0;

  const deviceColumns = [
    { header: 'Device ID', key: 'device_id', render: v => <span className="font-mono font-semibold" style={{ color: '#1E2D7A' }}>{v}</span> },
    { header: 'Type',      key: 'device_type',   render: v => v || '—' },
    { header: 'Status',    key: 'status',         render: v => <StatusBadge status={v} /> },
    { header: 'SIM',       key: 'sim_number',     render: v => v || '—' },
    { header: 'Serial',    key: 'serial_number',  render: v => v || '—' },
  ];

  const selectedReceipt = allReceipts.find(r => String(r.id) === String(dispatchForm.receipt_id));

  // ─── Pricing display helpers ───────────────────────────────────────────────
  const hasRoutePricing = !!(receiptForm.route_id || receiptForm.long_route_id);

  return (
    <div>
      <PageHeader
        title={ap?.name || `Data Entry #${id}`}
        subtitle={ap?.location || ''}
        breadcrumbs={[{ label: 'Data Entry', path: '/data-entry' }, { label: ap?.name || id }]}
        actions={
          <div className="flex flex-wrap gap-2">
            <button onClick={() => { loadExchangeRate(); setShowDispatch(true); }} className="btn-warning">🚛 Dispatch</button>
            <button onClick={() => { loadExchangeRate(); setShowNewReceipt(true); }} className="btn-info">New Receipt</button>
            <button onClick={() => setShowReceipts(true)} className="btn-primary">Receipts</button>
            <button onClick={openDispatchReport} className="btn-success">Dispatch Report</button>
            <Link to={`/allocation/${id}`} className="btn-secondary">← Allocation Point</Link>
          </div>
        }
      />

      {/* RECEIVED devices warning */}
      {receivedCount > 0 && (
        <div className="mb-4 rounded-lg px-4 py-3 text-sm flex items-center gap-2"
          style={{ background: '#dbeafe', color: '#1e40af' }}>
          <span className="font-bold">ℹ</span>
          <span>
            <strong>{receivedCount}</strong> device(s) are in RECEIVED status and cannot be dispatched.
            Go to the <Link to={`/allocation/${id}`} className="underline font-semibold">Allocation Point</Link> to accept them first.
          </span>
        </div>
      )}

      {/* Status Filter Tabs */}
      <div className="flex flex-wrap gap-2 mb-4">
        <button onClick={() => setStatusFilter('')}
          className={`card-sm text-xs font-semibold cursor-pointer ${!statusFilter ? 'ring-2' : ''}`}
          style={{ borderColor: !statusFilter ? '#1E2D7A' : undefined }}>
          All ({devices.length})
        </button>
        {DEVICE_STATUSES.map(s => (
          <button key={s} onClick={() => setStatusFilter(statusFilter === s ? '' : s)}
            className={`card-sm cursor-pointer hover:shadow-md transition-all ${statusFilter === s ? 'ring-2' : ''}`}
            style={{ background: STATUS_COLORS[s]?.bg, borderColor: statusFilter === s ? '#1E2D7A' : STATUS_COLORS[s]?.bg }}>
            <p className="text-xs font-semibold" style={{ color: STATUS_COLORS[s]?.text }}>{s} ({counts[s]})</p>
          </button>
        ))}
      </div>

      {/* Selected devices dispatch bar */}
      {selectedDevices.length > 0 && (
        <div className="flex items-center gap-3 mb-3 rounded-xl px-4 py-3 border"
          style={{ background: '#eef1fb', borderColor: '#c7cef0' }}>
          <span className="text-sm font-semibold" style={{ color: '#1E2D7A' }}>
            {selectedDevices.length} device(s) selected
          </span>
          <button onClick={() => setShowDispatch(true)} className="btn-warning btn-sm">Dispatch Selected</button>
          <button onClick={() => setSelectedDevices([])} className="btn-secondary btn-sm ml-auto">Clear</button>
        </div>
      )}

      {/* Devices Table */}
      <div className="card p-0 overflow-hidden mb-6">
        <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-semibold text-gray-900">Devices at this Allocation Point</h3>
          <span className="text-xs text-gray-500">{filteredDevices.length} device(s)</span>
        </div>
        <DataTable columns={deviceColumns} data={filteredDevices} loading={loading}
          selectable selected={selectedDevices}
          onSelect={(did, checked) => setSelectedDevices(prev => checked ? [...prev, did] : prev.filter(x => x !== did))}
          onSelectAll={checked => setSelectedDevices(checked ? filteredDevices.filter(d => d.status !== 'RECEIVED').map(d => d.id) : [])}
          emptyMessage="No devices at this allocation point." />
      </div>

      {/* ═══ NEW RECEIPT MODAL ═══════════════════════════════════════════════ */}
      <Modal isOpen={showNewReceipt} onClose={() => setShowNewReceipt(false)}
        title="New Receipt" size="xl"
        footer={
          <>
            <button onClick={() => setShowNewReceipt(false)} className="btn-secondary">Cancel</button>
            <button form="receipt-form" type="submit" disabled={receiptSaving} className="btn-primary">
              {receiptSaving ? 'Saving…' : 'Create Receipt'}
            </button>
          </>
        }>
        <form id="receipt-form" onSubmit={handleNewReceipt} className="space-y-5">

          {/* ── Section 1: Basic Information ── */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-3 pb-1 border-b"
              style={{ color: '#1E2D7A' }}>1 — Basic Information</h4>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="label">Receipt Date <span className="text-red-500">*</span></label>
                <input required type="date" className="input" value={receiptForm.date}
                  onChange={e => setReceiptForm(f => ({ ...f, date: e.target.value }))} />
              </div>
              <div>
                <label className="label">Consignment Nature <span className="text-red-500">*</span></label>
                <select required className="input" value={receiptForm.consignment_nature}
                  onChange={e => setReceiptForm(f => ({ ...f, consignment_nature: e.target.value }))}>
                  <option value="CN">CN — Containers</option>
                  <option value="FT">FT — Fuel Tanker</option>
                  <option value="GC">GC — General Cargo</option>
                  <option value="OV">OV — Overland Vehicles</option>
                </select>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4 mt-3">
              <div>
                <label className="label">Transaction Type <span className="text-red-500">*</span></label>
                <select required className="input" value={receiptForm.transaction_type}
                  onChange={e => setReceiptForm(f => ({ ...f, transaction_type: e.target.value }))}>
                  <option value="SAD">SAD (Customs T1)</option>
                  <option value="TRUCK">TRUCK (Domestic)</option>
                </select>
              </div>
              <div>
                <label className="label">
                  {receiptForm.transaction_type === 'TRUCK' ? 'Truck Reference Number' : 'SAD Number'}
                  {' '}<span className="text-red-500">*</span>
                </label>
                <input required className="input" value={receiptForm.sad_number}
                  onChange={e => setReceiptForm(f => ({ ...f, sad_number: e.target.value }))}
                  placeholder={receiptForm.transaction_type === 'TRUCK' ? 'TRUCK-REF-001' : 'SAD-2026-00001'} />
              </div>
            </div>
          </div>

          {/* ── Section 2: Route Selection ── */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-3 pb-1 border-b"
              style={{ color: '#1E2D7A' }}>2 — Route Selection</h4>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="label">
                  Route (Short)
                  {receiptForm.route_id && <span className="ml-2 text-xs font-normal" style={{ color: '#085E37' }}>✓ selected</span>}
                </label>
                <select className="input" value={receiptForm.route_id}
                  onChange={e => e.target.value ? applyRoute(e.target.value) : setReceiptForm(f => ({ ...f, route_id: '', billing_unit: '', base_unit_charge_usd: '', exchange_rate_used: '', unit_charge_gmd: '', total_charge_gmd: '' }))}>
                  <option value="">Select short route…</option>
                  {routes.map(r => (
                    <option key={r.id} value={r.id}>
                      {r.name} {r.base_usd_amount ? `— $${r.base_usd_amount}` : ''}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="label">
                  Long Route
                  {receiptForm.long_route_id && <span className="ml-2 text-xs font-normal" style={{ color: '#085E37' }}>✓ selected</span>}
                </label>
                <select className="input" value={receiptForm.long_route_id}
                  onChange={e => e.target.value ? applyLongRoute(e.target.value) : setReceiptForm(f => ({ ...f, long_route_id: '', billing_unit: '', base_unit_charge_usd: '', exchange_rate_used: '', unit_charge_gmd: '', total_charge_gmd: '' }))}>
                  <option value="">Select long route…</option>
                  {longRoutes.map(r => (
                    <option key={r.id} value={r.id}>
                      {r.name} {r.base_usd_amount ? `— $${r.base_usd_amount}` : ''}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            <p className="text-xs text-amber-600 mt-1">* At least one of Route or Long Route is required. Selecting one clears the other.</p>

            <div className="grid grid-cols-2 gap-4 mt-3">
              <div>
                <label className="label">Billing Unit</label>
                <input disabled className="input bg-gray-50 text-gray-600 font-medium"
                  value={receiptForm.billing_unit || '—'}
                  readOnly placeholder="Auto-set on route selection" />
              </div>
              <div>
                <label className="label">Moving Trucks <span className="text-red-500">*</span></label>
                <input required type="number" min={1} max={1000} className="input"
                  value={receiptForm.moving_trucks}
                  onChange={e => applyMovingTrucks(e.target.value)} />
              </div>
            </div>
          </div>

          {/* ── Section 3: Location ── */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-3 pb-1 border-b"
              style={{ color: '#1E2D7A' }}>3 — Location</h4>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="label">Allocation Point</label>
                <input disabled className="input bg-gray-50 text-gray-600" value={ap?.name || id} readOnly />
              </div>
              <div>
                <label className="label">Destination</label>
                <select className="input" value={receiptForm.destination_id}
                  onChange={e => setReceiptForm(f => ({ ...f, destination_id: e.target.value }))}>
                  <option value="">Select destination…</option>
                  {destinations.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                </select>
              </div>
            </div>
          </div>

          {/* ── Section 4: Pricing (all read-only, auto-calculated) ── */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-3 pb-1 border-b"
              style={{ color: '#1E2D7A' }}>4 — Pricing Calculation</h4>

            {!hasRoutePricing && (
              <p className="text-sm text-gray-500 mb-3 italic">Select a route above to auto-calculate pricing.</p>
            )}

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="label">Base Unit Charge (USD)</label>
                <div className="input bg-gray-50 flex items-center gap-2">
                  <span className="text-gray-400 text-sm">$</span>
                  <span className={`font-semibold ${hasRoutePricing ? 'text-gray-900' : 'text-gray-400'}`}>
                    {hasRoutePricing ? Number(receiptForm.base_unit_charge_usd).toFixed(2) : '—'}
                  </span>
                  <span className="text-xs text-gray-400 ml-auto">from route</span>
                </div>
              </div>
              <div>
                <label className="label">Exchange Rate (GMD/USD)</label>
                <div className="input bg-gray-50 flex items-center gap-2">
                  <span className={`font-semibold ${hasRoutePricing ? 'text-gray-900' : 'text-gray-400'}`}>
                    {hasRoutePricing ? Number(receiptForm.exchange_rate_used).toFixed(4) : exchangeRate.toFixed(4)}
                  </span>
                  <span className="text-xs text-gray-400 ml-auto">system setting</span>
                </div>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4 mt-3">
              <div>
                <label className="label">Unit Charge (GMD)</label>
                <div className="input bg-gray-50 flex items-center">
                  <span className={`font-semibold ${hasRoutePricing ? '' : 'text-gray-400'}`}
                    style={{ color: hasRoutePricing ? '#1E2D7A' : undefined }}>
                    {hasRoutePricing ? fmtGMD(receiptForm.unit_charge_gmd) : '—'}
                  </span>
                  <span className="text-xs text-gray-400 ml-auto">base × rate</span>
                </div>
              </div>
              <div>
                <label className="label">Total Charge (GMD)</label>
                <div className="input font-bold flex items-center"
                  style={{ background: hasRoutePricing ? '#f0fdf4' : '#f9fafb', color: hasRoutePricing ? '#085E37' : '#6b7280' }}>
                  <span className="text-sm">
                    {hasRoutePricing ? fmtGMD(receiptForm.total_charge_gmd) : '—'}
                  </span>
                  <span className="text-xs font-normal text-gray-400 ml-auto">unit × trucks</span>
                </div>
              </div>
            </div>

            {hasRoutePricing && (
              <div className="mt-3 rounded-lg p-3 text-xs" style={{ background: '#f0fdf4', color: '#166534' }}>
                <strong>Formula:</strong> {' '}
                ${Number(receiptForm.base_unit_charge_usd).toFixed(2)} × {Number(receiptForm.exchange_rate_used).toFixed(4)} GMD/USD
                = {fmtGMD(receiptForm.unit_charge_gmd)} per truck
                × {receiptForm.moving_trucks} trucks
                = <strong>{fmtGMD(receiptForm.total_charge_gmd)}</strong>
              </div>
            )}
          </div>

          {/* ── Section 5: Agent & Consignment ── */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-3 pb-1 border-b"
              style={{ color: '#1E2D7A' }}>5 — Agent & Consignment Details</h4>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="label">Agent Name <span className="text-red-500">*</span></label>
                <input required className="input" value={receiptForm.agent_name}
                  onChange={e => setReceiptForm(f => ({ ...f, agent_name: e.target.value }))}
                  placeholder="Full agent name" maxLength={255} />
              </div>
              <div>
                <label className="label">Agent Phone <span className="text-red-500">*</span></label>
                <input required type="tel" className="input" value={receiptForm.agent_phone}
                  onChange={e => setReceiptForm(f => ({ ...f, agent_phone: e.target.value }))}
                  placeholder="+220 XXX XXXX" maxLength={20} />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4 mt-3">
              <div>
                <label className="label">Consignee Details <span className="text-red-500">*</span></label>
                <textarea required className="input" rows={3} value={receiptForm.consignee_details}
                  onChange={e => setReceiptForm(f => ({ ...f, consignee_details: e.target.value }))}
                  placeholder="Consignee name, address, contact…" maxLength={500} />
              </div>
              <div>
                <label className="label">Shipper Details</label>
                <textarea className="input" rows={3} value={receiptForm.shipper_details}
                  onChange={e => setReceiptForm(f => ({ ...f, shipper_details: e.target.value }))}
                  placeholder="Shipper name, address…" maxLength={500} />
              </div>
            </div>
            <div className="mt-3">
              <label className="label">Description of Goods <span className="text-red-500">*</span></label>
              <textarea required className="input" rows={2} value={receiptForm.description_of_goods}
                onChange={e => setReceiptForm(f => ({ ...f, description_of_goods: e.target.value }))}
                placeholder="Brief description of the goods being transported…" maxLength={1000} />
            </div>
          </div>

          {/* ── Section 6: System Generated ── */}
          <div className="rounded-lg p-3 text-xs" style={{ background: '#f8fafc' }}>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-2" style={{ color: '#1E2D7A' }}>
              6 — System Generated
            </h4>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-gray-500">Receipt Number</span>
                <p className="font-mono font-semibold text-gray-700">
                  {receiptForm.receipt_number || `R-${new Date().toISOString().slice(0,10).replace(/-/g,'')}-(auto)`}
                </p>
              </div>
              <div>
                <span className="text-gray-500">Available Usage Count</span>
                <p className="font-bold" style={{ color: '#1E2D7A' }}>{receiptForm.moving_trucks} (= moving trucks)</p>
              </div>
            </div>
          </div>
        </form>
      </Modal>

      {/* ═══ RECEIPTS LIST MODAL ═════════════════════════════════════════════ */}
      <Modal isOpen={showReceipts} onClose={() => setShowReceipts(false)}
        title={`All Receipts (${receipts.length})`} size="full">
        <div className="overflow-x-auto">
          <table className="min-w-full text-xs">
            <thead className="bg-gray-50">
              <tr>
                {['Receipt No.', 'Allocation Point', 'Date', 'SAD/Ref', 'Type', 'Route', 'Long Route',
                  'Moving Trucks', 'Used/Avail', 'Unit GMD', 'Total GMD', 'Agent', 'Destination'].map(h => (
                  <th key={h} className="px-3 py-2 text-left font-semibold text-gray-600 whitespace-nowrap">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-100">
              {receipts.length === 0 ? (
                <tr><td colSpan={13} className="text-center py-8 text-gray-400">No receipts found.</td></tr>
              ) : receipts.map(r => {
                const used = parseInt(r.used) ?? 0;
                const total = parseInt(r.moving_trucks) ?? 0;
                const usedColor = used === 0 ? '#dc2626' : used <= Math.ceil(total * 0.25) ? '#d97706' : '#085E37';
                return (
                  <tr key={r.id} className="hover:bg-gray-50">
                    <td className="px-3 py-2 font-mono font-semibold" style={{ color: '#1E2D7A' }}>{r.receipt_number}</td>
                    <td className="px-3 py-2 text-xs text-gray-600">{r.station_name || '—'}</td>
                    <td className="px-3 py-2 whitespace-nowrap">{r.date ? new Date(r.date).toLocaleDateString() : '—'}</td>
                    <td className="px-3 py-2 font-mono">{r.sad_number || '—'}</td>
                    <td className="px-3 py-2"><StatusBadge status={r.transaction_type || 'SAD'} /></td>
                    <td className="px-3 py-2">{r.route_name || '—'}</td>
                    <td className="px-3 py-2">{r.long_route_name || '—'}</td>
                    <td className="px-3 py-2 text-center">{r.moving_trucks ?? '—'}</td>
                    <td className="px-3 py-2 text-center font-semibold" style={{ color: usedColor }}>
                      {used}/{total}
                    </td>
                    <td className="px-3 py-2 text-right">{r.unit_charge_gmd ? fmtGMD(r.unit_charge_gmd) : '—'}</td>
                    <td className="px-3 py-2 text-right font-semibold">{r.total_charge_gmd ? fmtGMD(r.total_charge_gmd) : '—'}</td>
                    <td className="px-3 py-2">{r.agent_name || '—'}</td>
                    <td className="px-3 py-2">{r.destination_name || '—'}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </Modal>

      {/* ═══ DISPATCH MODAL ══════════════════════════════════════════════════ */}
      <Modal isOpen={showDispatch} onClose={() => setShowDispatch(false)}
        title="Dispatch Devices" size="xl"
        footer={
          <>
            <button onClick={() => setShowDispatch(false)} className="btn-secondary">Cancel</button>
            <button form="dispatch-form" type="submit" disabled={dispatchSaving} className="btn-warning">
              {dispatchSaving ? 'Dispatching…' : 'Confirm Dispatch'}
            </button>
          </>
        }>
        <form id="dispatch-form" onSubmit={handleDispatch} className="space-y-5">

          {/* Selected devices summary */}
          <div className="rounded-lg p-3 text-sm" style={{ background: '#eef1fb' }}>
            <p className="font-semibold mb-1" style={{ color: '#1E2D7A' }}>
              {selectedDevices.length} device(s) selected:
            </p>
            <p className="font-mono text-xs text-gray-600">
              {selectedDevices.map(sid => devices.find(d => d.id === sid)?.device_id).filter(Boolean).join(', ') || 'None'}
            </p>
          </div>

          {/* Receipt selection — auto-fills fields */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-2 pb-1 border-b" style={{ color: '#1E2D7A' }}>
              Receipt
            </h4>
            <div>
              <label className="label">Select Receipt <span className="text-red-500">*</span></label>
              <select required className="input" value={dispatchForm.receipt_id}
                onChange={e => handleReceiptSelect(e.target.value)}>
                <option value="">Select receipt… ({allReceipts.length} available globally)</option>
                {allReceipts.map(r => {
                  const used  = parseInt(r.used) ?? 0;
                  const total = parseInt(r.moving_trucks) ?? 0;
                  const color = used === 0 ? 'red' : used <= Math.ceil(total * 0.25) ? 'darkorange' : 'green';
                  return (
                    <option key={r.id} value={r.id} style={{ color }}>
                      [{r.used}/{r.moving_trucks}] {r.receipt_number} — {r.transaction_type} — {r.sad_number || 'No SAD'} — {r.station_name || 'Unknown AP'}
                    </option>
                  );
                })}
              </select>
              {selectedReceipt && (
                <div className="mt-2 text-xs rounded p-2 grid grid-cols-3 gap-2" style={{ background: '#f0fdf4', color: '#166534' }}>
                  <span>Type: <strong>{selectedReceipt.transaction_type}</strong></span>
                  <span>SAD/Ref: <strong>{selectedReceipt.sad_number || '—'}</strong></span>
                  <span>Available: <strong>{selectedReceipt.used ?? '?'}/{selectedReceipt.moving_trucks ?? '?'}</strong></span>
                  <span>Route: <strong>{selectedReceipt.route_name || selectedReceipt.long_route_name || '—'}</strong></span>
                  <span>Agent: <strong>{selectedReceipt.agent_name || '—'}</strong></span>
                  <span>Dest: <strong>{selectedReceipt.destination_name || '—'}</strong></span>
                </div>
              )}
            </div>
          </div>

          {/* Vehicle & Driver */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-2 pb-1 border-b" style={{ color: '#1E2D7A' }}>Vehicle & Driver</h4>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="label">Vehicle Number <span className="text-red-500">*</span></label>
                <input required className="input" value={dispatchForm.vehicle_number}
                  onChange={e => setDispatchForm(f => ({ ...f, vehicle_number: e.target.value }))} placeholder="BJL 1234" />
              </div>
              <div>
                <label className="label">Truck Number</label>
                <input className="input" value={dispatchForm.truck_number}
                  onChange={e => setDispatchForm(f => ({ ...f, truck_number: e.target.value }))} />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4 mt-3">
              <div>
                <label className="label">Driver Name</label>
                <input className="input" value={dispatchForm.driver_name}
                  onChange={e => setDispatchForm(f => ({ ...f, driver_name: e.target.value }))} />
              </div>
              <div>
                <label className="label">Agency</label>
                <input className="input" value={dispatchForm.agency}
                  onChange={e => setDispatchForm(f => ({ ...f, agency: e.target.value }))} />
              </div>
            </div>
          </div>

          {/* Route & Regime */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-2 pb-1 border-b" style={{ color: '#1E2D7A' }}>Route & Regime</h4>
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
            <div className="grid grid-cols-2 gap-4 mt-3">
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
          </div>

          {/* Timing */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-2 pb-1 border-b" style={{ color: '#1E2D7A' }}>Timing</h4>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="label">ETD (Est. Departure) <span className="text-red-500">*</span></label>
                <input required type="datetime-local" className="input" value={dispatchForm.etd}
                  onChange={e => setDispatchForm(f => ({ ...f, etd: e.target.value }))} />
              </div>
              <div>
                <label className="label">ETA (Est. Arrival) <span className="text-red-500">*</span></label>
                <input required type="datetime-local" className="input" value={dispatchForm.eta}
                  onChange={e => setDispatchForm(f => ({ ...f, eta: e.target.value }))} />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4 mt-3">
              <div>
                <label className="label">Manifest Date</label>
                <input type="datetime-local" className="input" value={dispatchForm.manifest_date}
                  onChange={e => setDispatchForm(f => ({ ...f, manifest_date: e.target.value }))} />
              </div>
              <div>
                <label className="label">Agent Contact</label>
                <input type="tel" className="input" value={dispatchForm.agent_contact}
                  onChange={e => setDispatchForm(f => ({ ...f, agent_contact: e.target.value }))} />
              </div>
            </div>
          </div>

          {/* Dispatch Settings */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-2 pb-1 border-b" style={{ color: '#1E2D7A' }}>Dispatch Settings</h4>
            <div className="grid grid-cols-2 gap-4">
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
            </div>
          </div>

          {/* Cargo Details */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-widest mb-2 pb-1 border-b" style={{ color: '#1E2D7A' }}>Cargo Details</h4>
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
                <label className="label">Carrier</label>
                <input className="input" value={dispatchForm.carrier}
                  onChange={e => setDispatchForm(f => ({ ...f, carrier: e.target.value }))} />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4 mt-3">
              <div>
                <label className="label">Goods</label>
                <input className="input" value={dispatchForm.goods}
                  onChange={e => setDispatchForm(f => ({ ...f, goods: e.target.value }))} />
              </div>
              <div>
                <label className="label">Trip / Waybill Name</label>
                <input className="input" value={dispatchForm.waybill_name}
                  onChange={e => setDispatchForm(f => ({ ...f, waybill_name: e.target.value }))} />
              </div>
            </div>
            <div className="mt-3">
              <label className="label">Cargo Description</label>
              <textarea className="input" rows={2} value={dispatchForm.cargo_description}
                onChange={e => setDispatchForm(f => ({ ...f, cargo_description: e.target.value }))} />
            </div>
          </div>
        </form>
      </Modal>

      {/* ═══ DISPATCH REPORT MODAL ═══════════════════════════════════════════ */}
      <Modal isOpen={showDispatchReport} onClose={() => setShowDispatchReport(false)}
        title={`Dispatch Report — ${ap?.name || id}`} size="full"
        footer={
          <div className="flex items-center justify-between w-full">
            <a href={dispatchReportExportUrl()} target="_blank" rel="noreferrer" className="btn-success btn-sm">Export CSV</a>
            <button onClick={() => setShowDispatchReport(false)} className="btn-secondary">Close</button>
          </div>
        }>
        <div className="space-y-4">
          <div className="flex flex-wrap gap-3 items-end p-3 bg-gray-50 rounded-lg">
            <div>
              <label className="label text-xs">Search</label>
              <input type="text" className="input input-sm w-44" placeholder="Device ID / BOE / Vehicle…"
                value={dispatchReportFilters.search}
                onChange={e => setDispatchReportFilters(f => ({ ...f, search: e.target.value }))} />
            </div>
            <div>
              <label className="label text-xs">From Date</label>
              <input type="date" className="input input-sm" value={dispatchReportFilters.from}
                onChange={e => setDispatchReportFilters(f => ({ ...f, from: e.target.value }))} />
            </div>
            <div>
              <label className="label text-xs">To Date</label>
              <input type="date" className="input input-sm" value={dispatchReportFilters.to}
                onChange={e => setDispatchReportFilters(f => ({ ...f, to: e.target.value }))} />
            </div>
            <button onClick={() => runDispatchReport(dispatchReportFilters)} className="btn-primary btn-sm">Apply</button>
            <button onClick={() => { const r = { search: '', from: '', to: '' }; setDispatchReportFilters(r); runDispatchReport(r); }}
              className="btn-secondary btn-sm">Reset</button>
          </div>

          {dispatchReportLoading ? (
            <div className="py-8 text-center text-gray-400">Loading report…</div>
          ) : dispatchReportList.length === 0 ? (
            <div className="py-8 text-center text-gray-400">No dispatch records found for the selected filters.</div>
          ) : (
            <div className="overflow-x-auto max-h-[60vh]">
              <p className="text-xs text-gray-500 mb-2">{dispatchReportList.length} record(s)</p>
              <table className="min-w-full text-xs">
                <thead className="bg-gray-50 sticky top-0">
                  <tr>
                    {['Date','Device ID','BOE','Vehicle','Regime','Route','Destination',
                      'ETD','ETA','Manifest Date','Agency','Driver','Status'].map(h => (
                      <th key={h} className="px-2 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {dispatchReportList.map((r, i) => (
                    <tr key={r.id ?? i} className="hover:bg-gray-50">
                      <td className="px-2 py-1.5 whitespace-nowrap">{r.date ? new Date(r.date).toLocaleDateString() : '—'}</td>
                      <td className="px-2 py-1.5 font-mono font-semibold" style={{ color: '#1E2D7A' }}>{r.device_identifier || r.device_id || '—'}</td>
                      <td className="px-2 py-1.5 font-mono">{r.boe || '—'}</td>
                      <td className="px-2 py-1.5">{r.vehicle_number || '—'}</td>
                      <td className="px-2 py-1.5">{r.regime || '—'}</td>
                      <td className="px-2 py-1.5">{r.route || r.route_name || '—'}</td>
                      <td className="px-2 py-1.5">{r.destination || r.destination_name || '—'}</td>
                      <td className="px-2 py-1.5 whitespace-nowrap">{r.etd ? new Date(r.etd).toLocaleString() : '—'}</td>
                      <td className="px-2 py-1.5 whitespace-nowrap">{r.eta ? new Date(r.eta).toLocaleString() : '—'}</td>
                      <td className="px-2 py-1.5 whitespace-nowrap">{r.manifest_date ? new Date(r.manifest_date).toLocaleDateString() : <span className="text-amber-500">Pending</span>}</td>
                      <td className="px-2 py-1.5">{r.agency || '—'}</td>
                      <td className="px-2 py-1.5">{r.driver_name || '—'}</td>
                      <td className="px-2 py-1.5"><StatusBadge status={r.status || 'PENDING'} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </Modal>
    </div>
  );
}
