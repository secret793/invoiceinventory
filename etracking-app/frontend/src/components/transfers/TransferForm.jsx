import React, { useState, useEffect } from 'react';
import { Select, Input, Textarea } from '../common/FormField';
import { deviceService } from '../../services/deviceService';
import { allocationService } from '../../services/allocationService';
import { distributionService } from '../../services/distributionService';

export default function TransferForm({ onSubmit, loading, onCancel }) {
  const [form, setForm] = useState({
    device_id: '', transfer_type: 'ALLOCATION', to_allocation_point_id: '',
    to_distribution_point_id: '', quantity: 1, notes: '',
  });
  const [devices, setDevices]   = useState([]);
  const [aps, setAps]           = useState([]);
  const [dps, setDps]           = useState([]);
  const [errors, setErrors]     = useState({});
  const [loading2, setLoading2] = useState(false);

  useEffect(() => {
    setLoading2(true);
    Promise.all([
      deviceService.list({ per_page: 200 }),
      allocationService.list(),
      distributionService.list(),
    ]).then(([devRes, apRes, dpRes]) => {
      setDevices(devRes.data || []);
      setAps(apRes || []);
      setDps(dpRes || []);
    }).finally(() => setLoading2(false));
  }, []);

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const validate = () => {
    const e = {};
    if (!form.device_id) e.device_id = 'Device is required';
    if (form.transfer_type === 'ALLOCATION' && !form.to_allocation_point_id) e.to_allocation_point_id = 'Target allocation point required';
    if (form.transfer_type === 'DISTRIBUTION' && !form.to_distribution_point_id) e.to_distribution_point_id = 'Target distribution point required';
    setErrors(e); return !Object.keys(e).length;
  };

  const handleSubmit = (e) => { e.preventDefault(); if (validate()) onSubmit(form); };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Select label="Transfer Type" value={form.transfer_type} onChange={e => set('transfer_type', e.target.value)}>
          <option value="ALLOCATION">Allocation Transfer</option>
          <option value="DISTRIBUTION">Distribution Transfer</option>
          <option value="STORE_TO_AP">Store → Allocation Point</option>
        </Select>

        <Select label="Device" required value={form.device_id} onChange={e => set('device_id', e.target.value)} error={errors.device_id}>
          <option value="">Select device…</option>
          {devices.map(d => <option key={d.id} value={d.id}>{d.device_id} — {d.device_type}</option>)}
        </Select>

        {(form.transfer_type === 'ALLOCATION' || form.transfer_type === 'STORE_TO_AP') && (
          <Select label="To Allocation Point" required value={form.to_allocation_point_id}
            onChange={e => set('to_allocation_point_id', e.target.value)} error={errors.to_allocation_point_id}>
            <option value="">Select allocation point…</option>
            {aps.map(ap => <option key={ap.id} value={ap.id}>{ap.name}</option>)}
          </Select>
        )}

        {form.transfer_type === 'DISTRIBUTION' && (
          <Select label="To Distribution Point" required value={form.to_distribution_point_id}
            onChange={e => set('to_distribution_point_id', e.target.value)} error={errors.to_distribution_point_id}>
            <option value="">Select distribution point…</option>
            {dps.map(dp => <option key={dp.id} value={dp.id}>{dp.name}</option>)}
          </Select>
        )}

        <Input label="Quantity" type="number" min="1" value={form.quantity} onChange={e => set('quantity', e.target.value)} />
      </div>

      <Textarea label="Notes" value={form.notes} onChange={e => set('notes', e.target.value)} rows={2} />

      <div className="flex items-center gap-3 pt-2">
        <button type="submit" disabled={loading || loading2} className="btn-primary">
          {loading ? 'Creating…' : 'Create Transfer'}
        </button>
        {onCancel && <button type="button" onClick={onCancel} className="btn-secondary">Cancel</button>}
      </div>
    </form>
  );
}
