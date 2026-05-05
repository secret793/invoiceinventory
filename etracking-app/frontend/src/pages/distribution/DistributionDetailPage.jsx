import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { distributionService } from '../../services/distributionService';
import PageHeader from '../../components/common/PageHeader';
import DataTable from '../../components/common/DataTable';
import Pagination from '../../components/common/Pagination';
import StatusBadge from '../../components/common/StatusBadge';

export default function DistributionDetailPage() {
  const { id } = useParams();
  const [dp, setDp]         = useState(null);
  const [devices, setDevices] = useState([]);
  const [meta, setMeta]     = useState({});
  const [loading, setLoading] = useState(true);
  const [page, setPage]     = useState(1);

  useEffect(() => { distributionService.get(id).then(setDp).catch(() => {}); }, [id]);

  useEffect(() => {
    setLoading(true);
    distributionService.devices(id, { page, per_page: 25 }).then(r => {
      setDevices(r.data || []); setMeta(r.meta || {});
    }).catch(() => {}).finally(() => setLoading(false));
  }, [id, page]);

  const columns = [
    { header: 'Device ID',    key: 'device_id',    render: v => <span className="font-mono font-medium">{v}</span> },
    { header: 'Type',         key: 'device_type' },
    { header: 'Serial',       key: 'serial_number', render: v => v || '—' },
    { header: 'Status',       key: 'status',        render: v => <StatusBadge status={v} /> },
    { header: 'Date Received',key: 'date_received', render: v => v ? new Date(v).toLocaleDateString() : '—' },
  ];

  return (
    <div>
      <PageHeader title={dp?.name || 'Distribution Point'} subtitle={dp?.location || ''}
        breadcrumbs={[{ label: 'Distribution', path: '/distribution' }, { label: dp?.name || id }]} />
      <div className="card">
        <DataTable columns={columns} data={devices} loading={loading} emptyMessage="No devices at this distribution point." />
        <Pagination meta={meta} onPageChange={setPage} />
      </div>
    </div>
  );
}
