import React, { useEffect, useState } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { deviceService } from '../../services/deviceService';
import { retrievalService } from '../../services/retrievalService';
import StatCard from '../../components/common/StatCard';
import PageHeader from '../../components/common/PageHeader';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend } from 'recharts';

const PIE_COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280'];

export default function DashboardPage() {
  const { user } = useAuth();
  const [stats, setStats]         = useState({});
  const [retrievals, setRetrievals] = useState([]);
  const [loading, setLoading]     = useState(true);

  useEffect(() => {
    Promise.all([
      deviceService.stats().catch(() => ({})),
      retrievalService.list({ per_page: 200, retrieval_status: 'NOT_RETRIEVED' }).catch(() => ({ data: [] })),
    ]).then(([s, r]) => {
      setStats(s || {});
      setRetrievals(r.data || []);
    }).finally(() => setLoading(false));
  }, []);

  const totalDevices   = Object.values(stats).reduce((a, b) => a + b, 0);
  const overdueCount   = retrievals.filter(r => (r.overstay_days || 0) > 0).length;
  const activeCount    = stats['ACTIVE'] || stats['IN_USE'] || 0;
  const configuredCount = stats['CONFIGURED'] || 0;

  const statusChartData = Object.entries(stats).map(([k, v]) => ({ name: k, value: v }));

  const overstayBuckets = [
    { name: '0 days',    value: retrievals.filter(r => r.overstay_days === 0).length },
    { name: '1–3 days',  value: retrievals.filter(r => r.overstay_days >= 1 && r.overstay_days <= 3).length },
    { name: '4–7 days',  value: retrievals.filter(r => r.overstay_days >= 4 && r.overstay_days <= 7).length },
    { name: '8–14 days', value: retrievals.filter(r => r.overstay_days >= 8 && r.overstay_days <= 14).length },
    { name: '15+ days',  value: retrievals.filter(r => r.overstay_days >= 15).length },
  ];

  return (
    <div>
      <PageHeader title={`Welcome, ${user?.name?.split(' ')[0] || 'User'}`}
        subtitle="Here's an overview of the GNSW E-Tracking System" />

      {/* Stat Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <StatCard label="Total Devices"    value={totalDevices}   icon="📱" color="blue" />
        <StatCard label="Active Devices"   value={activeCount}    icon="✅" color="green" />
        <StatCard label="Configured"       value={configuredCount} icon="⚙️" color="purple" />
        <StatCard label="Overdue Retrievals" value={overdueCount} icon="⚠️" color="red" />
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div className="card">
          <h3 className="card-title mb-4">Device Status Distribution</h3>
          {statusChartData.length > 0 ? (
            <ResponsiveContainer width="100%" height={220}>
              <PieChart>
                <Pie data={statusChartData} cx="50%" cy="50%" outerRadius={80}
                  dataKey="value" label={({ name, value }) => `${name}: ${value}`} labelLine={false} fontSize={11}>
                  {statusChartData.map((_, i) => <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />)}
                </Pie>
                <Tooltip />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          ) : (
            <div className="flex items-center justify-center h-56 text-gray-400">No device data</div>
          )}
        </div>

        <div className="card">
          <h3 className="card-title mb-4">Overstay Distribution</h3>
          <ResponsiveContainer width="100%" height={220}>
            <BarChart data={overstayBuckets}>
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis dataKey="name" tick={{ fontSize: 11 }} />
              <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
              <Tooltip />
              <Bar dataKey="value" fill="#ef4444" radius={[4, 4, 0, 0]} name="Retrievals" />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Recent overdue retrievals */}
      {overdueCount > 0 && (
        <div className="card">
          <h3 className="card-title mb-4">⚠️ Overdue Retrievals ({overdueCount})</h3>
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>BOE</th><th>Vehicle</th><th>Device</th><th>Overstay Days</th><th>Station</th>
                </tr>
              </thead>
              <tbody className="bg-white">
                {retrievals.filter(r => r.overstay_days > 0).slice(0, 10).map(r => (
                  <tr key={r.id}>
                    <td className="font-medium">{r.boe}</td>
                    <td>{r.vehicle_number}</td>
                    <td>{r.device_identifier}</td>
                    <td><span className="badge-red">{r.overstay_days} days</span></td>
                    <td>{r.allocation_point_name || '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
