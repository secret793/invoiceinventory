import api from './api';

export const deviceService = {
  list:        (params = {}) => api.get('/devices',        { params }).then(r => r.data),
  stats:       ()            => api.get('/devices/stats').then(r => r.data.data),
  get:         (id)          => api.get(`/devices/${id}`).then(r => r.data.data),
  create:      (data)        => api.post('/devices', data).then(r => r.data.data),
  update:      (id, data)    => api.put(`/devices/${id}`, data).then(r => r.data.data),
  delete:      (id)          => api.delete(`/devices/${id}`).then(r => r.data),
  bulkStatus:  (ids, status) => api.post('/devices/bulk-status', { ids, status }).then(r => r.data),
  syncICloud:  ()            => api.post('/devices/sync-icloud').then(r => r.data),
};
