import React from 'react';
import { useNotifications } from '../../hooks/useNotifications';
import { useNotification } from '../../contexts/NotificationContext';
import PageHeader from '../../components/common/PageHeader';
import Pagination from '../../components/common/Pagination';

export default function NotificationsPage() {
  const { notifications, meta, loading, selected, setSelected, changePage, changeFilters, markRead, markUnread, bulkRead, toggleSelect } = useNotifications();
  const { notify } = useNotification();

  const handleBulkRead = async () => {
    try { await bulkRead(selected); setSelected([]); notify.success('Marked as read'); } catch (e) { notify.error(e.message); }
  };

  const parseMessage = (n) => {
    try { const d = JSON.parse(n.data || '{}'); return d.message || n.data || '—'; } catch { return n.data || '—'; }
  };

  return (
    <div>
      <PageHeader title="Notifications" subtitle="System notifications and alerts"
        actions={selected.length > 0 && (
          <button onClick={handleBulkRead} className="btn-primary btn-sm">Mark {selected.length} Read</button>
        )} />

      <div className="card">
        {loading ? (
          <div className="flex justify-center py-12"><div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" /></div>
        ) : notifications.length === 0 ? (
          <div className="text-center py-12 text-gray-400">No notifications</div>
        ) : (
          <div className="divide-y divide-gray-100">
            {notifications.map(n => (
              <div key={n.id}
                className={`flex items-start gap-3 p-4 hover:bg-gray-50 transition-colors ${!n.read_at ? 'bg-blue-50/40' : ''}`}>
                <input type="checkbox" checked={selected.includes(n.id)} onChange={() => toggleSelect(n.id)}
                  className="mt-0.5 rounded border-gray-300 text-blue-600" />
                <div className={`w-2 h-2 rounded-full mt-2 flex-shrink-0 ${n.read_at ? 'bg-gray-300' : 'bg-blue-500'}`} />
                <div className="flex-1 min-w-0">
                  <p className={`text-sm ${n.read_at ? 'text-gray-500' : 'text-gray-900 font-medium'}`}>
                    {parseMessage(n)}
                  </p>
                  <p className="text-xs text-gray-400 mt-0.5">
                    {n.created_at ? new Date(n.created_at).toLocaleString() : '—'}
                    {n.type && <span className="ml-2 badge-gray">{n.type}</span>}
                  </p>
                </div>
                <div className="flex gap-1 flex-shrink-0">
                  {!n.read_at ? (
                    <button onClick={() => markRead(n.id)} className="text-xs text-blue-600 hover:text-blue-800">Mark Read</button>
                  ) : (
                    <button onClick={() => markUnread(n.id)} className="text-xs text-gray-400 hover:text-gray-600">Mark Unread</button>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
        <Pagination
          meta={meta}
          onPageChange={changePage}
          onPerPageChange={(perPage) => changeFilters({ per_page: perPage })}
          allowAll
        />
      </div>
    </div>
  );
}
