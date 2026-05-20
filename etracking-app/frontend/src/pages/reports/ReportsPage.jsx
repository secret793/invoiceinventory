import React, { useState } from 'react';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import { downloadFile } from '../../utils/downloadFile';

const REPORTS = [
  { key: 'confirmed-affix',          label: 'Confirmed Affix Report',             icon: '✅', url: '/api/reports/confirmed-affix',          filename: 'confirmed-affix.csv',          desc: 'All confirmed affixed records' },
  { key: 'device-retrieval',         label: 'Device Retrieval Report',             icon: '🔙', url: '/api/reports/device-retrieval',         filename: 'device-retrieval.csv',         desc: 'Device retrieval records' },
  { key: 'device-retrieval-2',       label: 'Device Retrieval Report (Detailed)',  icon: '📋', url: '/api/reports/device-retrieval-2',       filename: 'device-retrieval-detailed.csv', desc: 'Detailed retrieval with station info' },
  { key: 'receipts',                 label: 'Receipts Report',                     icon: '🧾', url: '/api/reports/receipts',                filename: 'receipts.csv',                 desc: 'All receipt records' },
  { key: 'generated-receipts',       label: 'Generated Receipts',                  icon: '📄', url: '/api/reports/generated-receipts',      filename: 'generated-receipts.csv',       desc: 'Only generated/issued receipts' },
  { key: 'dispatch-finance-records', label: 'Dispatch Finance Records',            icon: '💰', url: '/api/reports/dispatch-finance-records', filename: 'dispatch-finance-records.csv', desc: 'Finance approved dispatch records' },
  { key: 'overstay-receipts',        label: 'Overstay Receipts',                   icon: '⏰', url: '/api/reports/overstay-receipts',       filename: 'overstay-receipts.csv',        desc: 'Receipts with overstay charges' },
  { key: 'overstay-invoices',        label: 'Overstay Invoices',                   icon: '🧾', url: '/api/reports/overstay-invoices',       filename: 'overstay-invoices.csv',        desc: 'Invoices for overstay charges' },
  { key: 'overstay-devices',         label: 'Overstay Devices (CSV)',              icon: '📱', url: '/api/reports/overstay-devices',        filename: 'overstay-devices.csv',         desc: 'Devices with overstay violations' },
];

export default function ReportsPage() {
  const { notify }          = useNotification();
  const [from, setFrom]     = useState('');
  const [to, setTo]         = useState('');
  const [loading, setLoading] = useState({});

  const handleDownload = async (report) => {
    setLoading(l => ({ ...l, [report.key]: true }));
    try {
      const p = [];
      if (from) p.push(`from=${from}`);
      if (to)   p.push(`to=${to}`);
      const url = p.length ? `${report.url}?${p.join('&')}` : report.url;
      await downloadFile(url, report.filename);
    } catch {
      notify.error(`Failed to download ${report.label}`);
    } finally {
      setLoading(l => ({ ...l, [report.key]: false }));
    }
  };

  return (
    <div>
      <PageHeader title="Reports" subtitle="Download CSV reports for all modules" />

      <div className="card mb-6">
        <p className="label mb-2">Date Range Filter (applies to all reports)</p>
        <div className="flex items-center gap-3">
          <input type="date" value={from} onChange={e => setFrom(e.target.value)} className="input w-40" />
          <span className="text-gray-400">–</span>
          <input type="date" value={to} onChange={e => setTo(e.target.value)} className="input w-40" />
          <button onClick={() => { setFrom(''); setTo(''); }} className="btn-secondary btn-sm">Clear</button>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {REPORTS.map(r => (
          <button key={r.key} onClick={() => handleDownload(r)} disabled={loading[r.key]}
            className="card hover:shadow-md hover:border-blue-200 transition-all group cursor-pointer text-left w-full disabled:opacity-60">
            <div className="flex items-center gap-3 mb-2">
              <span className="text-2xl">{r.icon}</span>
              <h3 className="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">{r.label}</h3>
            </div>
            <p className="text-sm text-gray-500">{r.desc}</p>
            <div className="mt-3 flex items-center gap-2 text-xs text-blue-600 font-medium">
              {loading[r.key] ? (
                <>
                  <span className="w-4 h-4 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin inline-block" />
                  Downloading…
                </>
              ) : (
                <>
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  Download CSV
                </>
              )}
            </div>
          </button>
        ))}
      </div>
    </div>
  );
}
