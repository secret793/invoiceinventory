import api from './api';

export const dataEntryService = {
  list:           (params = {}) => api.get('/data-entry', { params }).then(r => r.data),
  get:            (id)          => api.get(`/data-entry/${id}`).then(r => r.data.data),
  create:         (data)        => api.post('/data-entry', data).then(r => r.data.data),
  update:         (id, data)    => api.put(`/data-entry/${id}`, data).then(r => r.data.data),
  delete:         (id)          => api.delete(`/data-entry/${id}`).then(r => r.data),
  assignToAgent:  (id, data)    => api.post(`/data-entry/${id}/assign-to-agent`, data).then(r => r.data.data),
  returnDevice:   (id, note)    => api.post(`/data-entry/${id}/return`, { return_note: note }).then(r => r.data),
  dispatchLogs:   (id, params)  => api.get(`/data-entry/${id}/dispatch-logs`, { params }).then(r => r.data.data),
  receipts:       (id)          => api.get(`/data-entry/${id}/receipts`).then(r => r.data.data),
};
