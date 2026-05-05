import React from 'react';

export function FormField({ label, required, error, children, hint }) {
  return (
    <div>
      {label && (
        <label className="label">
          {label} {required && <span className="text-red-500">*</span>}
        </label>
      )}
      {children}
      {hint  && !error && <p className="mt-1 text-xs text-gray-400">{hint}</p>}
      {error && <p className="mt-1 text-xs text-red-500">{error}</p>}
    </div>
  );
}

export function Input({ label, required, error, hint, ...props }) {
  return (
    <FormField label={label} required={required} error={error} hint={hint}>
      <input className={`input ${error ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : ''}`} {...props} />
    </FormField>
  );
}

export function Select({ label, required, error, hint, options = [], children, ...props }) {
  return (
    <FormField label={label} required={required} error={error} hint={hint}>
      <select className={`input ${error ? 'border-red-400' : ''}`} {...props}>
        {children || options.map(o => (
          <option key={o.value ?? o} value={o.value ?? o}>{o.label ?? o}</option>
        ))}
      </select>
    </FormField>
  );
}

export function Textarea({ label, required, error, hint, rows = 3, ...props }) {
  return (
    <FormField label={label} required={required} error={error} hint={hint}>
      <textarea className={`input resize-none ${error ? 'border-red-400' : ''}`} rows={rows} {...props} />
    </FormField>
  );
}
