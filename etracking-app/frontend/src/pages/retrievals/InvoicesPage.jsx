/* @refresh reset */
import React, { useState, useEffect, useCallback } from 'react';
import { downloadFile } from '../../utils/downloadFile';
import { invoiceService } from '../../services/retrievalService';
import { retrievalService } from '../../services/retrievalService';
import { useNotification } from '../../contexts/NotificationContext';
import { useAuth } from '../../contexts/AuthContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';

const fmtDate  = (v) => v ? new Date(v).toLocaleDateString('en-GB') : '—';
const fmtMoney = (v) => parseFloat(v) > 0 ? `GMD ${Number(v).toLocaleString()}` : '—';

export default function InvoicesPage() {
  const { notify }  = useNotification();
  const { hasRole } = useAuth();

  const isFinance    = hasRole(['Finance Officer', 'Super Admin']);
  const isSuperAdmin = hasRole('Super Admin');

  const [invoices, setInvoices]     = useState([]);
  const [meta, setMeta]             = useState({ total: 0, current_page: 1, last_page: 1 });
  const [loading, setLoading]       = useState(false);
  const [filters, setFilters]       = useState({ search: '', status: '', from: '', to: '', page: 1, per_page: 25 });

  const load = useCallback(async (f = filters) => {
    setLoading(true);
    try {
      const res = await invoiceService.list(f);
      setInvoices(res.data || []);
      setMeta(res.meta || {});
    } catch (e) { notify.error(e.message); }
    finally { setLoading(false); }
  }, []);

  useEffect(() => { load(); }, []);

  const applyFilter = (next) => { setFilters(next); load(next); };
  const changePage  = (page) => applyFilter({ ...filters, page });
  const clearFilter = () => applyFilter({ search: '', status: '', from: '', to: '', page: 1, per_page: 25 });

  const columns = [
    {
      header: 'Ref #', key: 'reference_number',
      render: v => <span className="font-mono text-xs font-semibold" style={{ color: '#1E2D7A' }}>{v || '—'}</span>
    },
    {
      header: 'Date', key: 'reference_date',
      render: v => <span className="text-xs whitespace-nowrap">{fmtDate(v)}</span>
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
      header: 'Device', key: 'device_identifier',
      render: v => <span className="font-mono text-xs" style={{ color: '#1E2D7A' }}>{v || '—'}</span>
    },
    {
      header: 'Vehicle', key: 'vehicle_number',
      render: v => <span className="text-xs">{v || '—'}</span>
    },
    {
      header: 'Consignee', key: 'consignee',
      render: v => <span className="text-xs">{v || '—'}</span>
    },
    {
      header: 'Regime', key: 'regime',
      render: v => <span className="text-xs">{v || '—'}</span>
    },
    {
      header: 'Days', key: 'overstay_days',
      render: v => <span className="font-semibold text-red-700 text-xs">{v || 0}</span>
    },
    {
      header: 'Amount', key: 'total_amount',
      render: v => <span className="font-semibold text-xs text-red-700">{fmtMoney(v)}</span>
    },
    {
      header: 'Status', key: 'status',
      render: v => <StatusBadge status={v || 'PENDING'} />
    },
    {
      header: 'Retrieval Pmt', key: 'retrieval_payment_status',
      render: v => v ? <StatusBadge status={v} /> : <span className="text-xs text-gray-400">—</span>
    },
    {
      header: 'Actions', key: 'id',
      render: (id, row) => {
        const retrievalId = row.device_retrieval_id;
        if (!retrievalId) return null;
        const canDL = row.retrieval_payment_status === 'PD';
        return (
          <div className="flex gap-1">
            {canDL && (
              <button onClick={() => downloadFile(retrievalService.downloadInvoiceUrl(retrievalId), `Invoice-${retrievalId}.html`).catch(() => {})}
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
        title="Overstay Invoices"
        subtitle="Generated overstay billing records and payment status"
        actions={
          <button onClick={() => downloadFile('/api/reports/overstay-invoices', 'overstay-invoices.csv').catch(() => {})} className="btn-secondary">
            Export CSV
          </button>
        }
      />

      {!isFinance && (
        <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
          Viewing all overstay invoices. Finance Officers see only records pending payment.
        </div>
      )}

      <div className="card p-3 mb-4 flex flex-wrap gap-3 items-end">
        <input className="input input-sm w-52" placeholder="Search BOE / SAD / Vehicle / Ref#…"
          value={filters.search}
          onChange={e => applyFilter({ ...filters, search: e.target.value, page: 1 })} />
        <select className="input input-sm w-36" value={filters.status}
          onChange={e => applyFilter({ ...filters, status: e.target.value, page: 1 })}>
          <option value="">All Status</option>
          <option value="PD">Paid (PD)</option>
          <option value="PENDING">Pending</option>
          <option value="WAIVED">Waived</option>
        </select>
        <div className="flex items-center gap-1">
          <label className="text-xs text-gray-500">From</label>
          <input type="date" className="input input-sm" value={filters.from}
            onChange={e => applyFilter({ ...filters, from: e.target.value, page: 1 })} />
        </div>
        <div className="flex items-center gap-1">
          <label className="text-xs text-gray-500">To</label>
          <input type="date" className="input input-sm" value={filters.to}
            onChange={e => applyFilter({ ...filters, to: e.target.value, page: 1 })} />
        </div>
        <button className="btn-secondary btn-sm" onClick={clearFilter}>Clear</button>
      </div>

      <div className="card p-0 overflow-hidden">
        <DataTable columns={columns} data={invoices} loading={loading}
          emptyMessage="No invoices found." />
        <div className="px-4 py-3 border-t border-gray-100">
          <Pagination meta={meta} onPageChange={changePage} />
        </div>
      </div>
    </div>
  );
}
