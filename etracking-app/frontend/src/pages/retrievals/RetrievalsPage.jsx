/* @refresh reset */
import React, { useState, useEffect } from 'react';
import { useRetrievals } from '../../hooks/useRetrievals';
import { retrievalService } from '../../services/retrievalService';
import { distributionService } from '../../services/distributionService';
import { useNotification } from '../../contexts/NotificationContext';
import { useAuth } from '../../contexts/AuthContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';
import Modal from '../../components/common/Modal';

const fmtDate  = (v) => v ? new Date(v).toLocaleDateString('en-GB') : '—';
const fmtMoney = (v) => parseFloat(v) > 0 ? `GMD ${Number(v).toLocaleString()}` : '—';
const isSADType = (t) => ['SAD', 'T1'].includes((t || '').toUpperCase());

function Field({ label, value, highlight }) {
  return (
    <div>
      <p className="text-xs text-gray-500 mb-0.5">{label}</p>
      <p className={`text-sm font-medium ${highlight ? 'text-red-700' : 'text-gray-800'}`}>{value || '—'}</p>
    </div>
  );
}

function FiltersBar({ onFilter }) {
  const [f, setF] = useState({
    retrieval_status: '', payment_status: '', overstay_min: '', from: '', to: '', search: '',
  });
  const apply = (next) => { setF(next); onFilter(next); };
  const clear  = () => apply({ retrieval_status: '', payment_status: '', overstay_min: '', from: '', to: '', search: '' });

  return (
    <div className="card p-3 mb-4 flex flex-wrap gap-3 items-end">
      <input className="input input-sm w-48" placeholder="Search BOE / SAD / Vehicle / Device…"
        value={f.search} onChange={e => apply({ ...f, search: e.target.value })} />
      <select className="input input-sm w-40" value={f.retrieval_status}
        onChange={e => apply({ ...f, retrieval_status: e.target.value })}>
        <option value="">All Retrieval Status</option>
        <option value="NOT_RETRIEVED">Not Retrieved</option>
        <option value="RETRIEVED">Retrieved</option>
        <option value="RETURNED">Returned</option>
        <option value="OVERDUE">Overdue</option>
      </select>
      <select className="input input-sm w-36" value={f.payment_status}
        onChange={e => apply({ ...f, payment_status: e.target.value })}>
        <option value="">All Payment</option>
        <option value="PP">Pending Pmt</option>
        <option value="PD">Paid</option>
        <option value="WAIVED">Waived</option>
      </select>
      <div className="flex items-center gap-1">
        <label className="text-xs text-gray-500 whitespace-nowrap">Overstay ≥</label>
        <input type="number" min="0" className="input input-sm w-16" placeholder="0"
          value={f.overstay_min} onChange={e => apply({ ...f, overstay_min: e.target.value })} />
        <span className="text-xs text-gray-400">days</span>
      </div>
      <div className="flex items-center gap-1">
        <label className="text-xs text-gray-500">From</label>
        <input type="date" className="input input-sm" value={f.from}
          onChange={e => apply({ ...f, from: e.target.value })} />
      </div>
      <div className="flex items-center gap-1">
        <label className="text-xs text-gray-500">To</label>
        <input type="date" className="input input-sm" value={f.to}
          onChange={e => apply({ ...f, to: e.target.value })} />
      </div>
      <button className="btn-secondary btn-sm" onClick={clear}>Clear</button>
    </div>
  );
}

