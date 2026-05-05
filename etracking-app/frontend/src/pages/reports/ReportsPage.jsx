import React, { useState } from 'react';
import PageHeader from '../../components/common/PageHeader';

const REPORTS = [
  { key: 'confirmed-affix',          label: 'Confirmed Affix Report',          icon: '✅', url: '/api/reports/confirmed-affix',          desc: 'All confirmed affixed records' },
  { key: 'device-retrieval',         label: 'Device Retrieval Report',          icon: '🔙', url: '/api/reports/device-retrieval',         desc: 'Device retrieval records' },
  { key: 'device-retrieval-2',       label: 'Device Retrieval Report (Detailed)', icon: '📋', url: '/api/reports/device-retrieval-2',    desc: 'Detailed retrieval with station info' },
  { key: 'receipts',                 label: 'Receipts Report',                  icon: '🧾', url: '/api/reports/receipts',                desc: 'All receipt records' },
  { key: 'generated-receipts',       label: 'Generated Receipts',               icon: '📄', url: '/api/reports/generated-receipts',      desc: 'Only generated/issued receipts' },
  { key: 'dispatch-finance-records', label: 'Dispatch Finance Records',         icon: '💰', url: '/api/reports/dispatch-finance-records', desc: 'Finance approved dispatch records' },
  { key: 'overstay-receipts',        label: 'Overstay Receipts',                icon: '⏰', url: '/api/reports/overstay-receipts',       desc: 'Receipts with overstay charges' },
  { key: 'overstay-invoices',        label: 'Overstay Invoices',                icon: '🧾', url: '/api/reports/overstay-invoices',       desc: 'Invoices for overstay charges' },
  { key: 'overstay-devices',         label: 'Overstay Devices (CSV)',           icon: '📱', url: '/api/reports/overstay-devices',        desc: 'Devices with overstay violations' },
];

export default function ReportsPage() {
  const [from, setFrom] = useState('');
  const [to, setTo]     = useState('');

  const buildUrl = (base) => {
    const p = [];
    if (from) p.push(`from=${from}`);
    if (to)   p.push(`to=${to}`);
    return p.length ? `${base}?${p.join('&')}` : base;
  };

  return (
    <div>
      <PageHeader title="Reports" subtitle="Download CSV reports for all modules" />

      <div className="card mb-6">
        <p className="label mb-2">Date Range Filter (applies to all reports)</p>
        <div className="flex items-center gap-3">
          <input type="date" value={from} onChange={e => setFrom(e.target.value)} className="input w-40" placeholder="From" />
          <span className="text-gray-400">–</span>
          <input type="date" value={to} onChange={e => setTo(e.target.value)} className="input w-40" placeholder="To" />
          <button onClick={() => { setFrom(''); setTo(''); }} className="btn-secondary btn-sm">Clear</button>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {REPORTS.map(r => (
          <a key={r.key} href={buildUrl(r.url)} target="_blank" rel="noreferrer"
            className="card hover:shadow-md hover:border-blue-200 transition-all group cursor-pointer">
            <div className="flex items-center gap-3 mb-2">
              <span className="text-2xl">{r.icon}</span>
              <h3 className="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">{r.label}</h3>
            </div>
            <p className="text-sm text-gray-500">{r.desc}</p>
            <div className="mt-3 flex items-center gap-2 text-xs text-blue-600 font-medium">
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Download CSV
            </div>
          </a>
        ))}
      </div>
    </div>
  );
}
