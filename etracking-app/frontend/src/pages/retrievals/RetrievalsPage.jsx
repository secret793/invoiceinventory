/* @refresh reset */
import React, { useState, useEffect, useCallback } from 'react';
import { downloadFile } from '../../utils/downloadFile';
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
const fmtDT    = (v) => v ? new Date(v).toLocaleString('en-GB') : '—';
const fmtMoney = (v) => parseFloat(v) > 0 ? `GMD ${Number(v).toLocaleString()}` : '—';
const isSADType = (t) => ['SAD', 'T1'].includes((t || '').toUpperCase());

function Field({ label, value, highlight, blue }) {
  return (
    <div>
      <p className="text-xs text-gray-500 mb-0.5">{label}</p>
      <p className={`text-sm font-medium ${highlight ? 'text-red-700' : blue ? 'text-blue-700 font-semibold' : 'text-gray-800'}`}>
        {value || '—'}
      </p>
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
  const { notify }  = useNotification();
  const { hasRole, user } = useAuth();

  const [dps, setDps] = useState([]);
  useEffect(() => { distributionService.list().then(setDps).catch(() => {}); }, []);

  const isSuperAdmin        = hasRole('Super Admin');
  const isAdmin             = hasRole('Admin');
  const isAdminOrSuperAdmin = isSuperAdmin || isAdmin;
  const isFinance           = hasRole(['Finance Officer', 'Super Admin']);
  const isFinanceOnly       = hasRole('Finance Officer') && !isSuperAdmin;
  const canRetrieve         = hasRole(['Super Admin', 'Warehouse Manager', 'Retrieval Officer']);
  const isPrivileged        = hasRole(['Super Admin', 'Warehouse Manager']);

  const [busy, setBusy] = useState(false);

  /* ── Checkbox row selection (for Manual Overstay Days) ───────────────── */
  const [selectedIds, setSelectedIds] = useState(new Set());
  const toggleSelect = (id) => {
    setSelectedIds(prev => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };
  const toggleSelectAll = () => {
    if (selectedIds.size === retrievals.length) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(retrievals.map(r => r.id)));
    }
  };

  /* ── Retrieve Device ──────────────────────────────────────────────────── */
  const [retrieveRow,  setRetrieveRow]  = useState(null);
  const [retrieveForm, setRetrieveForm] = useState({ t1_validation_ref: '', receipt_number: '' });
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
    const days = parseInt(retrieveRow.overstay_days) || 0;
    if (isSADType(retrieveRow.transaction_type) && t1Check.isLast && !retrieveForm.t1_validation_ref.trim()) {
      notify.error('T1 Validation Reference is required for the last device on this SAD receipt.');
      return;
    }
    if (!isPrivileged && days >= 1 && !retrieveForm.receipt_number.trim()) {
      notify.error('Receipt Number is required — device is overdue.');
      return;
    }
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
  const [outstationForm, setOutstationForm] = useState({ distribution_point_id: '' });

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
  const [billRow,           setBillRow]           = useState(null);
  const [billConsignee,     setBillConsignee]     = useState('');
  const [billReferenceDate, setBillReferenceDate] = useState('');
  const [billNotes,         setBillNotes]         = useState('');

  const openBillModal = (row) => {
    setBillRow(row);
    setBillConsignee(row.consignee || row.agency || '');
    setBillReferenceDate(new Date().toISOString().slice(0, 10));
    setBillNotes('');
  };

  const handleGenerateBill = async () => {
    setBusy(true);
    try {
      const res = await retrievalService.generateInvoice(billRow.id, {
        consignee:      billConsignee,
        reference_date: billReferenceDate,
        notes:          billNotes,
      });
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

  const openPayModal = (row) => {
    setPayRow(row);
    setPayForm({ receipt_number: row.receipt_number || '', finance_notes: row.finance_notes || '' });
  };

  const handleApprovePayment = async () => {
    if (!payForm.receipt_number.trim()) { notify.error('Receipt number is required.'); return; }
    setBusy(true);
    try {
      await retrievalService.approvePayment(payRow.id, payForm);
      notify.success('Payment approved successfully.');
      setPayRow(null);
      fetch();
    } catch (e) { notify.error(e.message); }
    finally { setBusy(false); }
  };

  /* ── Manual Overstay Days (header button + checkbox) ──────────────────── */
  const [manualRow,  setManualRow]  = useState(null);
  const [manualDays, setManualDays] = useState('');

  const openManualOverstay = () => {
    if (selectedIds.size === 0) {
      notify.error('No Device Selected — tick exactly one row checkbox first.');
      return;
    }
    if (selectedIds.size > 1) {
      notify.error('Multiple Devices Selected — tick exactly one row checkbox.');
      return;
    }
    const id  = [...selectedIds][0];
    const row = retrievals.find(r => r.id === id);
    if (!row) return;
    setManualRow(row);
    setManualDays(String(row.overstay_days || 0));
  };

  const handleManualOverstay = async () => {
    const newDays = Math.max(0, parseInt(manualDays, 10) || 0);
    setBusy(true);
    try {
      const res = await retrievalService.manualOverstay(manualRow.id, { overstay_days: newDays });
      const msg = res?.message || `Overstay days updated to ${newDays}.`;
      notify.success(msg);
      setManualRow(null); setManualDays(''); setSelectedIds(new Set());
      fetch();
    } catch (e) { notify.error(e.message); }
    finally { setBusy(false); }
  };

  /* ── New Device Retrieval (manual create) ─────────────────────────────── */
  const [createOpen, setCreateOpen] = useState(false);
  const blankCreate = { device_id: '', boe: '', sad_number: '', vehicle_number: '', driver_name: '',
    affixing_date: '', transaction_type: 'SAD', regime: '', destination_name: '', consignee: '', agency: '' };
  const [createForm, setCreateForm] = useState(blankCreate);

  const handleCreate = async () => {
    if (!createForm.device_id || !createForm.boe || !createForm.affixing_date || !createForm.transaction_type) {
      notify.error('Device ID, BOE, Affixing Date and Transaction Type are required.'); return;
    }
    setBusy(true);
    try {
      await retrievalService.createManual(createForm);
      notify.success('Retrieval record created manually.');
      setCreateOpen(false); setCreateForm(blankCreate);
      fetch();
    } catch (e) { notify.error(e.message); }
    finally { setBusy(false); }
  };

  /* ── Overstay Devices Modal ───────────────────────────────────────────── */
  const [overstayOpen,    setOverstayOpen]    = useState(false);
  const [overstayList,    setOverstayList]    = useState([]);
  const [overstayStats,   setOverstayStats]   = useState({ by_destination: [], by_allocation_point: [] });
  const [overstayLoading, setOverstayLoading] = useState(false);
  const [ovFilters, setOvFilters] = useState({
    search: '', device_id: '', boe: '', invoice_number: '', destination: '',
    allocation_point: '', payment_status: '', amount_min: '', amount_max: '',
    overstay_min: '', overstay_max: '', from: '', to: '',
    sort_by: 'dr.overstay_days', sort_dir: 'DESC',
  });

  const loadOverstay = async (f = ovFilters) => {
    setOverstayLoading(true);
    try {
      const data = await retrievalService.overstayDevices(f);
      setOverstayList(Array.isArray(data?.list) ? data.list : []);
      setOverstayStats(data?.stats || { by_destination: [], by_allocation_point: [] });
    } catch (e) { notify.error(e.message); }
    finally { setOverstayLoading(false); }
  };

  const openOverstayModal = () => { setOverstayOpen(true); loadOverstay(); };

  const applyOvFilter = (next) => { setOvFilters(next); loadOverstay(next); };

  /* ── Report #1 Modal (audit log) ─────────────────────────────────────── */
  const [reportOpen, setReportOpen]         = useState(false);
  const [reportData, setReportData]         = useState({ data: [], total: 0, page: 1, last_page: 1 });
  const [reportLoading, setReportLoading]   = useState(false);
  const [rFilters, setRFilters] = useState({
    search: '', device_id: '', boe: '', vehicle_number: '', retrieval_status: '',
    action_type: '', from: '', to: '', start_time: '', end_time: '',
    sort_by: 'l.created_at', sort_dir: 'DESC', page: 1,
  });

  const runReport = async (f = rFilters) => {
    setReportLoading(true);
    try {
      const d = await retrievalService.report(f);
      setReportData(d || { data: [], total: 0, page: 1, last_page: 1 });
    } catch (e) { notify.error(e.message); }
    finally { setReportLoading(false); }
  };

  const applyReport = (next) => { setRFilters(next); runReport(next); };
  const resetReport = () => {
    const blank = { search: '', device_id: '', boe: '', vehicle_number: '', retrieval_status: '',
      action_type: '', from: '', to: '', start_time: '', end_time: '',
      sort_by: 'l.created_at', sort_dir: 'DESC', page: 1 };
    setRFilters(blank); runReport(blank);
  };

  /* ── Report #2 Modal ─────────────────────────────────────────────────── */
  const [report2Open, setReport2Open]       = useState(false);
  const [report2Data, setReport2Data]       = useState({ data: [], total: 0, page: 1, last_page: 1 });
  const [report2Loading, setReport2Loading] = useState(false);
  const [r2Filters, setR2Filters] = useState({
    search: '', device_id: '', boe: '', vehicle_number: '', retrieval_status: '',
    action_type: '', from: '', to: '', sort_by: 'l.created_at', sort_dir: 'DESC', page: 1,
  });

  const runReport2 = async (f = r2Filters) => {
    if (!f.retrieval_status) {
      notify.error('Retrieval Status is required for Report #2.');
      return;
    }
    setReport2Loading(true);
    try {
      const d = await retrievalService.report2(f);
      setReport2Data(d || { data: [], total: 0, page: 1, last_page: 1 });
    } catch (e) { notify.error(e.message); }
    finally { setReport2Loading(false); }
  };

  /* ── Table Columns ────────────────────────────────────────────────────── */
  const allSelected = retrievals.length > 0 && selectedIds.size === retrievals.length;

  const columns = [
    {
      header: (
        <input type="checkbox" className="rounded" checked={allSelected}
          onChange={toggleSelectAll} title="Select all" />
      ),
      key: '__chk',
      render: (_, row) => (
        <input type="checkbox" className="rounded" checked={selectedIds.has(row.id)}
          onChange={() => toggleSelect(row.id)} onClick={e => e.stopPropagation()} />
      ),
    },
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

        /* Fix #1: canGenBill = pay !== 'PD' (not just !pay) */
        /* Fix #2: canGenBill restricted to canRetrieve roles */
        const canGenBill = canRetrieve && !isWaived && days >= 1 && pay !== 'PD';
        /* Fix #5: canWaive includes Admin role */
        const canWaive   = isAdminOrSuperAdmin && !isWaived && pay === 'PP';
        const canPay     = isFinance && pay === 'PP' && (parseFloat(row.overstay_amount) || 0) > 0;
        const canDL      = pay === 'PD' && !!row.finance_approval_date;
        const canBeRet   = isWaived || days < 1 || pay === 'PD';

        return (
          <div className="flex flex-wrap gap-1">
            {canRetrieve && ret === 'NOT_RETRIEVED' && canBeRet && (
              <button onClick={() => { setRetrieveRow(row); setRetrieveForm({ t1_validation_ref: '', receipt_number: '' }); }}
                className="btn-success btn-sm">Retrieve</button>
            )}
            {canRetrieve && ret === 'RETRIEVED' && row.transfer_status !== 'completed' && (
              <button onClick={() => { setOutstationRow(row); setOutstationForm({ distribution_point_id: '' }); }}
                className="btn-warning btn-sm">Return&nbsp;DP</button>
            )}
            {canGenBill && (
              <button onClick={() => openBillModal(row)} className="btn-danger btn-sm">Gen.&nbsp;Bill</button>
            )}
            {canWaive && (
              <button onClick={() => { setWaiverRow(row); setWaiverReason(''); }}
                className="btn-danger btn-sm">Waive</button>
            )}
            {canPay && (
              <button onClick={() => openPayModal(row)}
                className="btn-success btn-sm">Approve&nbsp;Pmt</button>
            )}
            {canDL && (
              <button onClick={() => downloadFile(retrievalService.downloadInvoiceUrl(row.id), `Invoice-${row.id}.html`).catch(() => notify.error('Download failed'))}
                className="btn-primary btn-sm">Invoice</button>
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
            {/* A1: New Device Retrieval */}
            <button onClick={() => setCreateOpen(true)} className="btn-secondary">
              New Retrieval
            </button>
            {/* A2: Report #1 — blue */}
            <button onClick={() => { setReportOpen(true); runReport(); }} className="btn-primary">
              Retrieval Report
            </button>
            {/* A3: Report #2 — amber */}
            <button onClick={() => setReport2Open(true)}
              className="btn-warning">
              Report #2
            </button>
            {/* A4: View Overstay Devices — red */}
            <button onClick={openOverstayModal} className="btn-danger">
              Overstay Devices
            </button>
            {/* A5: Manual Overstay Days — Super Admin only, amber, checkbox-driven */}
            {isSuperAdmin && (
              <button onClick={openManualOverstay}
                className="btn-warning flex items-center gap-1"
                title="Select exactly one row first">
                ✏ Manual Overstay Days
                {selectedIds.size === 1 && (
                  <span className="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-white text-amber-800 font-bold">1</span>
                )}
              </button>
            )}
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
        {retrieveRow && (() => {
          const days = parseInt(retrieveRow.overstay_days) || 0;
          const showReceiptField = !isPrivileged && days >= 1;
          return (
            <div className="space-y-4">
              <div className="bg-gray-50 rounded-lg p-3 text-sm space-y-1">
                <p><span className="text-gray-500">BOE:</span> <strong className="font-mono">{retrieveRow.boe || '—'}</strong></p>
                <p><span className="text-gray-500">SAD:</span> <strong className="font-mono">{retrieveRow.sad_number || '—'}</strong></p>
                <p><span className="text-gray-500">Vehicle:</span> <strong>{retrieveRow.vehicle_number || '—'}</strong></p>
                <p><span className="text-gray-500">Device:</span> <strong className="font-mono">{retrieveRow.device_identifier || '—'}</strong></p>
                <p><span className="text-gray-500">Type:</span> <StatusBadge status={isSADType(retrieveRow.transaction_type) ? 'SAD' : (retrieveRow.transaction_type || '—')} /></p>
              </div>

              {/* Fix #4: Receipt Number for non-privileged overdue users */}
              {showReceiptField && (
                <div>
                  <label className="label">
                    Receipt Number <span className="text-red-500">*</span>
                    <span className="ml-2 text-xs text-red-600 font-semibold">
                      Required — device is {days} day(s) overdue
                    </span>
                  </label>
                  <input type="text" className="input border-red-300 focus:border-red-500"
                    placeholder="e.g. RCT-2024-0001"
                    value={retrieveForm.receipt_number}
                    onChange={e => setRetrieveForm(f => ({ ...f, receipt_number: e.target.value }))} />
                </div>
              )}

              {/* T1 field for SAD type */}
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

              {days > 0 && (
                <div className="rounded-lg p-3 border border-amber-200 bg-amber-50">
                  <p className="text-sm text-amber-800">
                    Overstay: <strong>{days} day(s)</strong> &mdash; Payment:&nbsp;
                    <StatusBadge status={retrieveRow.payment_status || 'PP'} />
                  </p>
                </div>
              )}
            </div>
          );
        })()}
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
            {/* ── Editable fields — shown first so they are always visible ── */}
            <div className="rounded-xl border-2 p-4 space-y-3"
              style={{ borderColor: '#1E2D7A', background: '#f0f2fb' }}>
              <p className="text-sm font-bold uppercase tracking-wide" style={{ color: '#1E2D7A' }}>
                Invoice Details — Review &amp; Edit Before Generating
              </p>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="label">Reference Date <span className="text-red-500">*</span></label>
                  <input type="date" className="input"
                    value={billReferenceDate}
                    onChange={e => setBillReferenceDate(e.target.value)} />
                </div>
                <div>
                  <label className="label">Consignee</label>
                  <input type="text" className="input"
                    placeholder="Consignee / importer name…"
                    value={billConsignee}
                    onChange={e => setBillConsignee(e.target.value)} />
                </div>
              </div>
              <div>
                <label className="label">Notes <span className="text-gray-400 text-xs">(optional)</span></label>
                <textarea className="input" rows={2}
                  placeholder="Additional notes for this invoice…"
                  value={billNotes}
                  onChange={e => setBillNotes(e.target.value)} />
              </div>
            </div>

            {/* ── Total charge summary ── */}
            <div className="rounded-lg p-4 border border-red-200 bg-red-50 text-center">
              <p className="text-sm text-red-700 mb-1">Total Overstay Charge</p>
              <p className="text-3xl font-bold text-red-700">{billRow.overstay_days} day(s)</p>
              <p className="text-xl font-semibold text-red-800 mt-1">
                GMD {Number((billRow.overstay_days || 0) * 1000).toLocaleString()}
              </p>
              <p className="text-xs text-red-600 mt-1">Rate: GMD 1,000 per day (flat rate)</p>
            </div>

            {/* ── Read-only device details ── */}
            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Device &amp; Trip Details</p>
            <div className="grid grid-cols-2 gap-3 bg-gray-50 rounded-lg p-4 text-sm">
              <Field label="Device ID"        value={billRow.device_identifier} />
              <Field label="BOE / SAD"         value={[billRow.boe, billRow.sad_number].filter(Boolean).join(' / ')} />
              <Field label="Vehicle Number"    value={billRow.vehicle_number} />
              <Field label="Driver Name"       value={billRow.driver_name} />
              <Field label="Regime"            value={billRow.regime} />
              <Field label="Agent / Agency"    value={billRow.agency} />
              <Field label="Destination"       value={billRow.destination_name} />
              <Field label="Allocation Point"  value={billRow.allocation_point_name} />
              <Field label="Route Type"        value={billRow.long_route_id ? 'Long Route (2-day grace)' : 'Short Route (1-day grace)'} />
              <Field label="Overstay Days"     value={`${billRow.overstay_days} day(s)`} highlight />
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
              <p><span className="text-gray-600">Device:</span> <strong>{waiverRow.device_identifier || '—'}</strong></p>
              <p className="mt-1"><span className="text-gray-600">Overstay Charges:</span>{' '}
                <strong className="text-red-700">{waiverRow.overstay_days} day(s) ({fmtMoney(waiverRow.overstay_amount)})</strong>
              </p>
              <p className="mt-1 text-xs text-red-600">This action is permanent. The waiver is recorded in the audit log and cannot be reversed.</p>
            </div>
            <div>
              <label className="label">
                Reason for Waiving <span className="text-red-500">*</span>
                <span className="ml-2 text-xs text-gray-400">
                  {waiverReason.trim().length}/10 min &nbsp;
                  {waiverReason.trim().length >= 10 ? '✓' : `(${10 - waiverReason.trim().length} more needed)`}
                </span>
              </label>
              <textarea className="input" rows={3} maxLength={500}
                placeholder="Enter reason for waiving overstay fees (min 10 characters)…"
                value={waiverReason} onChange={e => setWaiverReason(e.target.value)} />
            </div>
          </div>
        )}
      </Modal>

      {/* ── Approve Payment ──────────────────────────────────────────────── */}
      <Modal isOpen={!!payRow} onClose={() => setPayRow(null)} title="Approve Payment"
        footer={
          <>
            <button onClick={() => setPayRow(null)} className="btn-secondary">Cancel</button>
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
            {/* Fix #12: pre-fill receipt_number from existing record */}
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

      {/* ── A5: Manual Overstay Days (header button → modal) ─────────────── */}
      <Modal isOpen={!!manualRow} onClose={() => { setManualRow(null); setManualDays(''); }}
        title="Set Manual Overstay Days" size="sm"
        footer={
          <>
            <button onClick={() => { setManualRow(null); setManualDays(''); }} className="btn-secondary">Cancel</button>
            <button onClick={handleManualOverstay} disabled={busy} className="btn-warning">
              {busy ? 'Saving…' : 'Update Overstay Days'}
            </button>
          </>
        }>
        {manualRow && (
          <div className="space-y-4">
            <div className="bg-gray-50 rounded-lg p-3 text-sm space-y-1">
              <p><span className="text-gray-500">Device:</span> <strong className="font-mono">{manualRow.device_identifier || '—'}</strong></p>
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
            <p className="text-xs text-amber-600 bg-amber-50 rounded p-2">
              Note: If the automatic calculation differs from your entry, it may overwrite this value on the next record save.
            </p>
          </div>
        )}
      </Modal>

      {/* ── A1: New Device Retrieval (manual create form) ─────────────────── */}
      <Modal isOpen={createOpen} onClose={() => setCreateOpen(false)} title="New Device Retrieval" size="lg"
        footer={
          <>
            <button onClick={() => setCreateOpen(false)} className="btn-secondary">Cancel</button>
            <button onClick={handleCreate} disabled={busy} className="btn-primary">
              {busy ? 'Creating…' : 'Create Retrieval Record'}
            </button>
          </>
        }>
        <div className="space-y-3">
          <div className="rounded-lg p-3 border border-blue-200 bg-blue-50 text-xs text-blue-700">
            For data corrections only — retrieval records are normally created automatically from Confirmed Affixed.
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="label">Device ID <span className="text-red-500">*</span></label>
              <input className="input" placeholder="GPS device identifier"
                value={createForm.device_id}
                onChange={e => setCreateForm(f => ({ ...f, device_id: e.target.value }))} />
            </div>
            <div>
              <label className="label">BOE <span className="text-red-500">*</span></label>
              <input className="input" placeholder="Bill of Entry number"
                value={createForm.boe}
                onChange={e => setCreateForm(f => ({ ...f, boe: e.target.value }))} />
            </div>
            <div>
              <label className="label">SAD Number</label>
              <input className="input" placeholder="SAD document number"
                value={createForm.sad_number}
                onChange={e => setCreateForm(f => ({ ...f, sad_number: e.target.value }))} />
            </div>
            <div>
              <label className="label">Transaction Type <span className="text-red-500">*</span></label>
              <select className="input" value={createForm.transaction_type}
                onChange={e => setCreateForm(f => ({ ...f, transaction_type: e.target.value }))}>
                <option value="SAD">SAD</option>
                <option value="TRUCK">TRUCK</option>
              </select>
            </div>
            <div>
              <label className="label">Vehicle Number</label>
              <input className="input" placeholder="e.g. BJL-1234"
                value={createForm.vehicle_number}
                onChange={e => setCreateForm(f => ({ ...f, vehicle_number: e.target.value }))} />
            </div>
            <div>
              <label className="label">Driver Name</label>
              <input className="input" placeholder="Driver full name"
                value={createForm.driver_name}
                onChange={e => setCreateForm(f => ({ ...f, driver_name: e.target.value }))} />
            </div>
            <div>
              <label className="label">Affixing Date <span className="text-red-500">*</span></label>
              <input type="date" className="input"
                value={createForm.affixing_date}
                onChange={e => setCreateForm(f => ({ ...f, affixing_date: e.target.value }))} />
            </div>
            <div>
              <label className="label">Regime</label>
              <input className="input" placeholder="Customs regime"
                value={createForm.regime}
                onChange={e => setCreateForm(f => ({ ...f, regime: e.target.value }))} />
            </div>
            <div>
              <label className="label">Consignee</label>
              <input className="input" placeholder="Consignee name"
                value={createForm.consignee}
                onChange={e => setCreateForm(f => ({ ...f, consignee: e.target.value }))} />
            </div>
            <div>
              <label className="label">Agent / Agency</label>
              <input className="input" placeholder="Agent or agency name"
                value={createForm.agency}
                onChange={e => setCreateForm(f => ({ ...f, agency: e.target.value }))} />
            </div>
          </div>
        </div>
      </Modal>

      {/* ── A4: Overstay Devices Modal — with filters + stats ────────────── */}
      <Modal isOpen={overstayOpen} onClose={() => setOverstayOpen(false)} title="Overstay Devices" size="xl"
        footer={<button onClick={() => setOverstayOpen(false)} className="btn-secondary">Close</button>}>
        {/* Filter panel */}
        <div className="mb-4 p-3 bg-gray-50 rounded-lg">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-2 mb-2">
            <input className="input input-sm" placeholder="Search…"
              value={ovFilters.search} onChange={e => applyOvFilter({ ...ovFilters, search: e.target.value })} />
            <input className="input input-sm" placeholder="Device ID"
              value={ovFilters.device_id} onChange={e => applyOvFilter({ ...ovFilters, device_id: e.target.value })} />
            <input className="input input-sm" placeholder="BOE"
              value={ovFilters.boe} onChange={e => applyOvFilter({ ...ovFilters, boe: e.target.value })} />
            <input className="input input-sm" placeholder="Invoice Number"
              value={ovFilters.invoice_number} onChange={e => applyOvFilter({ ...ovFilters, invoice_number: e.target.value })} />
            <input className="input input-sm" placeholder="Destination"
              value={ovFilters.destination} onChange={e => applyOvFilter({ ...ovFilters, destination: e.target.value })} />
            <input className="input input-sm" placeholder="Allocation Point"
              value={ovFilters.allocation_point} onChange={e => applyOvFilter({ ...ovFilters, allocation_point: e.target.value })} />
            <select className="input input-sm" value={ovFilters.payment_status}
              onChange={e => applyOvFilter({ ...ovFilters, payment_status: e.target.value })}>
              <option value="">All Payment</option>
              <option value="PP">Pending</option>
              <option value="PD">Paid</option>
              <option value="WAIVED">Waived</option>
            </select>
            <div className="flex gap-1">
              <input type="number" className="input input-sm w-20" placeholder="Days min"
                value={ovFilters.overstay_min} onChange={e => applyOvFilter({ ...ovFilters, overstay_min: e.target.value })} />
              <input type="number" className="input input-sm w-20" placeholder="Days max"
                value={ovFilters.overstay_max} onChange={e => applyOvFilter({ ...ovFilters, overstay_max: e.target.value })} />
            </div>
          </div>
          <div className="flex gap-2 items-center flex-wrap">
            <input type="date" className="input input-sm" value={ovFilters.from}
              onChange={e => applyOvFilter({ ...ovFilters, from: e.target.value })} />
            <input type="date" className="input input-sm" value={ovFilters.to}
              onChange={e => applyOvFilter({ ...ovFilters, to: e.target.value })} />
            <select className="input input-sm" value={ovFilters.sort_by}
              onChange={e => applyOvFilter({ ...ovFilters, sort_by: e.target.value })}>
              <option value="dr.overstay_days">Sort by Days</option>
              <option value="dr.overstay_amount">Sort by Amount</option>
              <option value="dr.affixing_date">Sort by Date</option>
              <option value="dest.name">Sort by Destination</option>
            </select>
            <select className="input input-sm" value={ovFilters.sort_dir}
              onChange={e => applyOvFilter({ ...ovFilters, sort_dir: e.target.value })}>
              <option value="DESC">Descending</option>
              <option value="ASC">Ascending</option>
            </select>
            <button className="btn-secondary btn-sm" onClick={() => {
              const blank = { search: '', device_id: '', boe: '', invoice_number: '', destination: '',
                allocation_point: '', payment_status: '', amount_min: '', amount_max: '',
                overstay_min: '', overstay_max: '', from: '', to: '',
                sort_by: 'dr.overstay_days', sort_dir: 'DESC' };
              applyOvFilter(blank);
            }}>Reset</button>
          </div>
        </div>

        {/* Aggregate Stats */}
        {(overstayStats.by_destination?.length > 0 || overstayStats.by_allocation_point?.length > 0) && (
          <div className="grid grid-cols-2 gap-4 mb-4">
            {overstayStats.by_destination?.length > 0 && (
              <div>
                <p className="text-xs font-semibold text-gray-500 uppercase mb-2">By Destination</p>
                <div className="space-y-1">
                  {overstayStats.by_destination.map((r, i) => (
                    <div key={i} className="flex justify-between text-xs bg-gray-50 rounded px-2 py-1">
                      <span className="font-medium truncate max-w-[120px]">{r.label || '—'}</span>
                      <span className="text-gray-500">{r.count} device(s)</span>
                      <span className="text-red-700 font-semibold">{fmtMoney(r.total_amount)}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}
            {overstayStats.by_allocation_point?.length > 0 && (
              <div>
                <p className="text-xs font-semibold text-gray-500 uppercase mb-2">By Allocation Point</p>
                <div className="space-y-1">
                  {overstayStats.by_allocation_point.map((r, i) => (
                    <div key={i} className="flex justify-between text-xs bg-gray-50 rounded px-2 py-1">
                      <span className="font-medium truncate max-w-[120px]">{r.label || '—'}</span>
                      <span className="text-gray-500">{r.count} device(s)</span>
                      <span className="text-red-700 font-semibold">{fmtMoney(r.total_amount)}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}

        {overstayLoading ? (
          <div className="py-8 text-center text-gray-400">Loading…</div>
        ) : overstayList.length === 0 ? (
          <div className="py-8 text-center text-gray-400">No overstay devices match your filters.</div>
        ) : (
          <div className="overflow-x-auto">
            <p className="text-sm text-gray-500 mb-3">{overstayList.length} device(s) with active overstay</p>
            <table className="min-w-full text-xs">
              <thead>
                <tr className="bg-gray-50">
                  {['BOE', 'Device ID', 'Vehicle', 'Station', 'Destination', 'Days', 'Amount', 'Payment', 'Retrieval', 'Invoice #'].map(h => (
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
                    <td className="px-3 py-2 font-mono text-gray-500">{r.invoice_number || '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Modal>

      {/* ── A2: Report #1 — Audit Log Modal ──────────────────────────────── */}
      <Modal isOpen={reportOpen} onClose={() => setReportOpen(false)} title="Device Retrieval Report" size="xl"
        footer={
          <div className="flex gap-2">
            <button onClick={() => downloadFile(retrievalService.exportUrl(rFilters), 'device-retrieval-report.csv').catch(() => notify.error('Export failed'))}
              className="btn-primary">Export CSV</button>
            <button onClick={() => setReportOpen(false)} className="btn-secondary">Close</button>
          </div>
        }>
        <div className="space-y-3">
          {/* Filters */}
          <div className="p-3 bg-gray-50 rounded-lg space-y-2">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
              <input className="input input-sm" placeholder="General search…"
                value={rFilters.search} onChange={e => setRFilters(f => ({ ...f, search: e.target.value }))} />
              <input className="input input-sm" placeholder="Device ID"
                value={rFilters.device_id} onChange={e => setRFilters(f => ({ ...f, device_id: e.target.value }))} />
              <input className="input input-sm" placeholder="BOE"
                value={rFilters.boe} onChange={e => setRFilters(f => ({ ...f, boe: e.target.value }))} />
              <input className="input input-sm" placeholder="Vehicle Number"
                value={rFilters.vehicle_number} onChange={e => setRFilters(f => ({ ...f, vehicle_number: e.target.value }))} />
              <select className="input input-sm" value={rFilters.retrieval_status}
                onChange={e => setRFilters(f => ({ ...f, retrieval_status: e.target.value }))}>
                <option value="">All Retrieval Status</option>
                <option value="NOT_RETRIEVED">Not Retrieved</option>
                <option value="RETRIEVED">Retrieved</option>
              </select>
              <select className="input input-sm" value={rFilters.action_type}
                onChange={e => setRFilters(f => ({ ...f, action_type: e.target.value }))}>
                <option value="">All Action Types</option>
                <option value="RETRIEVED">RETRIEVED</option>
                <option value="RETURNED_OUTSTATION">RETURNED_OUTSTATION</option>
              </select>
              <div className="flex gap-1 items-center">
                <input type="date" className="input input-sm flex-1" value={rFilters.from}
                  onChange={e => setRFilters(f => ({ ...f, from: e.target.value }))} />
                <input type="time" className="input input-sm w-24" value={rFilters.start_time}
                  onChange={e => setRFilters(f => ({ ...f, start_time: e.target.value }))} />
              </div>
              <div className="flex gap-1 items-center">
                <input type="date" className="input input-sm flex-1" value={rFilters.to}
                  onChange={e => setRFilters(f => ({ ...f, to: e.target.value }))} />
                <input type="time" className="input input-sm w-24" value={rFilters.end_time}
                  onChange={e => setRFilters(f => ({ ...f, end_time: e.target.value }))} />
              </div>
              <select className="input input-sm" value={rFilters.sort_by}
                onChange={e => setRFilters(f => ({ ...f, sort_by: e.target.value }))}>
                <option value="l.created_at">Sort by Date</option>
                <option value="l.action_type">Sort by Action</option>
                <option value="l.boe">Sort by BOE</option>
                <option value="dr.retrieval_status">Sort by Status</option>
              </select>
              <select className="input input-sm" value={rFilters.sort_dir}
                onChange={e => setRFilters(f => ({ ...f, sort_dir: e.target.value }))}>
                <option value="DESC">Descending</option>
                <option value="ASC">Ascending</option>
              </select>
            </div>
            <div className="flex gap-2">
              <button className="btn-primary btn-sm" onClick={() => runReport({ ...rFilters, page: 1 })}>Run Report</button>
              <button className="btn-secondary btn-sm" onClick={resetReport}>Reset</button>
            </div>
          </div>

          {reportLoading ? (
            <div className="py-8 text-center text-gray-400">Loading report…</div>
          ) : (
            <div className="overflow-x-auto max-h-96">
              <p className="text-xs text-gray-500 mb-2">
                {reportData.total} record(s) — page {reportData.page} of {reportData.last_page}
              </p>
              <table className="min-w-full text-xs">
                <thead>
                  <tr className="bg-gray-50 sticky top-0">
                    {[
                      { label: 'Date/Time', col: 'l.created_at' },
                      { label: 'Action', col: 'l.action_type' },
                      { label: 'Device', col: null },
                      { label: 'BOE', col: 'l.boe' },
                      { label: 'Vehicle', col: null },
                      { label: 'Station', col: null },
                      { label: 'Destination', col: null },
                      { label: 'Status', col: 'dr.retrieval_status' },
                      { label: 'Notes', col: null },
                    ].map(({ label, col }) => (
                      <th key={label} className="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap cursor-pointer hover:bg-gray-100"
                        onClick={() => {
                          if (!col) return;
                          const next = { ...rFilters, sort_by: col,
                            sort_dir: rFilters.sort_by === col && rFilters.sort_dir === 'ASC' ? 'DESC' : 'ASC', page: 1 };
                          applyReport(next);
                        }}>
                        {label}
                        {rFilters.sort_by === col && <span className="ml-1">{rFilters.sort_dir === 'ASC' ? '↑' : '↓'}</span>}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {(reportData.data || []).map((r, i) => (
                    <tr key={r.id || i} className="hover:bg-gray-50">
                      <td className="px-3 py-2 whitespace-nowrap">{fmtDT(r.created_at)}</td>
                      <td className="px-3 py-2"><StatusBadge status={r.action_type || '—'} /></td>
                      <td className="px-3 py-2 font-mono font-semibold" style={{ color: '#1E2D7A' }}>{r.device_identifier || '—'}</td>
                      <td className="px-3 py-2 font-mono">{r.boe || '—'}</td>
                      <td className="px-3 py-2">{r.vehicle_number || '—'}</td>
                      <td className="px-3 py-2">{r.allocation_point_name || '—'}</td>
                      <td className="px-3 py-2">{r.destination_name || '—'}</td>
                      <td className="px-3 py-2"><StatusBadge status={r.retrieval_status || 'NOT_RETRIEVED'} /></td>
                      <td className="px-3 py-2 text-gray-500">{r.notes || '—'}</td>
                    </tr>
                  ))}
                  {(reportData.data || []).length === 0 && (
                    <tr><td colSpan={9} className="px-3 py-8 text-center text-gray-400">No records found.</td></tr>
                  )}
                </tbody>
              </table>
              {reportData.last_page > 1 && (
                <div className="flex gap-2 mt-3 items-center justify-center text-xs">
                  <button disabled={reportData.page <= 1} className="btn-secondary btn-sm"
                    onClick={() => applyReport({ ...rFilters, page: reportData.page - 1 })}>← Prev</button>
                  <span>Page {reportData.page} / {reportData.last_page}</span>
                  <button disabled={reportData.page >= reportData.last_page} className="btn-secondary btn-sm"
                    onClick={() => applyReport({ ...rFilters, page: reportData.page + 1 })}>Next →</button>
                </div>
              )}
            </div>
          )}
        </div>
      </Modal>

      {/* ── A3: Report #2 Modal (amber, retrieval_status required) ────────── */}
      <Modal isOpen={report2Open} onClose={() => setReport2Open(false)} title="Device Retrieval Report #2" size="xl"
        footer={
          <div className="flex gap-2">
            <button onClick={() => downloadFile(retrievalService.export2Url(r2Filters), 'device-retrieval-report-2.csv').catch(() => notify.error('Export failed'))}
              className="btn-warning">Export Report #2</button>
            <button onClick={() => setReport2Open(false)} className="btn-secondary">Close</button>
          </div>
        }>
        <div className="space-y-3">
          <div className="rounded-lg p-3 border border-amber-200 bg-amber-50 text-xs text-amber-700">
            Retrieval Status is <strong>required</strong> before running this report.
          </div>
          {/* Filters */}
          <div className="p-3 bg-gray-50 rounded-lg space-y-2">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
              <input className="input input-sm" placeholder="General search…"
                value={r2Filters.search} onChange={e => setR2Filters(f => ({ ...f, search: e.target.value }))} />
              <input className="input input-sm" placeholder="Device ID"
                value={r2Filters.device_id} onChange={e => setR2Filters(f => ({ ...f, device_id: e.target.value }))} />
              <input className="input input-sm" placeholder="BOE"
                value={r2Filters.boe} onChange={e => setR2Filters(f => ({ ...f, boe: e.target.value }))} />
              <input className="input input-sm" placeholder="Vehicle Number"
                value={r2Filters.vehicle_number} onChange={e => setR2Filters(f => ({ ...f, vehicle_number: e.target.value }))} />
              <select className="input input-sm border-amber-300" value={r2Filters.retrieval_status}
                onChange={e => setR2Filters(f => ({ ...f, retrieval_status: e.target.value }))}>
                <option value="">Retrieval Status *</option>
                <option value="NOT_RETRIEVED">Not Retrieved</option>
                <option value="RETRIEVED">Retrieved</option>
              </select>
              <select className="input input-sm" value={r2Filters.action_type}
                onChange={e => setR2Filters(f => ({ ...f, action_type: e.target.value }))}>
                <option value="">All Action Types</option>
                <option value="RETRIEVED">RETRIEVED</option>
                <option value="RETURNED_OUTSTATION">RETURNED_OUTSTATION</option>
              </select>
              <input type="date" className="input input-sm" value={r2Filters.from}
                onChange={e => setR2Filters(f => ({ ...f, from: e.target.value }))} />
              <input type="date" className="input input-sm" value={r2Filters.to}
                onChange={e => setR2Filters(f => ({ ...f, to: e.target.value }))} />
              <select className="input input-sm" value={r2Filters.sort_by}
                onChange={e => setR2Filters(f => ({ ...f, sort_by: e.target.value }))}>
                <option value="l.created_at">Sort by Date</option>
                <option value="l.action_type">Sort by Action</option>
                <option value="l.boe">Sort by BOE</option>
              </select>
              <select className="input input-sm" value={r2Filters.sort_dir}
                onChange={e => setR2Filters(f => ({ ...f, sort_dir: e.target.value }))}>
                <option value="DESC">Descending</option>
                <option value="ASC">Ascending</option>
              </select>
            </div>
            <div className="flex gap-2">
              <button className="btn-warning btn-sm" onClick={() => runReport2({ ...r2Filters, page: 1 })}>Run Report #2</button>
              <button className="btn-secondary btn-sm" onClick={() => {
                const blank = { search: '', device_id: '', boe: '', vehicle_number: '',
                  retrieval_status: '', action_type: '', from: '', to: '',
                  sort_by: 'l.created_at', sort_dir: 'DESC', page: 1 };
                setR2Filters(blank);
              }}>Reset</button>
            </div>
          </div>

          {report2Loading ? (
            <div className="py-8 text-center text-gray-400">Loading report…</div>
          ) : (
            <div className="overflow-x-auto max-h-96">
              <p className="text-xs text-gray-500 mb-2">
                {report2Data.total} record(s) — page {report2Data.page} of {report2Data.last_page}
              </p>
              <table className="min-w-full text-xs">
                <thead>
                  <tr className="bg-amber-50 sticky top-0">
                    {['Date/Time', 'Action', 'Device', 'BOE', 'Vehicle', 'Station', 'Destination', 'Status', 'Notes'].map(h => (
                      <th key={h} className="px-3 py-2 text-left font-semibold text-amber-800 uppercase tracking-wide whitespace-nowrap">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {(report2Data.data || []).map((r, i) => (
                    <tr key={r.id || i} className="hover:bg-gray-50">
                      <td className="px-3 py-2 whitespace-nowrap">{fmtDT(r.created_at)}</td>
                      <td className="px-3 py-2"><StatusBadge status={r.action_type || '—'} /></td>
                      <td className="px-3 py-2 font-mono font-semibold" style={{ color: '#1E2D7A' }}>{r.device_identifier || '—'}</td>
                      <td className="px-3 py-2 font-mono">{r.boe || '—'}</td>
                      <td className="px-3 py-2">{r.vehicle_number || '—'}</td>
                      <td className="px-3 py-2">{r.allocation_point_name || '—'}</td>
                      <td className="px-3 py-2">{r.destination_name || '—'}</td>
                      <td className="px-3 py-2"><StatusBadge status={r.retrieval_status || 'NOT_RETRIEVED'} /></td>
                      <td className="px-3 py-2 text-gray-500">{r.notes || '—'}</td>
                    </tr>
                  ))}
                  {(report2Data.data || []).length === 0 && (
                    <tr><td colSpan={9} className="px-3 py-8 text-center text-gray-400">
                      {r2Filters.retrieval_status ? 'No records found.' : 'Select Retrieval Status and click Run Report #2.'}
                    </td></tr>
                  )}
                </tbody>
              </table>
              {report2Data.last_page > 1 && (
                <div className="flex gap-2 mt-3 items-center justify-center text-xs">
                  <button disabled={report2Data.page <= 1} className="btn-secondary btn-sm"
                    onClick={() => runReport2({ ...r2Filters, page: report2Data.page - 1 })}>← Prev</button>
                  <span>Page {report2Data.page} / {report2Data.last_page}</span>
                  <button disabled={report2Data.page >= report2Data.last_page} className="btn-secondary btn-sm"
                    onClick={() => runReport2({ ...r2Filters, page: report2Data.page + 1 })}>Next →</button>
                </div>
              )}
            </div>
          )}
        </div>
      </Modal>
    </div>
  );
}
