import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { deviceService } from '../../services/deviceService';
import { retrievalService } from '../../services/retrievalService';
import { PieChart, Pie, Cell, Tooltip, Legend, ResponsiveContainer, BarChart, Bar, XAxis, YAxis, CartesianGrid } from 'recharts';

const GN_BLUE  = '#1E2D7A';
const GN_RED   = '#E31E24';
const GN_GREEN = '#085E37';

const STATUS_COLORS_PIE = {
  ONLINE:       '#16a34a',
  OFFLINE:      '#dc2626',
  DAMAGED:      '#ea580c',
  FIXED:        '#9333ea',
  LOST:         '#6b7280',
  RECEIVED:     '#3b82f6',
  CONFIGURED:   '#0ea5e9',
  UNCONFIGURED: '#9ca3af',
  RETRIEVED:    '#6b7280',
};

export default function DashboardPage() {
  const { user } = useAuth();
  const [stats, setStats]         = useState({});
  const [retrievals, setRetrievals] = useState([]);
  const [loading, setLoading]     = useState(true);
  const [now, setNow]             = useState(new Date());

  useEffect(() => {
    Promise.all([
      deviceService.stats().catch(() => ({})),
      retrievalService.list({ per_page: 200, retrieval_status: 'NOT_RETRIEVED' }).catch(() => ({ data: [] })),
    ]).then(([s, r]) => {
      setStats(s || {});
      setRetrievals(r.data || []);
    }).finally(() => setLoading(false));

    const t = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(t);
  }, []);

  const totalDevices   = Object.values(stats).reduce((a, b) => a + (b || 0), 0);
  const onlineCount    = stats['ONLINE']  || 0;
  const offlineCount   = stats['OFFLINE'] || 0;
  const damagedCount   = stats['DAMAGED'] || 0;
  const lostCount      = stats['LOST']    || 0;
  const overdueCount   = retrievals.filter(r => (r.overstay_days || 0) > 0).length;

  const pieData = Object.entries(stats)
    .filter(([, v]) => v > 0)
    .map(([k, v]) => ({ name: k, value: v }));

  const overstayBuckets = [
    { name: '0d',    value: retrievals.filter(r => (r.overstay_days || 0) === 0).length },
    { name: '1–3d',  value: retrievals.filter(r => r.overstay_days >= 1 && r.overstay_days <= 3).length },
    { name: '4–7d',  value: retrievals.filter(r => r.overstay_days >= 4 && r.overstay_days <= 7).length },
    { name: '8–14d', value: retrievals.filter(r => r.overstay_days >= 8 && r.overstay_days <= 14).length },
    { name: '15+d',  value: retrievals.filter(r => r.overstay_days >= 15).length },
  ];

  const statCards = [
    { label: 'Total Devices',    value: totalDevices,  color: GN_BLUE,  icon: '📡', link: '/devices' },
    { label: 'Online',           value: onlineCount,   color: GN_GREEN, icon: '🟢', link: '/devices' },
    { label: 'Offline',          value: offlineCount,  color: '#dc2626', icon: '🔴', link: '/devices' },
    { label: 'Damaged',          value: damagedCount,  color: '#ea580c', icon: '🔧', link: '/devices' },
    { label: 'Lost',             value: lostCount,     color: '#6b7280', icon: '❓', link: '/devices' },
    { label: 'Overdue Retrievals', value: overdueCount, color: GN_RED,  icon: '⚠️', link: '/device-retrievals' },
  ];

  return (
    <div>
      {/* Welcome Banner */}
      <div className="rounded-2xl mb-6 p-6 text-white relative overflow-hidden"
        style={{ background: `linear-gradient(135deg, ${GN_BLUE} 0%, #2a3d96 60%, #1a2970 100%)` }}>
        <div className="relative z-10">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 className="text-2xl font-bold">
                Welcome back, {user?.name?.split(' ')[0] || 'User'}
              </h1>
              <p className="text-blue-200 mt-1 text-sm">GNSW E-Tracking System — Gambia National Standards &amp; Weights</p>
            </div>
            <div className="text-right">
              <p className="text-blue-200 text-xs">Current Time</p>
              <p className="font-mono text-xl font-bold">{now.toLocaleTimeString()}</p>
              <p className="text-blue-300 text-xs">{now.toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
            </div>
          </div>
        </div>
        {/* Decorative circles */}
        <div className="absolute -top-6 -right-6 w-32 h-32 rounded-full opacity-10" style={{ background: 'white' }} />
        <div className="absolute -bottom-8 -left-8 w-40 h-40 rounded-full opacity-10" style={{ background: 'white' }} />
      </div>

      {/* Stat Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        {statCards.map(card => (
          <Link key={card.label} to={card.link}
            className="card flex flex-col items-center justify-center text-center py-5 hover:shadow-md transition-shadow cursor-pointer">
            <span className="text-2xl mb-2">{card.icon}</span>
            <p className="text-3xl font-extrabold" style={{ color: card.color }}>
              {loading ? '—' : card.value}
            </p>
            <p className="text-xs text-gray-500 mt-1 font-medium">{card.label}</p>
          </Link>
        ))}
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div className="card">
          <h3 className="card-title mb-4">Device Status Distribution</h3>
          {pieData.length > 0 ? (
            <ResponsiveContainer width="100%" height={240}>
              <PieChart>
                <Pie data={pieData} cx="50%" cy="50%" outerRadius={80} dataKey="value"
                  label={({ name, value }) => `${name}: ${value}`} labelLine={false} fontSize={11}>
                  {pieData.map((entry, i) => (
                    <Cell key={i} fill={STATUS_COLORS_PIE[entry.name] || '#94a3b8'} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          ) : (
            <div className="flex items-center justify-center h-56 text-gray-400">
              {loading ? 'Loading…' : 'No device data'}
            </div>
          )}
        </div>

        <div className="card">
          <h3 className="card-title mb-4">Overstay Distribution (Not Retrieved)</h3>
          <ResponsiveContainer width="100%" height={240}>
            <BarChart data={overstayBuckets}>
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis dataKey="name" tick={{ fontSize: 11 }} />
              <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
              <Tooltip />
              <Bar dataKey="value" fill={GN_RED} radius={[4, 4, 0, 0]} name="Devices" />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Quick Links */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        {[
          { label: 'Confirmed Dispatch', path: '/confirmed-affixed', icon: '✅', desc: 'Pick for affixing' },
          { label: 'Device Retrievals',  path: '/device-retrievals', icon: '🔙', desc: 'Retrieve & track' },
          { label: 'Monitoring',         path: '/monitoring',        icon: '👁️', desc: 'Live device tracking' },
          { label: 'Transfers',          path: '/transfers',         icon: '🔄', desc: 'Approve & manage' },
        ].map(q => (
          <Link key={q.path} to={q.path}
            className="card flex items-start gap-3 hover:shadow-md transition-shadow cursor-pointer">
            <span className="text-2xl flex-shrink-0">{q.icon}</span>
            <div className="min-w-0">
              <p className="font-semibold text-sm text-gray-900 truncate">{q.label}</p>
              <p className="text-xs text-gray-400">{q.desc}</p>
            </div>
          </Link>
        ))}
      </div>

      {/* Overdue Retrievals Table */}
      {overdueCount > 0 && (
        <div className="card">
          <div className="flex items-center justify-between mb-4">
            <h3 className="card-title" style={{ color: GN_RED }}>
              ⚠️ Overdue Retrievals ({overdueCount})
            </h3>
            <Link to="/device-retrievals" className="btn-danger btn-sm">View All</Link>
          </div>
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>BOE</th><th>Vehicle</th><th>Device</th><th>Overstay Days</th><th>Station</th>
                </tr>
              </thead>
              <tbody className="bg-white">
                {retrievals.filter(r => (r.overstay_days || 0) > 0).slice(0, 10).map(r => (
                  <tr key={r.id}>
                    <td className="font-mono font-medium">{r.boe || '—'}</td>
                    <td>{r.vehicle_number}</td>
                    <td className="font-mono">{r.device_identifier}</td>
                    <td><span className="badge-red">{r.overstay_days} day(s)</span></td>
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
