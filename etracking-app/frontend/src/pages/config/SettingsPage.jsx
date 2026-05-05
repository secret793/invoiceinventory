import React, { useState, useEffect } from 'react';
import { configService } from '../../services/configService';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';

export default function SettingsPage() {
  const { notify } = useNotification();
  const [settings, setSettings] = useState([]);
  const [loading, setLoading]   = useState(false);
  const [editing, setEditing]   = useState({});
  const [saving, setSaving]     = useState({});

  useEffect(() => {
    setLoading(true);
    configService.settings.list().then(s => { setSettings(s || []); }).catch(() => {}).finally(() => setLoading(false));
  }, []);

  const handleChange = (id, val) => setEditing(e => ({ ...e, [id]: val }));

  const handleSave = async (setting) => {
    const val = editing[setting.id] ?? setting.value;
    setSaving(s => ({ ...s, [setting.id]: true }));
    try {
      await configService.settings.update(setting.id, val);
      notify.success(`"${setting.key}" updated`);
      setSettings(prev => prev.map(s => s.id === setting.id ? { ...s, value: val } : s));
    } catch (e) { notify.error(e.message); }
    finally     { setSaving(s => ({ ...s, [setting.id]: false })); }
  };

  return (
    <div>
      <PageHeader title="System Settings" subtitle="Global configuration values"
        breadcrumbs={[{ label: 'Configuration' }, { label: 'Settings' }]} />

      <div className="card">
        {loading ? (
          <div className="flex justify-center py-12"><div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" /></div>
        ) : settings.length === 0 ? (
          <p className="text-center py-12 text-gray-400">No settings configured.</p>
        ) : (
          <div className="divide-y divide-gray-100">
            {settings.map(s => (
              <div key={s.id} className="flex items-center justify-between gap-4 py-4">
                <div className="min-w-0">
                  <p className="font-medium text-gray-900 text-sm">{s.key}</p>
                  {s.description && <p className="text-xs text-gray-400 mt-0.5">{s.description}</p>}
                </div>
                <div className="flex items-center gap-2 flex-shrink-0">
                  <input type="text" defaultValue={s.value || ''}
                    onChange={e => handleChange(s.id, e.target.value)}
                    className="input w-48 text-sm" />
                  <button onClick={() => handleSave(s)} disabled={saving[s.id]} className="btn-primary btn-sm">
                    {saving[s.id] ? 'Saving…' : 'Save'}
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
