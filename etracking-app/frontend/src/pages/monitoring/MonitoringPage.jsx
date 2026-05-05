import React from 'react';
import { useMonitoring } from '../../hooks/useMonitoring';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';

export default function MonitoringPage() {
  const { records, meta, loading, fetch } = useMonitoring();

  const columns = [
    { header: 'BOE',          key: 'boe',            render: v => <span className="font-mono font-medium">{v}</span> },
    { header: 'Vehicle',      key: 'vehicle_number' },
    { header: 'Device',       key: 'device_identifier', render: v => v || '—' },
    { header: 'Station',      key: 'allocation_point_name', render: v => v || '—' },
    { header: 'Affix Date',   key: 'affixing_date', render: v => v ? new Date(v).toLocaleDateString() : '—' },
    { header: 'Overstay',     key: 'overstay_days', render: v => (v || 0) > 0 ? <span className="badge-red">{v} days</span> : <span className="badge-green">On time</span> },
    { header: 'Status',       key: 'retrieval_status', render: v => <StatusBadge status={v} /> },
  ];

  return (
    <div>
      <PageHeader title="Monitoring" subtitle="Real-time device tracking and overstay monitoring" />

      <div className="card mb-4">
        <div className="flex gap-3">
          <input type="number" placeholder="Min overstay days" className="input w-40"
            onChange={e => fetch({ overstay_min: e.target.value })} />
          <input type="number" placeholder="Max overstay days" className="input w-40"
            onChange={e => fetch({ overstay_max: e.target.value })} />
          <select className="input w-44" onChange={e => fetch({ retrieval_status: e.target.value })}>
            <option value="">All Statuses</option>
            <option value="NOT_RETRIEVED">Not Retrieved</option>
            <option value="RETRIEVED">Retrieved</option>
            <option value="OVERDUE">Overdue</option>
          </select>
          <button onClick={() => fetch()} className="btn-secondary">Reset</button>
        </div>
      </div>

      <div className="card">
        <DataTable columns={columns} data={records} loading={loading} emptyMessage="No monitoring records found." />
        <Pagination meta={meta} onPageChange={p => fetch({ page: p })} />
      </div>
    </div>
  );
}
