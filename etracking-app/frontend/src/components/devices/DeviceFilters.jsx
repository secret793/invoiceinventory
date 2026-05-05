import React, { useState } from 'react';

const STATUSES = ['', 'UNCONFIGURED', 'CONFIGURED', 'ALLOCATED', 'DISTRIBUTED', 'ACTIVE', 'FAULTY', 'LOST', 'RETURNED'];

export default function DeviceFilters({ onFilter, allocationPoints = [], distributionPoints = [] }) {
  const [f, setF] = useState({ status: '', search: '', allocation_point_id: '', distribution_point_id: '' });

  const set = (k, v) => {
    const updated = { ...f, [k]: v };
    setF(updated);
    onFilter(updated);
  };

  return (
    <div className="flex flex-wrap gap-3 items-center">
      <input type="text" value={f.search} onChange={e => set('search', e.target.value)}
        placeholder="Search device ID, serial…" className="input w-52" />
      <select value={f.status} onChange={e => set('status', e.target.value)} className="input w-44">
        <option value="">All Statuses</option>
        {STATUSES.filter(Boolean).map(s => <option key={s} value={s}>{s}</option>)}
      </select>
      <select value={f.allocation_point_id} onChange={e => set('allocation_point_id', e.target.value)} className="input w-44">
        <option value="">All Allocation Points</option>
        {allocationPoints.map(ap => <option key={ap.id} value={ap.id}>{ap.name}</option>)}
      </select>
      <select value={f.distribution_point_id} onChange={e => set('distribution_point_id', e.target.value)} className="input w-44">
        <option value="">All Distribution Points</option>
        {distributionPoints.map(dp => <option key={dp.id} value={dp.id}>{dp.name}</option>)}
      </select>
    </div>
  );
}
