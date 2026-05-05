import api from './api';

export const notificationService = {
  list:        (params = {}) => api.get('/notifications', { params }).then(r => r.data),
  unreadCount: ()            => api.get('/notifications/unread-count').then(r => r.data.data?.count ?? 0),
  markRead:    (id)          => api.post(`/notifications/${id}/read`).then(r => r.data),
  markUnread:  (id)          => api.post(`/notifications/${id}/unread`).then(r => r.data),
  bulkRead:    (ids)         => api.post('/notifications/bulk-read', { ids }).then(r => r.data),
  bulkUnread:  (ids)         => api.post('/notifications/bulk-unread', { ids }).then(r => r.data),
};
