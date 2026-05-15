import React, { useState, useCallback, useEffect } from 'react';
import api from '../../services/api';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';

const fmtDate  = (v) => v ? new Date(v).toLocaleDateString('en-GB') : '—';
const fmtMoney = (v) => v && parseFloat(v) > 0 ? `GMD ${Number(v).toLocaleString()}` : '—';

export default function FinanceDispatchPage() {
  const { notify } = useNotification();
  const [rows, setRows]       = useState([]);
  const [meta, setMeta]       = useState({ total: 0, current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(false);
  const [filters, setFilters] = useState({ search: '', status: '', from: '', to: '', page: 1, per_page: 25 });

  const load = useCallback(async (f = filters) => {
    setLoading(true);
    try {
      const { data } = await api.get('/confirmed-affixed', { params: { ...f, per_page: f.per_page || 25 } });
      setRows(data.data || []);
      setMeta(data.meta || {});
    } catch (e) { notify.error(e?.message || 'Failed to load dispatch records'); }
    finally { setLoading(false); }
  }, []);

  useEffect(() => { load(); }, []);

  const apply  = (next) => { setFilters(next); load(next); };
  const clear  = () => apply({ search: '', status: '', from: '', to: '', page: 1, per_page: 25 });
  const goPage = (p) => apply({ ...filters, page: p });

  const total = rows.length;

  const columns = [
    {
      header: 'Receipt / SAD',
      key: 'sad_number',
      render: (v, row) => (
        <div>
          <div className="font-mono text-xs font-semibold" style={{ color: '#1E2D7A' }}>{v || row.boe || '—'}</div>
          {row.boe && v && <div className="text-[10px] text-gray-400">BOE: {row.boe}</div>}
        </div>
      ),
    },
    {
      header: 'Device',
      key: 'device_identifier',
      render: v => <span className="font-mono text-xs">{v || '—'}</span>,
    },
    {
      header: 'Vehicle',
      key: 'vehicle_number',
      render: v => <span className="text-xs">{v || '—'}</span>,
    },
    {
      header: 'Regime',
      key: 'regime',
      render: v => <span className="text-xs">{v || '—'}</span>,
    },
    {
      header: 'Route',
      key: 'route_name',
      render: (v, row) => (
        <span className="text-xs">
          {row.long_route_name ? `${row.long_route_name} (Long)` : v || '—'}
        </span>
      ),
    },
    {
      header: 'Allocation Point',
      key: 'allocation_point_name',
      render: v => <span className="text-xs">{v || '—'}</span>,
    },
    {
      header: 'Dispatch Date',
      key: 'date',
      render: v => <span className="text-xs whitespace-nowrap">{fmtDate(v)}</span>,
    },
    {
      header: 'Status',
      key: 'status',
      render: v => {
        const color = v === 'AFFIXED' ? '#085E37' : v === 'PENDING' ? '#d97706' : '#64748b';
        return (
          <span className="text-[11px] font-semibold px-2 py-0.5 rounded-full text-white"
            style={{ background: color }}>{v || '—'}</span>
        );
      },
    },
    {
      header: 'Driver',
      key: 'driver_name',
      render: v => <span className="text-xs">{v || '—'}</span>,
    },
  ];

  return (
    <div>
      <PageHeader
        title="Dispatch Records"
        subtitle="Financial overview of all dispatched vehicle records"
      />

      {/* Stats bar */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        {[
          { label: 'Total Records',  value: meta.total || 0,              color: '#1E2D7A' },
          { label: 'This Page',      value: rows.length,                  color: '#1E2D7A' },
          { label: 'Affixed',        value: rows.filter(r => r.status === 'AFFIXED').length,  color: '#085E37' },
          { label: 'Pending',        value: rows.filter(r => r.status === 'PENDING').length,  color: '#d97706' },
        ].map(s => (
          <div key={s.label} className="card py-3 text-center">
            <p className="text-2xl font-extrabold" style={{ color: s.color }}>{s.value}</p>
            <p className="text-xs text-gray-500 mt-0.5">{s.label}</p>
          </div>
        ))}
      </div>

      {/* Filters */}
      <div className="card mb-4">
        <div className="flex flex-wrap gap-3 items-end">
          <div>
            <label className="label">Search</label>
            <input className="input w-48" placeholder="BOE / vehicle / driver…"
              value={filters.search}
              onChange={e => setFilters(f => ({ ...f, search: e.target.value }))}
              onKeyDown={e => e.key === 'Enter' && apply({ ...filters, page: 1 })}
            />
          </div>
          <div>
            <label className="label">Status</label>
            <select className="input w-36" value={filters.status}
              onChange={e => apply({ ...filters, status: e.target.value, page: 1 })}>
              <option value="">All</option>
              <option value="PENDING">Pending</option>
              <option value="AFFIXED">Affixed</option>
            </select>
          </div>
          <div>
            <label className="label">From</label>
            <input type="date" className="input w-36" value={filters.from}
              onChange={e => apply({ ...filters, from: e.target.value, page: 1 })} />
          </div>
          <div>
            <label className="label">To</label>
            <input type="date" className="input w-36" value={filters.to}
              onChange={e => apply({ ...filters, to: e.target.value, page: 1 })} />
          </div>
          <button className="btn-secondary btn-sm" onClick={clear}>Clear</button>
          <button className="btn-primary btn-sm" onClick={() => apply({ ...filters, page: 1 })}>Search</button>
        </div>
      </div>

      <div className="card">
        <DataTable columns={columns} data={rows} loading={loading} emptyMessage="No dispatch records found." />
        <Pagination meta={meta} onPageChange={goPage} />
      </div>
    </div>
  );
}
