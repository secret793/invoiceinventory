import React, { useState } from 'react';
import { Input, Select, FormField } from '../common/FormField';

const DEVICE_TYPES  = ['GPS Tracker', 'Mobile Unit', 'Fixed Unit', 'Handheld'];
const DEVICE_STATUS = ['UNCONFIGURED', 'CONFIGURED', 'ALLOCATED', 'DISTRIBUTED', 'ACTIVE', 'FAULTY', 'LOST'];
const SIM_OPERATORS = ['Africell', 'Gamcel', 'QCell', 'Comium'];

export default function DeviceForm({ initial = {}, onSubmit, loading = false, onCancel, allocationPoints = [], distributionPoints = [] }) {
  const [form, setForm] = useState({
    device_type: '', device_id: '', serial_number: '', sim_number: '',
    sim_operator: '', batch_number: '', date_received: '', status: 'UNCONFIGURED',
    allocation_point_id: '', distribution_point_id: '', is_configured: 0,
    notes: '', ...initial,
  });
  const [errors, setErrors] = useState({});

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const validate = () => {
    const e = {};
    if (!form.device_type)   e.device_type   = 'Device type is required';
    if (!form.device_id)     e.device_id     = 'Device ID is required';
    if (!form.date_received) e.date_received = 'Date received is required';
    setErrors(e);
    return !Object.keys(e).length;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (validate()) onSubmit(form);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Select label="Device Type" required value={form.device_type} onChange={e => set('device_type', e.target.value)} error={errors.device_type}>
          <option value="">Select type…</option>
          {DEVICE_TYPES.map(t => <option key={t} value={t}>{t}</option>)}
        </Select>
        <Input label="Device ID" required value={form.device_id} onChange={e => set('device_id', e.target.value)} error={errors.device_id} placeholder="e.g. GT-001" />
        <Input label="Serial Number" value={form.serial_number || ''} onChange={e => set('serial_number', e.target.value)} placeholder="Serial / IMEI" />
        <Input label="SIM Number" value={form.sim_number || ''} onChange={e => set('sim_number', e.target.value)} placeholder="SIM card number" />
        <Select label="SIM Operator" value={form.sim_operator || ''} onChange={e => set('sim_operator', e.target.value)}>
          <option value="">Select operator…</option>
          {SIM_OPERATORS.map(o => <option key={o} value={o}>{o}</option>)}
        </Select>
        <Input label="Batch Number" value={form.batch_number || ''} onChange={e => set('batch_number', e.target.value)} placeholder="BATCH-2024-001" />
        <Input label="Date Received" type="date" required value={form.date_received || ''} onChange={e => set('date_received', e.target.value)} error={errors.date_received} />
        <Select label="Status" value={form.status || 'UNCONFIGURED'} onChange={e => set('status', e.target.value)}>
          {DEVICE_STATUS.map(s => <option key={s} value={s}>{s}</option>)}
        </Select>
        <Select label="Allocation Point" value={form.allocation_point_id || ''} onChange={e => set('allocation_point_id', e.target.value)}>
          <option value="">None</option>
          {allocationPoints.map(ap => <option key={ap.id} value={ap.id}>{ap.name}</option>)}
        </Select>
        <Select label="Distribution Point" value={form.distribution_point_id || ''} onChange={e => set('distribution_point_id', e.target.value)}>
          <option value="">None</option>
          {distributionPoints.map(dp => <option key={dp.id} value={dp.id}>{dp.name}</option>)}
        </Select>
      </div>
      <FormField label="Notes">
        <textarea className="input" rows={2} value={form.notes || ''} onChange={e => set('notes', e.target.value)} placeholder="Additional notes…" />
      </FormField>
      <div className="flex items-center gap-3 pt-2">
        <button type="submit" disabled={loading} className="btn-primary">{loading ? 'Saving…' : 'Save Device'}</button>
        {onCancel && <button type="button" onClick={onCancel} className="btn-secondary">Cancel</button>}
      </div>
    </form>
  );
}
