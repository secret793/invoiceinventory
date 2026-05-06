import React, { useState } from 'react';
import { Input, Select, FormField } from '../common/FormField';

const DEVICE_TYPES  = ['JT701', 'JT709A', 'JT709C'];
const DEVICE_STATUS = ['UNCONFIGURED', 'CONFIGURED', 'ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST'];
const SIM_OPERATORS = ['Africell', 'Gamcel', 'QCell', 'Comium'];

export default function DeviceForm({ initial = {}, onSubmit, loading = false, onCancel, allocationPoints = [], distributionPoints = [] }) {
  const today = new Date().toISOString().split('T')[0];
  const defaultBatch = 'BATCH-' + today.replace(/-/g, '');

  const [form, setForm] = useState({
    device_type: '', device_id: '', serial_number: '', sim_number: '',
    sim_operator: '', batch_number: defaultBatch, date_received: today,
    status: 'UNCONFIGURED', allocation_point_id: '', distribution_point_id: '',
    is_configured: 0, notes: '', icloud_device_guid: '',
    ...initial,
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

  const isConfigured = Number(form.is_configured) === 1 || form.is_configured === true;

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {/* Configured toggle */}
      <div className="flex items-center gap-3 p-3 rounded-lg" style={{ background: '#f8f9ff', border: '1px solid #e0e4f8' }}>
        <label className="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" className="sr-only peer"
            checked={isConfigured}
            onChange={e => set('is_configured', e.target.checked ? 1 : 0)} />
          <div className="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-700" style={{ '--tw-bg-opacity': 1 }} />
        </label>
        <span className="text-sm font-medium text-gray-700">
          Device is configured {isConfigured ? <span className="text-green-600 font-semibold">(SIM fields required)</span> : <span className="text-gray-400">(SIM fields optional)</span>}
        </span>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Select label="Device Type" required value={form.device_type} onChange={e => set('device_type', e.target.value)} error={errors.device_type}>
          <option value="">Select type…</option>
          {DEVICE_TYPES.map(t => <option key={t} value={t}>{t}</option>)}
        </Select>

        <Input label="Device ID" required value={form.device_id} onChange={e => set('device_id', e.target.value)} error={errors.device_id} placeholder="e.g. GT-001" />

        <Input label="Serial Number" value={form.serial_number || ''} onChange={e => set('serial_number', e.target.value)} placeholder="Serial / IMEI" />

        <Input label="Batch Number" value={form.batch_number || ''} onChange={e => set('batch_number', e.target.value)} placeholder={defaultBatch} />

        <Input label="Date Received" type="date" required value={form.date_received || ''} onChange={e => set('date_received', e.target.value)} error={errors.date_received} max={today} />

        <Select label="Status" value={form.status || 'UNCONFIGURED'} onChange={e => set('status', e.target.value)}>
          {DEVICE_STATUS.map(s => <option key={s} value={s}>{s}</option>)}
        </Select>

        <Input label="SIM Number" required={isConfigured} value={form.sim_number || ''}
          onChange={e => set('sim_number', e.target.value)} placeholder="SIM card number" />

        <Select label="SIM Operator" value={form.sim_operator || ''} onChange={e => set('sim_operator', e.target.value)}>
          <option value="">Select operator…</option>
          {SIM_OPERATORS.map(o => <option key={o} value={o}>{o}</option>)}
        </Select>

        <Select label="Allocation Point" value={form.allocation_point_id || ''} onChange={e => set('allocation_point_id', e.target.value)}>
          <option value="">None</option>
          {allocationPoints.map(ap => <option key={ap.id} value={ap.id}>{ap.name}</option>)}
        </Select>

        <Input label="iCloud Device GUID" value={form.icloud_device_guid || ''} onChange={e => set('icloud_device_guid', e.target.value)} placeholder="Optional — auto-filled by Sync" />
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