export default function RetrievalsPage() {
  const { retrievals, meta, loading, fetch, changePage, changeFilters } = useRetrievals();
  const { notify }    = useNotification();
  const { hasRole, user }   = useAuth();

  const [dps, setDps] = useState([]);
  useEffect(() => { distributionService.list().then(setDps).catch(() => {}); }, []);

  const isSuperAdmin  = hasRole('Super Admin');
  const isFinance     = hasRole(['Finance Officer', 'Super Admin']);
  const isFinanceOnly = hasRole('Finance Officer') && !isSuperAdmin;
  const canRetrieve   = hasRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer']);

  const [busy, setBusy] = useState(false);

  /* ── Retrieve Device ──────────────────────────────────────────────────── */
  const [retrieveRow,  setRetrieveRow]  = useState(null);
  const [retrieveForm, setRetrieveForm] = useState({ t1_validation_ref: '' });
  const [t1Check, setT1Check] = useState({ loading: false, isLast: false });

  useEffect(() => {
    if (!retrieveRow) { setT1Check({ loading: false, isLast: false }); return; }
    if (!isSADType(retrieveRow.transaction_type)) { setT1Check({ loading: false, isLast: false }); return; }
    setT1Check({ loading: true, isLast: false });
    retrievalService.checkLastDevice(retrieveRow.id)
      .then(d => setT1Check({ loading: false, isLast: !!d?.is_last_device }))
      .catch(() => setT1Check({ loading: false, isLast: false }));
  }, [retrieveRow?.id]);

  const handleRetrieve = async () => {
    setBusy(true);
    try {
      await retrievalService.retrieve(retrieveRow.id, retrieveForm);
      notify.success('Device retrieved successfully.');
      setRetrieveRow(null);
      fetch();
    } catch (e) { notify.error(e.message); }
    finally { setBusy(false); }
  };

  /* ── Return to Outstation ─────────────────────────────────────────────── */
  const [outstationRow,  setOutstationRow]  = useState(null);
  const [outstationForm, setOutstationForm] = useState({ distribution_point_id: '', archive_reason: '' });

  const handleReturnOutstation = async () => {
    if (!outstationForm.distribution_point_id) { notify.error('Select a distribution point.'); return; }
    setBusy(true);
    try {
      await retrievalService.returnOutstation(outstationRow.id, outstationForm);
      notify.success('Device returned to outstation.');
      setOutstationRow(null);
      fetch();
    } catch (e) { notify.error(e.message); }
    finally { setBusy(false); }
  };

  /* ── Generate Overdue Bill ────────────────────────────────────────────── */
  const [billRow, setBillRow]             = useState(null);
  const [billConsignee, setBillConsignee] = useState('');

  const openBillModal = (row) => {
    setBillRow(row);
    setBillConsignee(row.consignee || row.agency || '');
  };

  const handleGenerateBill = async () => {
    setBusy(true);
    try {
      const res = await retrievalService.generateInvoice(billRow.id, { consignee: billConsignee });
      notify.success(`Overstay bill generated — ${fmtMoney(res?.overstay_amount || res?.total_amount || 0)}`);
      setBillRow(null);
      fetch();
    } catch (e) { notify.error(e.message); }
    finally { setBusy(false); }
  };

  /* ── Waiver ───────────────────────────────────────────────────────────── */
  const [waiverRow, setWaiverRow]       = useState(null);
  const [waiverReason, setWaiverReason] = useState('');

  const handleWaiver = async () => {
    if (waiverReason.trim().length < 10) {
      notify.error('Waiver reason must be at least 10 characters.');
      return;
    }
    setBusy(true);
    try {
      await retrievalService.waiver(waiverRow.id, { reason: waiverReason });
      notify.success('Overstay fee waived.');
      setWaiverRow(null); setWaiverReason('');
      fetch();
    } catch (e) { notify.error(e.message); }
    finally { setBusy(false); }
  };

  /* ── Approve Payment ──────────────────────────────────────────────────── */
  const [payRow,  setPayRow]  = useState(null);
  const [payForm, setPayForm] = useState({ receipt_number: '', finance_notes: '' });

  const handleApprovePayment = async () => {
    if (!payForm.receipt_number.trim()) { notify.error('Receipt number is required.'); return; }
    setBusy(true);
    try {
      await retrievalService.approvePayment(payRow.id, payForm);
      notify.success('Payment approved successfully.');
      setPayRow(null); setPayForm({ receipt_number: '', finance_notes: '' });
      fetch();
    } catch (e) { notify.error(e.message); }
    finally { setBusy(false); }
  };

  /* ── Manual Overstay Days ─────────────────────────────────────────────── */
  const [manualRow, setManualRow]   = useState(null);
  const [manualDays, setManualDays] = useState('');

  const handleManualOverstay = async () => {
    setBusy(true);
    try {
      await retrievalService.manualOverstay(manualRow.id, { overstay_days: Math.max(0, parseInt(manualDays, 10) || 0) });
      notify.success('Overstay days updated.');
      setManualRow(null); setManualDays('');
      fetch();
    } catch (e) { notify.error(e.message); }
    finally { setBusy(false); }
  };

  /* ── Overstay Devices Modal ───────────────────────────────────────────── */
  const [overstayOpen, setOverstayOpen]       = useState(false);
  const [overstayList, setOverstayList]       = useState([]);
  const [overstayLoading, setOverstayLoading] = useState(false);

  const openOverstayModal = async () => {
    setOverstayOpen(true); setOverstayLoading(true);
    try {
      const data = await retrievalService.overstayDevices();
      setOverstayList(Array.isArray(data) ? data : []);
    } catch (e) { notify.error(e.message); }
    finally { setOverstayLoading(false); }
  };

  /* ── Retrieval Report Modal ───────────────────────────────────────────── */
  const [reportOpen, setReportOpen]       = useState(false);
  const [reportList, setReportList]       = useState([]);
  const [reportLoading, setReportLoading] = useState(false);
  const [reportFilters, setReportFilters] = useState({ from: '', to: '', retrieval_status: '' });

  const runReport = async (f = reportFilters) => {
    setReportLoading(true);
    try {
      const data = await retrievalService.report(f);
      setReportList(Array.isArray(data) ? data : []);
    } catch (e) { notify.error(e.message); }
    finally { setReportLoading(false); }
  };

  const openReport = () => { setReportOpen(true); runReport(); };

  /* ── Table Columns ────────────────────────────────────────────────────── */
  const columns = [
    {
      header: 'Affixing Date', key: 'affixing_date',
      render: v => <span className="text-xs whitespace-nowrap">{fmtDate(v)}</span>
    },
    {
      header: 'Device ID', key: 'device_identifier',
      render: v => <span className="font-mono font-semibold text-xs" style={{ color: '#1E2D7A' }}>{v || '—'}</span>
    },
    {
      header: 'BOE', key: 'boe',
      render: v => <span className="font-mono text-xs">{v || '—'}</span>
    },
    {
      header: 'SAD No.', key: 'sad_number',
      render: v => <span className="font-mono text-xs">{v || '—'}</span>
    },
    {
      header: 'Type', key: 'transaction_type',
      render: v => v ? <StatusBadge status={isSADType(v) ? 'SAD' : 'TRUCK'} /> : '—'
    },
    {
      header: 'T1 Ref', key: 't1_validation_ref',
      render: v => <span className="text-xs text-gray-500">{v || '—'}</span>
    },
    {
      header: 'Vehicle', key: 'vehicle_number',
      render: v => <span className="text-xs">{v || '—'}</span>
    },
    {
      header: 'Regime', key: 'regime',
      render: v => <span className="text-xs">{v || '—'}</span>
    },
    {
      header: 'Station', key: 'allocation_point_name',
      render: v => <span className="text-xs">{v || '—'}</span>
    },
    {
      header: 'Destination', key: 'destination_name',
      render: v => <span className="text-xs">{v || '—'}</span>
    },
    {
      header: 'Overstay', key: 'overstay_days',
      render: (v, row) => {
        const days   = parseInt(v) || 0;
        const waived = !!(row.is_waived) || row.payment_status === 'WAIVED';
        if (waived)   return <span className="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">Waived</span>;
        if (days > 0) return <span className="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">{days}d overdue</span>;
        return <span className="inline-flex px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">On time</span>;
      }
    },
    {
      header: 'Amount', key: 'overstay_amount',
      render: v => parseFloat(v) > 0
        ? <span className="text-xs font-semibold text-red-700">{fmtMoney(v)}</span>
        : <span className="text-xs text-gray-400">—</span>
    },
    {
      header: 'Payment', key: 'payment_status',
      render: v => v ? <StatusBadge status={v} /> : <span className="text-xs text-gray-400">No bill</span>
    },
    {
      header: 'Retrieval', key: 'retrieval_status',
      render: v => <StatusBadge status={v || 'NOT_RETRIEVED'} />
    },
    {
      header: 'Actions', key: 'id',
      render: (_, row) => {
        const days     = parseInt(row.overstay_days) || 0;
        const pay      = row.payment_status;
        const ret      = row.retrieval_status || 'NOT_RETRIEVED';
        const isWaived = !!(row.is_waived) || pay === 'WAIVED';

        const canGenBill = !isWaived && days >= 1 && !pay;
        const canWaive   = isSuperAdmin && !isWaived && pay === 'PP';
        const canPay     = isFinance && pay === 'PP' && (parseFloat(row.overstay_amount) || 0) > 0;
        const canDL      = pay === 'PD' && !!row.finance_approval_date;
        const canBeRet   = isWaived || days < 1 || pay === 'PD';

        return (
          <div className="flex flex-wrap gap-1">
            {canRetrieve && ret === 'NOT_RETRIEVED' && canBeRet && (
              <button onClick={() => { setRetrieveRow(row); setRetrieveForm({ t1_validation_ref: '' }); }}
                className="btn-success btn-sm">Retrieve</button>
            )}
            {canRetrieve && ret === 'RETRIEVED' && row.transfer_status !== 'completed' && (
              <button onClick={() => { setOutstationRow(row); setOutstationForm({ distribution_point_id: '', archive_reason: '' }); }}
                className="btn-warning btn-sm">Return&nbsp;DP</button>
            )}
            {canGenBill && (
              <button onClick={() => openBillModal(row)} className="btn-danger btn-sm">Gen.&nbsp;Bill</button>
            )}
            {canWaive && (
              <button onClick={() => { setWaiverRow(row); setWaiverReason(''); }}
                className="btn-secondary btn-sm">Waive</button>
            )}
            {canPay && (
              <button onClick={() => { setPayRow(row); setPayForm({ receipt_number: '', finance_notes: '' }); }}
                className="btn-success btn-sm">Approve&nbsp;Pmt</button>
            )}
            {canDL && (
              <a href={retrievalService.downloadInvoiceUrl(row.id)} target="_blank" rel="noreferrer"
                className="btn-primary btn-sm">Invoice</a>
            )}
            {isSuperAdmin && (
              <button onClick={() => { setManualRow(row); setManualDays(String(row.overstay_days || 0)); }}
                className="btn-secondary btn-sm" title="Set overstay days manually">⚙</button>
            )}
          </div>
        );
      }
    },
  ];

  return (
    <div>
      <PageHeader
        title="Device Retrievals"
        subtitle="Track GPS device retrieval lifecycle, overstay billing and payment"
        actions={
          <div className="flex gap-2 flex-wrap">
            <button onClick={openOverstayModal} className="btn-danger">Overstay Devices</button>
            <button onClick={openReport}        className="btn-secondary">Report</button>
            <a href="/api/device-retrievals/export" target="_blank" className="btn-secondary">Export CSV</a>
          </div>
        }
      />

      {isFinanceOnly && (
        <div className="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
          Finance Officer view — showing records with 2+ overstay days only.
          Use <strong>Approve Payment</strong> on any <em>Pending Payment (PP)</em> record to confirm receipt.
        </div>
      )}

      <FiltersBar onFilter={changeFilters} />

      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={retrievals} loading={loading}
          emptyMessage="No retrieval records found." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination meta={meta} onPageChange={changePage} />
        </div>
      </div>

      {/* ── Retrieve Device ──────────────────────────────────────────────── */}
      <Modal isOpen={!!retrieveRow} onClose={() => setRetrieveRow(null)} title="Retrieve Device"
        footer={
          <>
            <button onClick={() => setRetrieveRow(null)} className="btn-secondary">Cancel</button>
            <button onClick={handleRetrieve} disabled={busy} className="btn-success">
              {busy ? 'Processing…' : 'Confirm Retrieval'}
            </button>
          </>
        }>
        {retrieveRow && (
          <div className="space-y-4">
            <div className="bg-gray-50 rounded-lg p-3 text-sm space-y-1">
              <p><span className="text-gray-500">BOE:</span> <strong className="font-mono">{retrieveRow.boe || '—'}</strong></p>
              <p><span className="text-gray-500">SAD:</span> <strong className="font-mono">{retrieveRow.sad_number || '—'}</strong></p>
              <p><span className="text-gray-500">Vehicle:</span> <strong>{retrieveRow.vehicle_number || '—'}</strong></p>
              <p><span className="text-gray-500">Device:</span> <strong className="font-mono">{retrieveRow.device_identifier || '—'}</strong></p>
              <p><span className="text-gray-500">Type:</span> <StatusBadge status={isSADType(retrieveRow.transaction_type) ? 'SAD' : (retrieveRow.transaction_type || '—')} /></p>
            </div>

            {isSADType(retrieveRow.transaction_type) && (
              <div>
                <label className="label">
                  T1 Validation Reference
                  {t1Check.loading && <span className="text-xs text-gray-400 ml-2">Checking…</span>}
                  {!t1Check.loading && t1Check.isLast && (
                    <span className="text-xs font-semibold text-red-600 ml-2">* REQUIRED — last device on this SAD receipt</span>
                  )}
                  {!t1Check.loading && !t1Check.isLast && (
                    <span className="text-xs text-gray-400 ml-2">(optional — not last device)</span>
                  )}
                </label>
                <input type="text" className={`input ${t1Check.isLast ? 'border-red-400 focus:border-red-500' : ''}`}
                  maxLength={100} placeholder="Enter T1 reference number"
                  value={retrieveForm.t1_validation_ref}
                  onChange={e => setRetrieveForm(f => ({ ...f, t1_validation_ref: e.target.value }))} />
              </div>
            )}

            {(parseInt(retrieveRow.overstay_days) || 0) > 0 && (
              <div className="rounded-lg p-3 border border-amber-200 bg-amber-50">
                <p className="text-sm text-amber-800">
                  Overstay: <strong>{retrieveRow.overstay_days} day(s)</strong> &mdash; Payment:&nbsp;
                  <StatusBadge status={retrieveRow.payment_status || 'PP'} />
                </p>
              </div>
            )}
          </div>
        )}
      </Modal>

      {/* ── Return to Outstation ─────────────────────────────────────────── */}
      <Modal isOpen={!!outstationRow} onClose={() => setOutstationRow(null)} title="Return to Outstation"
        footer={
          <>
            <button onClick={() => setOutstationRow(null)} className="btn-secondary">Cancel</button>
            <button onClick={handleReturnOutstation} disabled={busy || !outstationForm.distribution_point_id}
              className="btn-warning">
              {busy ? 'Processing…' : 'Return to Outstation'}
            </button>
          </>
        }>
        <div className="space-y-4">
          <div>
            <label className="label">Distribution Point <span className="text-red-500">*</span></label>
            <select className="input" value={outstationForm.distribution_point_id}
              onChange={e => setOutstationForm(f => ({ ...f, distribution_point_id: e.target.value }))}>
              <option value="">Select distribution point…</option>
              {dps.map(dp => (
                <option key={dp.id} value={dp.id}>{dp.name}{dp.location ? ` — ${dp.location}` : ''}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="label">Archive Reason <span className="text-xs text-gray-400">(optional)</span></label>
            <textarea className="input" rows={2} placeholder="Reason for returning to outstation…"
              value={outstationForm.archive_reason}
              onChange={e => setOutstationForm(f => ({ ...f, archive_reason: e.target.value }))} />
          </div>
          <div className="rounded-lg p-3 border border-amber-200 bg-amber-50 text-xs text-amber-700">
            Device will be set to PENDING at the selected DP. The retrieval record will be archived — invoices are preserved.
          </div>
        </div>
      </Modal>

      {/* ── Generate Overdue Bill ─────────────────────────────────────────── */}
      <Modal isOpen={!!billRow} onClose={() => setBillRow(null)} title="Generate Overdue Bill" size="lg"
        footer={
          <>
            <button onClick={() => setBillRow(null)} className="btn-secondary">Cancel</button>
            <button onClick={handleGenerateBill} disabled={busy} className="btn-danger">
              {busy ? 'Generating…' : 'Confirm & Generate Bill'}
            </button>
          </>
        }>
        {billRow && (
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-3 bg-gray-50 rounded-lg p-4 text-sm">
              <Field label="Reference #" value="OVR-{auto-generated}" />
              <Field label="BOE / SAD No." value={[billRow.boe, billRow.sad_number].filter(Boolean).join(' / ')} />
              <Field label="Device ID" value={billRow.device_identifier} />
              <Field label="Vehicle Number" value={billRow.vehicle_number} />
              <Field label="Driver Name" value={billRow.driver_name} />
              <Field label="Agent / Agency" value={billRow.agency} />
              <Field label="Allocation Point" value={billRow.allocation_point_name} />
              <Field label="Destination" value={billRow.destination_name} />
              <Field label="Route Type" value={billRow.long_route_id ? 'Long Route (2-day grace)' : 'Short Route (1-day grace)'} />
              <Field label="Regime" value={billRow.regime} />
              <Field label="Penalty Rate" value="GMD 1,000 / day" />
              <Field label="Affixing Date" value={fmtDate(billRow.affixing_date)} />
            </div>

            <div className="rounded-lg p-4 border border-red-200 bg-red-50 text-center">
              <p className="text-sm text-red-700 mb-1">Total Overstay Charge</p>
              <p className="text-3xl font-bold text-red-700">{billRow.overstay_days} day(s)</p>
              <p className="text-xl font-semibold text-red-800 mt-1">
                GMD {Number((billRow.overstay_days || 0) * 1000).toLocaleString()}
              </p>
              <p className="text-xs text-red-600 mt-1">Rate: GMD 1,000 per day</p>
            </div>

            <div>
              <label className="label">
                Consignee
                <span className="ml-2 text-xs font-semibold text-blue-600">Editable — verify before generating</span>
              </label>
              <input type="text" className="input border-blue-300 focus:border-blue-500"
                placeholder="Enter consignee name…"
                value={billConsignee}
                onChange={e => setBillConsignee(e.target.value)} />
            </div>
          </div>
        )}
      </Modal>

      {/* ── Waiver ───────────────────────────────────────────────────────── */}
      <Modal isOpen={!!waiverRow} onClose={() => { setWaiverRow(null); setWaiverReason(''); }} title="Admin Waiver"
        footer={
          <>
            <button onClick={() => { setWaiverRow(null); setWaiverReason(''); }} className="btn-secondary">Cancel</button>
            <button onClick={handleWaiver}
              disabled={busy || waiverReason.trim().length < 10}
              className="btn-danger">
              {busy ? 'Waiving…' : 'Confirm Waiver'}
            </button>
          </>
        }>
        {waiverRow && (
          <div className="space-y-4">
            <div className="rounded-lg p-3 border border-red-200 bg-red-50 text-sm">
              <p className="text-red-700">
                Waiving overstay for BOE: <strong className="font-mono">{waiverRow.boe}</strong>
              </p>
              <p className="text-red-700 mt-1">
                <strong>{waiverRow.overstay_days} day(s)</strong> — {fmtMoney(waiverRow.overstay_amount)} will be zeroed out.
              </p>
            </div>
            <div>
              <label className="label">
                Waiver Reason <span className="text-red-500">*</span>
                <span className="ml-2 text-xs text-gray-400">
                  {waiverReason.trim().length}/10 min chars
                  {waiverReason.trim().length >= 10 && ' ✓'}
                </span>
              </label>
              <textarea className="input" rows={3} placeholder="Enter reason for waiving overstay fees (min 10 characters)…"
                value={waiverReason} onChange={e => setWaiverReason(e.target.value)} />
            </div>
            <p className="text-xs text-gray-400">This action is permanent. A waiver record will be logged for audit.</p>
          </div>
        )}
      </Modal>

      {/* ── Approve Payment ──────────────────────────────────────────────── */}
      <Modal isOpen={!!payRow} onClose={() => { setPayRow(null); setPayForm({ receipt_number: '', finance_notes: '' }); }}
        title="Approve Payment"
        footer={
          <>
            <button onClick={() => { setPayRow(null); setPayForm({ receipt_number: '', finance_notes: '' }); }} className="btn-secondary">Cancel</button>
            <button onClick={handleApprovePayment} disabled={busy || !payForm.receipt_number.trim()} className="btn-success">
              {busy ? 'Approving…' : 'Approve Payment'}
            </button>
          </>
        }>
        {payRow && (
          <div className="space-y-4">
            <div className="bg-gray-50 rounded-lg p-3 text-sm space-y-1">
              <p><span className="text-gray-500">BOE:</span> <strong className="font-mono">{payRow.boe || '—'}</strong></p>
              <p><span className="text-gray-500">Vehicle:</span> <strong>{payRow.vehicle_number || '—'}</strong></p>
              <p><span className="text-gray-500">Overstay Days:</span> <strong>{payRow.overstay_days}</strong></p>
              <p><span className="text-gray-500">Amount Due:</span> <strong className="text-red-700">{fmtMoney(payRow.overstay_amount)}</strong></p>
            </div>
            <div>
              <label className="label">Receipt Number <span className="text-red-500">*</span></label>
              <input type="text" className="input" placeholder="e.g. RCT-2024-0001"
                value={payForm.receipt_number}
                onChange={e => setPayForm(f => ({ ...f, receipt_number: e.target.value }))} />
            </div>
            <div>
              <label className="label">Finance Notes <span className="text-xs text-gray-400">(optional)</span></label>
              <textarea className="input" rows={2} placeholder="Any notes about this payment…"
                value={payForm.finance_notes}
                onChange={e => setPayForm(f => ({ ...f, finance_notes: e.target.value }))} />
            </div>
          </div>
        )}
      </Modal>

      {/* ── Manual Overstay Days ─────────────────────────────────────────── */}
      <Modal isOpen={!!manualRow} onClose={() => { setManualRow(null); setManualDays(''); }}
        title="Set Manual Overstay Days" size="sm"
        footer={
          <>
            <button onClick={() => { setManualRow(null); setManualDays(''); }} className="btn-secondary">Cancel</button>
            <button onClick={handleManualOverstay} disabled={busy} className="btn-primary">
              {busy ? 'Saving…' : 'Update Overstay Days'}
            </button>
          </>
        }>
        {manualRow && (
          <div className="space-y-4">
            <div className="bg-gray-50 rounded-lg p-3 text-sm space-y-1">
              <p><span className="text-gray-500">BOE:</span> <strong className="font-mono">{manualRow.boe || '—'}</strong></p>
              <p><span className="text-gray-500">Current overstay:</span> <strong>{manualRow.overstay_days || 0} day(s)</strong></p>
            </div>
            <div>
              <label className="label">New Overstay Days</label>
              <input type="number" min="0" className="input" value={manualDays}
                onChange={e => setManualDays(e.target.value)} />
              {manualDays !== '' && (
                <p className="text-xs text-gray-500 mt-1">
                  Amount: GMD {Number(Math.max(0, parseInt(manualDays || 0)) * 1000).toLocaleString()}
                </p>
              )}
            </div>
          </div>
        )}
      </Modal>

      {/* ── Overstay Devices Modal ────────────────────────────────────────── */}
      <Modal isOpen={overstayOpen} onClose={() => setOverstayOpen(false)} title="Overstay Devices" size="xl"
        footer={<button onClick={() => setOverstayOpen(false)} className="btn-secondary">Close</button>}>
        {overstayLoading ? (
          <div className="py-8 text-center text-gray-400">Loading…</div>
        ) : overstayList.length === 0 ? (
          <div className="py-8 text-center text-gray-400">No overstay devices found.</div>
        ) : (
          <div className="overflow-x-auto">
            <p className="text-sm text-gray-500 mb-3">{overstayList.length} device(s) with active overstay</p>
            <table className="min-w-full text-xs">
              <thead>
                <tr className="bg-gray-50">
                  {['BOE', 'Device ID', 'Vehicle', 'Station', 'Destination', 'Days', 'Amount', 'Payment', 'Retrieval'].map(h => (
                    <th key={h} className="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {overstayList.map(r => (
                  <tr key={r.id} className="hover:bg-gray-50">
                    <td className="px-3 py-2 font-mono">{r.boe || '—'}</td>
                    <td className="px-3 py-2 font-mono font-semibold" style={{ color: '#1E2D7A' }}>{r.device_identifier || '—'}</td>
                    <td className="px-3 py-2">{r.vehicle_number || '—'}</td>
                    <td className="px-3 py-2">{r.allocation_point_name || '—'}</td>
                    <td className="px-3 py-2">{r.destination_name || '—'}</td>
                    <td className="px-3 py-2"><span className="font-bold text-red-700">{r.overstay_days}</span></td>
                    <td className="px-3 py-2 font-semibold text-red-700">{fmtMoney(r.overstay_amount)}</td>
                    <td className="px-3 py-2"><StatusBadge status={r.payment_status || 'PP'} /></td>
                    <td className="px-3 py-2"><StatusBadge status={r.retrieval_status || 'NOT_RETRIEVED'} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Modal>

      {/* ── Retrieval Report Modal ────────────────────────────────────────── */}
      <Modal isOpen={reportOpen} onClose={() => setReportOpen(false)} title="Device Retrieval Report" size="xl"
        footer={<button onClick={() => setReportOpen(false)} className="btn-secondary">Close</button>}>
        <div className="space-y-3">
          <div className="flex flex-wrap gap-2 items-end">
            <div>
              <label className="label">From</label>
              <input type="date" className="input input-sm" value={reportFilters.from}
                onChange={e => setReportFilters(f => ({ ...f, from: e.target.value }))} />
            </div>
            <div>
              <label className="label">To</label>
              <input type="date" className="input input-sm" value={reportFilters.to}
                onChange={e => setReportFilters(f => ({ ...f, to: e.target.value }))} />
            </div>
            <div>
              <label className="label">Retrieval Status</label>
              <select className="input input-sm w-40" value={reportFilters.retrieval_status}
                onChange={e => setReportFilters(f => ({ ...f, retrieval_status: e.target.value }))}>
                <option value="">All</option>
                <option value="NOT_RETRIEVED">Not Retrieved</option>
                <option value="RETRIEVED">Retrieved</option>
              </select>
            </div>
            <button className="btn-primary btn-sm" onClick={() => runReport()}>Run Report</button>
          </div>
          {reportLoading ? (
            <div className="py-8 text-center text-gray-400">Loading report…</div>
          ) : reportList.length === 0 ? (
            <div className="py-6 text-center text-gray-400">No records found for selected filters.</div>
          ) : (
            <div className="overflow-x-auto max-h-96">
              <p className="text-xs text-gray-500 mb-2">{reportList.length} record(s)</p>
              <table className="min-w-full text-xs">
                <thead>
                  <tr className="bg-gray-50 sticky top-0">
                    {['Date', 'Device', 'BOE', 'Vehicle', 'Station', 'Destination', 'Days', 'Amount', 'Payment', 'Retrieval'].map(h => (
                      <th key={h} className="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {reportList.map((r, i) => (
                    <tr key={r.id || i} className="hover:bg-gray-50">
                      <td className="px-3 py-2 whitespace-nowrap">{fmtDate(r.affixing_date)}</td>
                      <td className="px-3 py-2 font-mono font-semibold" style={{ color: '#1E2D7A' }}>{r.device_identifier || '—'}</td>
                      <td className="px-3 py-2 font-mono">{r.boe || '—'}</td>
                      <td className="px-3 py-2">{r.vehicle_number || '—'}</td>
                      <td className="px-3 py-2">{r.allocation_point_name || '—'}</td>
                      <td className="px-3 py-2">{r.destination_name || '—'}</td>
                      <td className="px-3 py-2 text-red-700 font-semibold">{r.overstay_days || 0}</td>
                      <td className="px-3 py-2 text-red-700">{fmtMoney(r.overstay_amount)}</td>
                      <td className="px-3 py-2"><StatusBadge status={r.payment_status || 'PP'} /></td>
                      <td className="px-3 py-2"><StatusBadge status={r.retrieval_status || 'NOT_RETRIEVED'} /></td>
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
