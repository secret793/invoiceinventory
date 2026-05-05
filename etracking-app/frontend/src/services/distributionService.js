import api from './api';

export const distributionService = {
  list:    ()           => api.get('/distribution-points').then(r => r.data.data),
  get:     (id)         => api.get(`/distribution-points/${id}`).then(r => r.data.data),
  devices: (id, params) => api.get(`/distribution-points/${id}/devices`, { params }).then(r => r.data),
  create:  (data)       => api.post('/distribution-points', data).then(r => r.data.data),
  update:  (id, data)   => api.put(`/distribution-points/${id}`, data).then(r => r.data.data),
  delete:  (id)         => api.delete(`/distribution-points/${id}`).then(r => r.data),
};
