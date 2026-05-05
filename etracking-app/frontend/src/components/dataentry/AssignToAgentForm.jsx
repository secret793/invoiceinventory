import React, { useState, useEffect } from 'react';
import { Input, Select, Textarea } from '../common/FormField';
import { configService } from '../../services/configService';

export default function AssignToAgentForm({ assignment, onSubmit, loading, onCancel }) {
  const [form, setForm] = useState({
    boe: '', vehicle_number: '', regime: '', destination_id: '',
    route_id: '', long_route_id: '', manifest_date: '', agency: '',
    agent_contact: '', truck_number: '', driver_name: '', transaction_type: 'SAD',
  });
  const [routes, setRoutes]       = useState([]);
  const [longRoutes, setLongRoutes] = useState([]);
  const [regimes, setRegimes]     = useState([]);
  const [destinations, setDests]  = useState([]);
  const [errors, setErrors]       = useState({});

  useEffect(() => {
    Promise.all([
      configService.routes.list(),
      configService.longRoutes.list(),
      configService.regimes.list(),
      configService.destinations.list(),
    ]).then(([r, lr, reg, dest]) => {
      setRoutes(r || []); setLongRoutes(lr || []); setRegimes(reg || []); setDests(dest || []);
    });
  }, []);

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const validate = () => {
    const e = {};
    if (!form.boe)            e.boe            = 'BOE is required';
    if (!form.vehicle_number) e.vehicle_number = 'Vehicle number is required';
    if (!form.regime)         e.regime         = 'Regime is required';
    if (!form.destination_id) e.destination_id = 'Destination is required';
    setErrors(e);
    return !Object.keys(e).length;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (validate()) onSubmit(form);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="bg-blue-50 rounded-lg p-3 text-sm text-blue-800 mb-2">
        Assigning to allocation point: <strong>{assignment?.allocation_point_name || `#${assignment?.allocation_point_id}`}</strong>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Select label="Transaction Type" value={form.transaction_type} onChange={e => set('transaction_type', e.target.value)}>
          <option value="SAD">SAD</option>
          <option value="T1">T1</option>
          <option value="EX">Export</option>
        </Select>
        <Input label="BOE / SAD Number" required value={form.boe} onChange={e => set('boe', e.target.value)} error={errors.boe} />
        <Input label="Vehicle Number" required value={form.vehicle_number} onChange={e => set('vehicle_number', e.target.value)} error={errors.vehicle_number} />
        <Input label="Truck Number" value={form.truck_number} onChange={e => set('truck_number', e.target.value)} />
        <Input label="Driver Name" value={form.driver_name} onChange={e => set('driver_name', e.target.value)} />
        <Select label="Regime" required value={form.regime} onChange={e => set('regime', e.target.value)} error={errors.regime}>
          <option value="">Select regime…</option>
          {regimes.map(r => <option key={r.id} value={r.name}>{r.name}</option>)}
        </Select>
        <Select label="Destination" required value={form.destination_id} onChange={e => set('destination_id', e.target.value)} error={errors.destination_id}>
          <option value="">Select destination…</option>
          {destinations.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
        </Select>
        <Select label="Route (Short)" value={form.route_id} onChange={e => set('route_id', e.target.value)}>
          <option value="">Select short route…</option>
          {routes.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
        </Select>
        <Select label="Long Route" value={form.long_route_id} onChange={e => set('long_route_id', e.target.value)}>
          <option value="">Select long route…</option>
          {longRoutes.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
        </Select>
        <Input label="Agency" value={form.agency} onChange={e => set('agency', e.target.value)} />
        <Input label="Agent Contact" value={form.agent_contact} onChange={e => set('agent_contact', e.target.value)} />
        <Input label="Manifest Date" type="date" value={form.manifest_date} onChange={e => set('manifest_date', e.target.value)} />
      </div>

      <div className="flex items-center gap-3 pt-2">
        <button type="submit" disabled={loading} className="btn-primary">{loading ? 'Assigning…' : 'Assign to Agent'}</button>
        {onCancel && <button type="button" onClick={onCancel} className="btn-secondary">Cancel</button>}
      </div>
    </form>
  );
}
