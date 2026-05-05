import React, { createContext, useContext, useState, useCallback, useEffect, useRef } from 'react';
import { notificationService } from '../services/notificationService';
import { useAuth } from './AuthContext';

const NotificationContext = createContext(null);

export function NotificationProvider({ children }) {
  const { user } = useAuth();
  const [unreadCount, setUnreadCount] = useState(0);
  const [toasts, setToasts]           = useState([]);
  const intervalRef = useRef(null);

  const fetchUnread = useCallback(async () => {
    if (!user) return;
    try {
      const count = await notificationService.unreadCount();
      setUnreadCount(count);
    } catch { /* ignore */ }
  }, [user]);

  useEffect(() => {
    if (!user) return;
    fetchUnread();
    intervalRef.current = setInterval(fetchUnread, 30_000);
    return () => clearInterval(intervalRef.current);
  }, [user, fetchUnread]);

  const addToast = useCallback((message, type = 'info', duration = 4000) => {
    const id = Date.now() + Math.random();
    setToasts(prev => [...prev, { id, message, type }]);
    setTimeout(() => setToasts(prev => prev.filter(t => t.id !== id)), duration);
  }, []);

  const removeToast = useCallback((id) => {
    setToasts(prev => prev.filter(t => t.id !== id));
  }, []);

  const notify = {
    success: (msg) => addToast(msg, 'success'),
    error:   (msg) => addToast(msg, 'error'),
    warning: (msg) => addToast(msg, 'warning'),
    info:    (msg) => addToast(msg, 'info'),
  };

  return (
    <NotificationContext.Provider value={{
      unreadCount, setUnreadCount, fetchUnread, toasts, removeToast, notify,
    }}>
      {children}
      <ToastContainer toasts={toasts} onRemove={removeToast} />
    </NotificationContext.Provider>
  );
}

function ToastContainer({ toasts, onRemove }) {
  if (!toasts.length) return null;
  return (
    <div className="fixed bottom-4 right-4 z-50 flex flex-col gap-2 w-80">
      {toasts.map(t => (
        <div key={t.id}
          className={`flex items-start gap-3 rounded-xl shadow-lg p-4 text-sm font-medium transition-all
            ${t.type === 'success' ? 'bg-green-600 text-white' :
              t.type === 'error'   ? 'bg-red-600 text-white'   :
              t.type === 'warning' ? 'bg-yellow-500 text-white' :
                                     'bg-gray-800 text-white'}`}>
          <span className="flex-1">{t.message}</span>
          <button onClick={() => onRemove(t.id)}
            className="flex-shrink-0 opacity-70 hover:opacity-100 text-lg leading-none">×</button>
        </div>
      ))}
    </div>
  );
}

export function useNotification() {
  const ctx = useContext(NotificationContext);
  if (!ctx) throw new Error('useNotification must be used within NotificationProvider');
  return ctx;
}
