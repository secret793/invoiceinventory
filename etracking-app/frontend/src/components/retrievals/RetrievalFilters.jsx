import React, { useState } from 'react';

export default function RetrievalFilters({ onFilter, allocationPoints = [], destinations = [] }) {
  const [f, setF] = useState({
    search: '', retrieval_status: '', payment_status: '',
    allocation_point_id: '', destination_id: '', route_type: '',
    overdue: '', from: '', to: '', overstay_min: '', overstay_max: '',
  });

  const set = (k, v) => {
    const upd = { ...f, [k]: v };
    setF(upd);
    onFilter(upd);
  };

  return (
    <div className="bg-white rounded-xl border border-gray-100 p-4 mb-4">
      <div className="flex flex-wrap gap-3">
        <input type="text" value={f.search} onChange={e => set('search', e.target.value)}
          placeholder="Search BOE, vehicle, device…" className="input w-52" />

        <select value={f.retrieval_status} onChange={e => set('retrieval_status', e.target.value)} className="input w-40">
          <option value="">Retrieval Status</option>
          <option value="NOT_RETRIEVED">Not Retrieved</option>
          <option value="RETRIEVED">Retrieved</option>
          <option value="OVERDUE">Overdue</option>
        </select>

        <select value={f.payment_status} onChange={e => set('payment_status', e.target.value)} className="input w-36">
          <option value="">Payment Status</option>
          <option value="PP">Pending</option>
          <option value="PAID">Paid</option>
          <option value="WAIVED">Waived</option>
          <option value="EXEMPTED">Exempted</option>
        </select>

        <select value={f.allocation_point_id} onChange={e => set('allocation_point_id', e.target.value)} className="input w-44">
          <option value="">All Stations</option>
          {allocationPoints.map(ap => <option key={ap.id} value={ap.id}>{ap.name}</option>)}
        </select>

        <select value={f.destination_id} onChange={e => set('destination_id', e.target.value)} className="input w-40">
          <option value="">All Destinations</option>
          {destinations.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
        </select>

        <select value={f.route_type} onChange={e => set('route_type', e.target.value)} className="input w-36">
          <option value="">Route Type</option>
          <option value="short">Short Route</option>
          <option value="long">Long Route</option>
        </select>

        <div className="flex items-center gap-2">
          <input type="date" value={f.from} onChange={e => set('from', e.target.value)} className="input w-36" title="From date" />
          <span className="text-gray-400 text-sm">–</span>
          <input type="date" value={f.to} onChange={e => set('to', e.target.value)} className="input w-36" title="To date" />
        </div>

        <div className="flex items-center gap-2">
          <input type="number" value={f.overstay_min} onChange={e => set('overstay_min', e.target.value)}
            placeholder="Min overstay" className="input w-28" min="0" />
          <span className="text-gray-400 text-sm">–</span>
          <input type="number" value={f.overstay_max} onChange={e => set('overstay_max', e.target.value)}
            placeholder="Max overstay" className="input w-28" min="0" />
        </div>

        <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
          <input type="checkbox" checked={f.overdue === '1'} onChange={e => set('overdue', e.target.checked ? '1' : '')}
            className="rounded border-gray-300 text-blue-600" />
          Overdue only
        </label>
      </div>
    </div>
  );
}
