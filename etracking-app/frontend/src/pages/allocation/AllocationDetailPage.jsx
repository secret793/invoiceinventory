import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { allocationService } from '../../services/allocationService';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';

export default function AllocationDetailPage() {
  const { id }          = useParams();
  const [ap, setAp]     = useState(null);
  const [devices, setDevices] = useState([]);
  const [meta, setMeta] = useState({});
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);

  useEffect(() => {
    allocationService.get(id).then(setAp).catch(() => {});
  }, [id]);

  useEffect(() => {
    setLoading(true);
    allocationService.devices(id, { page, per_page: 25 }).then(r => {
      setDevices(r.data || []); setMeta(r.meta || {});
    }).catch(() => {}).finally(() => setLoading(false));
  }, [id, page]);

  const columns = [
    { header: 'Device ID',    key: 'device_id',    render: v => <span className="font-mono font-medium">{v}</span> },
    { header: 'Type',         key: 'device_type' },
    { header: 'Serial',       key: 'serial_number', render: v => v || '—' },
    { header: 'SIM',          key: 'sim_number',    render: v => v || '—' },
    { header: 'Status',       key: 'status',        render: v => <StatusBadge status={v} /> },
    { header: 'Date Received',key: 'date_received', render: v => v ? new Date(v).toLocaleDateString() : '—' },
  ];

  return (
    <div>
      <PageHeader
        title={ap?.name || 'Allocation Point'}
        subtitle={ap?.location || ''}
        breadcrumbs={[{ label: 'Allocation Points', path: '/allocation' }, { label: ap?.name || id }]}
        actions={
          <Link to={`/data-entry/${id}`} className="btn-primary">Data Entry</Link>
        } />

      <div className="grid grid-cols-3 gap-4 mb-6">
        <div className="card text-center">
          <p className="text-3xl font-bold text-blue-600">{ap?.received_count ?? 0}</p>
          <p className="text-gray-500 text-sm mt-1">Received</p>
        </div>
        <div className="card text-center">
          <p className="text-3xl font-bold text-green-600">{ap?.other_count ?? 0}</p>
          <p className="text-gray-500 text-sm mt-1">Active</p>
        </div>
        <div className="card text-center">
          <p className="text-3xl font-bold text-gray-700">{meta.total ?? 0}</p>
          <p className="text-gray-500 text-sm mt-1">Total</p>
        </div>
      </div>

      <div className="card">
        <DataTable columns={columns} data={devices} loading={loading} emptyMessage="No devices at this allocation point." />
        <Pagination meta={meta} onPageChange={setPage} />
      </div>
    </div>
  );
}
